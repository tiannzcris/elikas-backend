<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Evacuee extends Model
{
    protected $fillable = [
        'family_id', 'barangay_id', 'first_name', 'middle_name', 'last_name', 'suffix',
        'sex', 'date_of_birth', 'age_bracket_override', 'civil_status', 'contact_number',
        'is_pwd', 'pwd_type', 'is_pregnant', 'is_lactating', 'is_solo_parent',
        'is_indigenous_person', 'is_4ps_beneficiary', 'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_pwd' => 'boolean',
        'is_pregnant' => 'boolean',
        'is_lactating' => 'boolean',
        'is_solo_parent' => 'boolean',
        'is_indigenous_person' => 'boolean',
        'is_4ps_beneficiary' => 'boolean',
    ];

    protected $appends = ['full_name', 'age', 'age_bracket', 'is_placeholder'];

    // The four fields a "complete" evacuee record needs -- anything short
    // of all four is a placeholder. Kept as a single source of truth here
    // rather than a stored status column: a stored flag would need to be
    // kept in sync on every single write path (fast registration, add
    // member, incremental edit, any future bulk-import), and could drift
    // out of sync with the fields it's supposed to describe. A computed
    // check can't drift -- it's always exactly true or false for whatever
    // the row actually contains right now.
    private const IDENTITY_FIELDS = ['first_name', 'last_name', 'sex', 'date_of_birth'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * A 4Ps beneficiary is genuinely a household-level designation (the
     * whole family is enrolled in the program, not just one member), but
     * every place that records this evacuee's own is_4ps_beneficiary flag
     * (EC Board's addEvacuee(), EvacueeController::addMember()/update())
     * only ever asks about the one person in front of the form. Call this
     * right after any of those saves this evacuee's own flag, so ticking
     * it on even one member correctly flags the whole family -- never the
     * reverse: an unticked box on this one evacuee must not un-flag a
     * family another member, or the original registration, already
     * established as a 4Ps beneficiary.
     */
    public function propagateFourPsToFamily(): void
    {
        if ($this->is_4ps_beneficiary && $this->family && ! $this->family->is_4ps_beneficiary) {
            $this->family->update(['is_4ps_beneficiary' => true]);
        }
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function evacuationRecords(): HasMany
    {
        return $this->hasMany(EvacuationRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name} {$this->suffix}");
    }

    // Null for a placeholder with no date_of_birth yet -- Carbon::parse(null)
    // would otherwise silently resolve to "now" (age 0), which is how a
    // placeholder used to get miscounted as an infant instead of excluded.
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? Carbon::parse($this->date_of_birth)->age : null;
    }

    // Reproduces the 7 age brackets used in the DROMIC Region V report and the
    // EC Information Board template. Computed from date_of_birth rather than
    // stored, so a record never goes stale as the evacuee ages. Falls back to
    // age_bracket_override when date_of_birth isn't known yet -- "Add
    // Evacuee" on the EC Board (EvacuationCenterController::addEvacuee())
    // records a person's age bracket + sex directly, with no birthdate to
    // derive one from (see the migration that added that column). Real data
    // always wins once it exists: a real date_of_birth is used the instant
    // it's set via "Add details", even though age_bracket_override itself is
    // left in place afterward rather than being cleared -- it's just inert
    // history at that point, since this accessor never looks at it again for
    // that record. Still null (not a guessed bracket) when NEITHER is known
    // -- every age/sex filter elsewhere (DromicRegionVReportService,
    // EcInformationBoardReportService, EvacuationCenterQuickCount's live*()
    // methods) compares against one of the 7 bracket strings, so null simply
    // never matches any of them and the placeholder is excluded from every
    // bracket, rather than being force-counted into "infant".
    public function getAgeBracketAttribute(): ?string
    {
        if (! $this->date_of_birth) {
            return $this->age_bracket_override;
        }

        $ageInMonths = Carbon::parse($this->date_of_birth)->diffInMonths(now());
        $age = $this->age;

        return match (true) {
            $ageInMonths <= 6 => 'infant',
            $ageInMonths <= 24 => 'toddler',
            $age <= 5 => 'preschooler',
            $age <= 12 => 'school_age',
            $age <= 17 => 'teenage',
            $age <= 59 => 'adult',
            default => 'senior_citizen',
        };
    }

    // True if any of the four core identity fields are still missing --
    // exactly the fields a fast/headcount-only registration leaves blank.
    // See the IDENTITY_FIELDS docblock above for why this is computed
    // rather than a stored column.
    public function getIsPlaceholderAttribute(): bool
    {
        foreach (self::IDENTITY_FIELDS as $field) {
            if ($this->{$field} === null) {
                return true;
            }
        }

        return false;
    }
}
