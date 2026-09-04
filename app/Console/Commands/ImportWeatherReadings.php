<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use App\Models\WeatherReading;
use Illuminate\Console\Command;

class ImportWeatherReadings extends Command
{
    // Real PAGASA sentinel values, confirmed from PAGASA's own README
    // accompanying this dataset -- not assumed. -999 means the field is
    // genuinely missing for that day; -1 in RAINFALL specifically means
    // "trace" (rain occurred but measured under 0.1mm), a real reading,
    // not a missing one.
    private const MISSING_SENTINEL = -999.0;

    private const TRACE_SENTINEL = -1.0;

    // What "trace" rainfall is stored as -- not literal 0.0, so a future
    // query can still tell "trace amount detected" apart from "confirmed
    // no rain" if that distinction ever matters, while still being
    // effectively zero for SARIMA training purposes.
    private const TRACE_VALUE_MM = 0.05;

    // 1 m/s = 3.6 km/h. PAGASA's WIND_SPEED column is in m/s; this
    // system's wind_speed_kph column is, as the name says, kph. Every
    // value is multiplied by this during import -- getting this wrong
    // would silently corrupt every wind-speed figure by a factor of 3.6x.
    private const MS_TO_KPH = 3.6;

    protected $signature = 'weather:import {path : Path to the CSV file, relative to project root or absolute}
                            {--station=default : Station name to tag these readings with (the real PAGASA export itself does not include a station column)}
                            {--sample : Mark every imported row as non-authoritative sample/test data, NOT real PAGASA data}
                            {--dry-run : Parse and validate the whole file, but write nothing to the database -- just preview the first 10 parsed rows}';

    protected $description = 'Imports daily rainfall/wind readings from a real PAGASA CSV export (YEAR,MONTH,DAY,RAINFALL,WIND_SPEED,WIND_DIRECTION) for SARIMA forecasting.';

    public function handle(): int
    {
        $path = $this->argument('path');
        $absolutePath = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
            ? $path
            : base_path($path);

        if (! is_readable($absolutePath)) {
            $this->error("Cannot read file: {$absolutePath}");

            return self::FAILURE;
        }

        $handle = fopen($absolutePath, 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            $this->error('File is empty or not a valid CSV.');
            fclose($handle);

            return self::FAILURE;
        }

        // Matched by header name (case-insensitive, trimmed), not fixed
        // column position -- WIND_DIRECTION's position isn't assumed
        // either, since it's simply never read at all (not used by this
        // system).
        $header = array_map(fn ($h) => strtoupper(trim($h)), $header);
        $yearIdx = array_search('YEAR', $header, true);
        $monthIdx = array_search('MONTH', $header, true);
        $dayIdx = array_search('DAY', $header, true);
        $rainfallIdx = array_search('RAINFALL', $header, true);
        $windIdx = array_search('WIND_SPEED', $header, true);

        $missingColumns = array_filter([
            'YEAR' => $yearIdx, 'MONTH' => $monthIdx, 'DAY' => $dayIdx,
            'RAINFALL' => $rainfallIdx, 'WIND_SPEED' => $windIdx,
        ], fn ($idx) => $idx === false);

        if (! empty($missingColumns)) {
            $this->error('CSV is missing required column(s): '.implode(', ', array_keys($missingColumns)).'. Found columns: '.implode(', ', $header));
            fclose($handle);

            return self::FAILURE;
        }

        $isSample = (bool) $this->option('sample');
        $isDryRun = (bool) $this->option('dry-run');
        $station = trim((string) $this->option('station')) ?: 'default';
        $sourceFile = basename($absolutePath);

        $imported = 0;
        $updated = 0;
        $skippedInvalid = 0;
        $skippedProtected = 0;
        $skippedBothMissing = 0;
        $rowNumber = 1; // header was row 1
        $previewRows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $year = trim($row[$yearIdx] ?? '');
            $month = trim($row[$monthIdx] ?? '');
            $day = trim($row[$dayIdx] ?? '');

            if (! ctype_digit($year) || ! ctype_digit($month) || ! ctype_digit($day)
                || ! checkdate((int) $month, (int) $day, (int) $year)) {
                $this->warn("Row {$rowNumber}: invalid date (YEAR={$year}, MONTH={$month}, DAY={$day}) -- skipped.");
                $skippedInvalid++;

                continue;
            }

            $readingDate = sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);

            $rainfallRaw = trim($row[$rainfallIdx] ?? '');
            $windRaw = trim($row[$windIdx] ?? '');

            $rainfall = $this->parseRainfall($rainfallRaw);
            $wind = $this->parseWindSpeedKph($windRaw);

            if ($rainfallRaw !== '' && $rainfall === false) {
                $this->warn("Row {$rowNumber} ({$readingDate}): unrecognized RAINFALL value '{$rainfallRaw}' (not a number, not -999, not -1) -- field skipped.");
                $rainfall = null;
                $skippedInvalid++;
            } elseif ($rainfall instanceof MissingValue) {
                $rainfall = null;
            }

            if ($windRaw !== '' && $wind === false) {
                $this->warn("Row {$rowNumber} ({$readingDate}): unrecognized WIND_SPEED value '{$windRaw}' (not a number, not -999) -- field skipped.");
                $wind = null;
                $skippedInvalid++;
            } elseif ($wind instanceof MissingValue) {
                $wind = null;
            }

            if ($rainfall === null && $wind === null) {
                $skippedBothMissing++;

                continue;
            }

            if (count($previewRows) < 10) {
                $previewRows[] = [
                    $readingDate,
                    $station,
                    $rainfallRaw,
                    $rainfall === null ? 'NULL (missing)' : number_format($rainfall, 2),
                    $windRaw,
                    $wind === null ? 'NULL (missing)' : number_format($wind, 2),
                ];
            }

            if ($isDryRun) {
                $imported++; // counted as "would import" for the dry-run summary

                continue;
            }

            $existing = WeatherReading::where('reading_date', $readingDate)
                ->where('station', $station)
                ->first();

            // Sample imports never overwrite real PAGASA data already on
            // file -- protects against a stale test re-import clobbering
            // the real thing once it arrives.
            if ($isSample && $existing && ! $existing->is_sample) {
                $this->warn("Row {$rowNumber} ({$readingDate}/{$station}): already has real PAGASA data on file; refusing to overwrite with --sample data.");
                $skippedProtected++;

                continue;
            }

            $wasNew = ! $existing;

            WeatherReading::updateOrCreate(
                ['reading_date' => $readingDate, 'station' => $station],
                [
                    'rainfall_mm' => $rainfall,
                    'wind_speed_kph' => $wind,
                    'is_sample' => $isSample,
                    'source_file' => $sourceFile,
                ]
            );

            $wasNew ? $imported++ : $updated++;
        }

        fclose($handle);

        $this->newLine();
        $this->info($isDryRun ? 'DRY RUN -- nothing was written to the database.' : 'Import complete.');
        $this->table(
            ['Reading date', 'Station', 'Raw RAINFALL', 'rainfall_mm (parsed)', 'Raw WIND_SPEED (m/s)', 'wind_speed_kph (x3.6)'],
            $previewRows
        );

        $this->table(
            [$isDryRun ? 'Would import/update' : 'Imported', 'Updated', 'Skipped (invalid)', 'Skipped (protected)', 'Skipped (both fields missing)'],
            [[$imported, $updated, $skippedInvalid, $skippedProtected, $skippedBothMissing]]
        );

        if ($isDryRun) {
            $this->comment('Re-run without --dry-run to actually write these rows to weather_readings.');

            return self::SUCCESS;
        }

        SystemLog::create([
            'user_id' => null,
            'action' => 'weather_readings.imported',
            'description' => sprintf(
                '%s import from "%s" (station: %s): %d imported, %d updated, %d skipped invalid, %d skipped protected, %d skipped (both fields missing).',
                $isSample ? 'Sample/test' : 'Real PAGASA',
                $sourceFile,
                $station,
                $imported,
                $updated,
                $skippedInvalid,
                $skippedProtected,
                $skippedBothMissing
            ),
            'ip_address' => null,
        ]);

        if ($imported === 0 && $updated === 0) {
            $this->error('No rows were successfully imported.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Returns: a float (real reading), null wrapped as MissingValue (the
     * -999 sentinel), or false (couldn't parse at all -- not a number,
     * and not either recognized sentinel).
     */
    private function parseRainfall(string $raw): float|MissingValue|false
    {
        if ($raw === '') {
            return new MissingValue;
        }

        if (! is_numeric($raw)) {
            return false;
        }

        $value = (float) $raw;

        if (abs($value - self::MISSING_SENTINEL) < 0.001) {
            return new MissingValue;
        }

        if (abs($value - self::TRACE_SENTINEL) < 0.001) {
            return self::TRACE_VALUE_MM;
        }

        if ($value < 0) {
            // Some other unexplained negative value -- not one of the two
            // documented sentinels, and real rainfall can't be negative.
            return false;
        }

        return $value;
    }

    /**
     * Same three-way return as parseRainfall(), but converts m/s -> kph
     * on the way out for any genuine reading.
     */
    private function parseWindSpeedKph(string $raw): float|MissingValue|false
    {
        if ($raw === '') {
            return new MissingValue;
        }

        if (! is_numeric($raw)) {
            return false;
        }

        $value = (float) $raw;

        if (abs($value - self::MISSING_SENTINEL) < 0.001) {
            return new MissingValue;
        }

        if ($value < 0) {
            // WIND_SPEED has no "trace" convention -- any other negative
            // value here is unexplained, not a documented sentinel.
            return false;
        }

        return $value * self::MS_TO_KPH;
    }
}

/**
 * Marker type distinguishing "field is genuinely missing" (-999 sentinel)
 * from "field could not be parsed at all" (false) and from a real 0.0
 * reading -- three different outcomes that a bare nullable float can't
 * represent unambiguously.
 */
final class MissingValue {}
