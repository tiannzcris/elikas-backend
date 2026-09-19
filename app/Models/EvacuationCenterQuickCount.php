<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The EC Information Board's remaining stored figures: a running cumulative
 * headcount (families_cumulative/persons_cumulative -- see recordArrival()
 * for why this can't just be computed on the fly, and live*() below for why
 * "Now" can) and the sectoral breakdown (see EvacuationCenterController's
 * class-level notes -- sectoral flags aren't known at "Add Evacuee" time,
 * so that section stays a manually-reported aggregate, not something
 * derived from individual records). families_now/persons_now and the
 * age/sex breakdown are NOT stored here anymore -- see the live*() methods,
 * which compute them directly from real Evacuee/EvacuationRecord rows
 * every time the board is viewed.
 */
class EvacuationCenterQuickCount extends Model
{
    protected $fillable = [
        'evacuation_center_id', 'evacuation_event_id',
        'families_cumulative', 'persons_cumulative',
        'beneficiaries_4ps', 'updated_by',
    ];

    // Canonical bracket/category lists, in the exact order the EC
    // Information Board template presents them -- the single source of
    // truth both the controller (for zero-filling missing rows) and the
    // resource (for ordering output) build off of, so the two can never
    // drift out of sync with each other.
    public const AGE_BRACKETS = [
        'infant', 'toddler', 'preschooler', 'school_age', 'teenage', 'adult', 'senior_citizen',
    ];

    public const SECTORAL_GROUPS = [
        'pwd', 'child_headed_family', 'single_headed_family', 'solo_parent',
        'pregnant_women', 'lactating_mothers', 'four_ps_beneficiary', 'indigenous_peoples',
    ];

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sectoralGroups(): HasMany
    {
        return $this->hasMany(EvacuationCenterQuickCountSectoralGroup::class);
    }

    /**
     * The one shared hook for keeping families_cumulative/persons_cumulative
     * accurate: call this once, right after $evacuee ends up with an active
     * EvacuationRecord at $evacuationCenterId/$eventId that center hadn't
     * already counted -- whether that record was just CREATED
     * (FamilyController::store(), EvacueeController::addMember(),
     * EvacuationCenterController::addEvacuee()) or an existing one was
     * REPOINTED to a different center (FamilyController::updateEvacuationCenter()).
     * One shared method (not duplicated increment logic in each of those,
     * and not a model observer either) so "who counts as a cumulative
     * arrival here" can only ever be defined in one place, matching this
     * codebase's existing preference for explicit, traceable calls over
     * implicit event hooks. Takes plain IDs rather than model instances --
     * every call site already has the ID on hand (from validated request
     * input or a just-created/just-updated record) without needing to
     * fetch a model just for this.
     *
     * No-op for 'outside_center' displacement ($evacuationCenterId null) --
     * cumulative is inherently a per-CENTER figure, nothing to attribute it
     * to otherwise.
     *
     * families_cumulative increments only the FIRST time a given family
     * gets a record at this specific center+event -- determined by
     * querying for any OTHER EvacuationRecord this family already has here
     * (not by trusting a caller-supplied "is this a new family" flag),
     * so it stays correct even for a family whose other members already
     * arrived earlier, or who's arriving at a second center after already
     * being counted at a first. persons_cumulative increments every time,
     * unconditionally. Neither ever decrements -- see this model's own
     * docblock for why "Now" is the live figure and this one specifically
     * isn't.
     */
    public static function recordArrival(?int $evacuationCenterId, int $eventId, Evacuee $evacuee): void
    {
        if (! $evacuationCenterId) {
            return;
        }

        $quickCount = self::firstOrCreate(
            ['evacuation_center_id' => $evacuationCenterId, 'evacuation_event_id' => $eventId],
            ['families_cumulative' => 0, 'persons_cumulative' => 0, 'beneficiaries_4ps' => 0]
        );

        $quickCount->increment('persons_cumulative');

        $familyAlreadyHasARecordHere = EvacuationRecord::where('evacuation_center_id', $evacuationCenterId)
            ->where('evacuation_event_id', $eventId)
            ->where('evacuee_id', '!=', $evacuee->id)
            ->whereHas('evacuee', fn ($q) => $q->where('family_id', $evacuee->family_id))
            ->exists();

        if (! $familyAlreadyHasARecordHere) {
            $quickCount->increment('families_cumulative');
        }
    }

    private ?\Illuminate\Support\Collection $currentEvacueesCache = null;

    /**
     * Every evacuee CURRENTLY checked into this center for this event --
     * shared basis for liveFamiliesNow()/livePersonsNow()/liveAgeSexBreakdown()
     * so the three numbers can never disagree with each other about who
     * counts as "here right now" (and so calling all three back-to-back,
     * as the resource does, only runs this query once). Works on an
     * unsaved instance too (as long as evacuation_center_id/
     * evacuation_event_id are set), which is exactly the state
     * EvacuationCenterController::quickCount() hands this method when no
     * board row exists yet for this center+event.
     */
    private function currentEvacuees(): \Illuminate\Support\Collection
    {
        return $this->currentEvacueesCache ??= $this->evacuationCenter->evacuationRecords()
            ->where('evacuation_event_id', $this->evacuation_event_id)
            ->where('status', 'currently_evacuated')
            ->whereNull('date_out')
            ->with('evacuee')
            ->get()
            ->pluck('evacuee')
            ->unique('id');
    }

    public function liveFamiliesNow(): int
    {
        return $this->currentEvacuees()->pluck('family_id')->unique()->count();
    }

    public function livePersonsNow(): int
    {
        return $this->currentEvacuees()->count();
    }

    /**
     * One row per (bracket, sex) -- every combination always present,
     * zero-filled, matching the fixed-shape table the EC Board renders.
     * Uses Evacuee::age_bracket (real date_of_birth if known, otherwise
     * age_bracket_override), so a placeholder added via "Add Evacuee"
     * counts here immediately, before anyone fills in their real details.
     *
     * Always ends with one extra 'unclassified' row for anyone currently
     * here who is missing sex and/or age_bracket -- e.g. a record created
     * before this app tracked either (the now-removed member_count fast
     * path could produce these). Without this row such a person is simply
     * absent from every (bracket, sex) cell above while still counted in
     * livePersonsNow(), which is exactly the silent-mismatch bug this row
     * exists to surface instead of hide: a real DROMIC submission built
     * from a breakdown that quietly under-counts is worse than one that
     * visibly flags "N not yet classified". male_count/female_count on
     * this row are whoever's sex IS at least known (bracket still
     * missing); total_count is the FULL unclassified headcount including
     * anyone whose sex isn't known either, so
     * sum(every row's total) always equals livePersonsNow() exactly --
     * callers must use total_count for this row, not male_count +
     * female_count, which can be less than total_count when sex itself is
     * unknown for some of them.
     *
     * @return list<array{age_bracket: string, male_count: int, female_count: int, total_count?: int}>
     */
    public function liveAgeSexBreakdown(): array
    {
        $evacuees = $this->currentEvacuees();

        $rows = collect(self::AGE_BRACKETS)->map(fn ($bracket) => [
            'age_bracket' => $bracket,
            'male_count' => $evacuees->filter(fn ($e) => $e->age_bracket === $bracket && $e->sex === 'male')->count(),
            'female_count' => $evacuees->filter(fn ($e) => $e->age_bracket === $bracket && $e->sex === 'female')->count(),
        ]);

        $unclassified = $evacuees->filter(
            fn ($e) => ! in_array($e->age_bracket, self::AGE_BRACKETS, true) || ! in_array($e->sex, ['male', 'female'], true)
        );

        $rows->push([
            'age_bracket' => 'unclassified',
            'male_count' => $unclassified->filter(fn ($e) => $e->sex === 'male')->count(),
            'female_count' => $unclassified->filter(fn ($e) => $e->sex === 'female')->count(),
            'total_count' => $unclassified->count(),
        ]);

        return $rows->all();
    }
}
