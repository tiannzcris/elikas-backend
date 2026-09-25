<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Serves OpenStreetMap tiles through this app's own server, caching each
 * one on first view so repeat views don't re-hit OSM.
 *
 * WHY THIS EXISTS: OSM actually blocked this application's tile access
 * once already. Investigation found three real causes, all of which this
 * class plus the four Leaflet call sites now address:
 *   1. Attribution was plain text with no link to
 *      openstreetmap.org/copyright (policy requires the link) -- fixed at
 *      each Leaflet tileLayer call site, not here.
 *   2. The deprecated {s}.tile.openstreetmap.org subdomain pattern was
 *      used; policy specifies exactly one host, tile.openstreetmap.org --
 *      also fixed at the call sites, and this proxy only ever talks to
 *      that one host.
 *   3. Zero caching of any kind: every resident's browser independently
 *      re-fetched the same tiles for the same small city forever, and the
 *      app had no visibility into any of it (tile requests went straight
 *      browser -> OSM, never touching this server, so nothing was ever
 *      logged). That's what this class fixes.
 *
 * STAYING WITHIN POLICY -- this is deliberately NOT a general-purpose or
 * public tile service, which OSM prohibits without prior arrangement:
 *   - Tiles are fetched ONLY on demand, in direct response to a real map
 *     view. There is deliberately no pre-warming/batch/"download the whole
 *     city" path anywhere in this class -- policy explicitly forbids "any
 *     pre-emptive fetching of tiles other than those a user is actively
 *     viewing", including building tile archives for later distribution.
 *   - Requests outside this app's own service area (Ligao City and a
 *     surrounding margin) or outside the zoom range its maps actually use
 *     are refused, so this can't be repurposed as an open world-wide tile
 *     proxy by anyone who finds the URL.
 *   - A global cap on outbound fetches to OSM across ALL visitors (see
 *     OSM_FETCHES_PER_MINUTE), separate from the per-IP route throttle --
 *     so even requests spread over many IPs can't turn this server into a
 *     bulk downloader.
 *   - A circuit breaker: after any upstream failure (network down, 403,
 *     429), OSM isn't contacted again for a short cool-down -- no retry
 *     storm against a server that's already refusing us, and no PHP
 *     workers tied up waiting on it.
 *   - Cached tiles expire (honouring OSM's own cache headers, floored at
 *     their documented 7-day minimum) rather than being kept forever, so
 *     this stays a cache and never drifts into being a stale mirror.
 *   - Outbound requests carry a real identifying User-Agent and Referer,
 *     which a browser physically cannot be made to send for <img>-based
 *     tile loads -- so routing through here actually makes this app MORE
 *     identifiable to OSM than it was before, not less.
 */
class TileProxyController extends Controller
{
    // Ligao City sits at roughly 13.14 N, 123.53 E. This box covers the
    // city plus a wide margin of surrounding Albay -- enough that panning
    // around the area still works normally, while keeping this from
    // answering arbitrary tile requests for anywhere else on earth.
    private const MIN_LAT = 12.7;

    private const MAX_LAT = 13.6;

    private const MIN_LON = 123.1;

    private const MAX_LON = 124.0;

    // Every map in this app opens at zoom 13 over the city. 8 gives room
    // to zoom out for regional context; 18 is Leaflet's own default
    // maxZoom, which none of this app's maps override -- so nothing
    // legitimate ever asks for 19, and 19 alone would be 3/4 of every tile
    // inside the service area.
    private const MIN_ZOOM = 8;

    private const MAX_ZOOM = 18;

    private const CIRCUIT_KEY = 'tile-proxy:circuit-open';

    private const CIRCUIT_COOLDOWN_SECONDS = 60;

    // A GLOBAL cap on outbound fetches to OSM, across all visitors
    // combined. The per-IP route throttle protects this server; this is
    // what protects OSM (and therefore us from being blocked again) --
    // without it, requests spread across many IPs could still make this
    // server bulk-download the service area, which is exactly the
    // "pre-emptive fetching" OSM's policy prohibits. Normal use sits far
    // below this: once a tile is cached, nobody fetches it again for at
    // least a week, so misses only come from genuinely new views.
    private const OSM_FETCH_BUDGET_KEY = 'tile-proxy:osm-fetches';

    private const OSM_FETCHES_PER_MINUTE = 120;

    public function show(int $z, int $x, int $y)
    {
        if ($z < self::MIN_ZOOM || $z > self::MAX_ZOOM) {
            abort(404);
        }

        // Guards against both a malformed request and a tile genuinely
        // outside this app's service area (see class docblock).
        $maxIndex = (2 ** $z) - 1;
        if ($x < 0 || $y < 0 || $x > $maxIndex || $y > $maxIndex) {
            abort(404);
        }

        if (! $this->tileIntersectsServiceArea($z, $x, $y)) {
            abort(404);
        }

        $cachePath = "tiles/{$z}/{$x}/{$y}.png";
        $expiryKey = "tile-expiry:{$z}:{$x}:{$y}";

        if (Storage::disk('local')->exists($cachePath)) {
            $expiresAt = Cache::get($expiryKey);

            if ($expiresAt !== null && now()->timestamp < $expiresAt) {
                return $this->serve(Storage::disk('local')->get($cachePath), fromCache: true);
            }
        }

        // Circuit breaker: after any upstream failure, stop calling OSM for a
        // short cool-down and serve stale-or-502 immediately instead. Two
        // reasons, both about exactly the situation this class exists for:
        // (1) if OSM is down, slow, or blocking us again, every tile request
        // would otherwise hang for the full timeout while holding a PHP
        // worker -- a single map view pulls dozens of tiles, so that can
        // stall the whole site, not just the map; (2) retrying OSM on every
        // request while it's refusing us is precisely the behaviour that
        // escalates a temporary block into a longer one.
        if (Cache::has(self::CIRCUIT_KEY)) {
            return $this->serveStaleOrFail($cachePath, $z, $x, $y, 'circuit open (recent upstream failure)', logIt: false);
        }

        if (RateLimiter::tooManyAttempts(self::OSM_FETCH_BUDGET_KEY, self::OSM_FETCHES_PER_MINUTE)) {
            // Logged once per window (Cache::add only succeeds for the first
            // caller), not once per tile -- a single line is the signal worth
            // keeping; hundreds of identical ones would bury it.
            return $this->serveStaleOrFail(
                $cachePath, $z, $x, $y, 'global OSM fetch budget exhausted',
                logIt: Cache::add('tile-proxy:budget-exhausted-logged', true, now()->addMinute()),
            );
        }
        RateLimiter::hit(self::OSM_FETCH_BUDGET_KEY, 60);

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('elikas.tile_user_agent'),
                'Referer' => config('app.url'),
            ])->connectTimeout(3)->timeout(8)->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");
        } catch (\Throwable $e) {
            Cache::put(self::CIRCUIT_KEY, true, now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS));

            return $this->serveStaleOrFail($cachePath, $z, $x, $y, $e->getMessage());
        }

        if (! $response->successful()) {
            // 403/429 is OSM telling us to back off -- honour it for the
            // cool-down rather than immediately asking again.
            Cache::put(self::CIRCUIT_KEY, true, now()->addSeconds(self::CIRCUIT_COOLDOWN_SECONDS));

            return $this->serveStaleOrFail($cachePath, $z, $x, $y, "HTTP {$response->status()}");
        }

        Storage::disk('local')->put($cachePath, $response->body());
        Cache::put($expiryKey, $this->resolveExpiry($response), now()->addYear());

        // The visibility this app previously had none of -- every genuine
        // outbound hit to OSM is now one log line. A sudden flood of these
        // is the early warning that something is re-fetching more than it
        // should, well before OSM notices and blocks anything.
        Log::info('Tile proxy: fetched from OSM', ['tile' => "{$z}/{$x}/{$y}"]);

        return $this->serve($response->body(), fromCache: false);
    }

    /**
     * A tile we already hold is better than a broken map if OSM is briefly
     * unreachable or rate-limiting us -- serving it slightly stale keeps
     * the map usable during exactly the situation where hammering OSM with
     * retries would be the worst possible response.
     */
    private function serveStaleOrFail(string $cachePath, int $z, int $x, int $y, string $reason, bool $logIt = true)
    {
        // Skipped while the circuit is open: the failure that tripped it was
        // already logged once, and logging every tile a map requests during
        // the cool-down would bury that one useful line in noise.
        if ($logIt) {
            Log::warning('Tile proxy: upstream OSM request failed', [
                'tile' => "{$z}/{$x}/{$y}",
                'reason' => $reason,
            ]);
        }

        if (Storage::disk('local')->exists($cachePath)) {
            return $this->serve(Storage::disk('local')->get($cachePath), fromCache: true, stale: true);
        }

        abort(502, 'Tile temporarily unavailable.');
    }

    /**
     * Policy: "Honour server caching headers (Cache-Control, Expires,
     * Etag). If unable to read headers, cache each tile for at least 7
     * days." So OSM's own header wins when it's usable, and the 7-day
     * minimum acts as a floor rather than a flat replacement -- a short or
     * missing header can never make this app cache LESS than the policy's
     * own stated minimum, which would mean more traffic to OSM, not less.
     */
    private function resolveExpiry($response): int
    {
        $floor = now()->addDays((int) config('elikas.tile_cache_min_days', 7))->timestamp;

        if ($cacheControl = $response->header('Cache-Control')) {
            if (preg_match('/max-age=(\d+)/i', $cacheControl, $matches)) {
                return max(now()->addSeconds((int) $matches[1])->timestamp, $floor);
            }
        }

        if ($expires = $response->header('Expires')) {
            $parsed = strtotime($expires);
            if ($parsed !== false) {
                return max($parsed, $floor);
            }
        }

        return $floor;
    }

    private function serve(string $body, bool $fromCache, bool $stale = false)
    {
        // Also lets each browser keep its own copy, so a repeat view within
        // the window doesn't even reach this server, let alone OSM. Policy
        // explicitly asks for exactly this ("a sufficient local cache to
        // ensure that repeat views do not unnecessarily re-download tiles").
        $browserTtl = (int) config('elikas.tile_cache_min_days', 7) * 86400;

        return response($body, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', "public, max-age={$browserTtl}")
            ->header('X-Tile-Cache', $stale ? 'STALE' : ($fromCache ? 'HIT' : 'MISS'));
    }

    /**
     * Standard slippy-map tile -> lat/lon conversion, used to reject tiles
     * outside this app's own service area (see class docblock for why that
     * boundary exists at all).
     */
    private function tileIntersectsServiceArea(int $z, int $x, int $y): bool
    {
        $n = 2 ** $z;

        $lonLeft = $x / $n * 360 - 180;
        $lonRight = ($x + 1) / $n * 360 - 180;
        $latTop = rad2deg(atan(sinh(M_PI * (1 - 2 * $y / $n))));
        $latBottom = rad2deg(atan(sinh(M_PI * (1 - 2 * ($y + 1) / $n))));

        return $lonRight >= self::MIN_LON
            && $lonLeft <= self::MAX_LON
            && $latTop >= self::MIN_LAT
            && $latBottom <= self::MAX_LAT;
    }
}
