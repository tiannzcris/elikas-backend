<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'barangay_id', 'home_address', 'head_of_family_evacuee_id', 'is_4ps_beneficiary', 'name',
        'is_single_headed', 'head_is_minor', 'head_sex',
    ];

    protected $casts = [
        'is_4ps_beneficiary' => 'boolean',
        'is_single_headed' => 'boolean',
        'head_is_minor' => 'boolean',
    ];

    // The age brackets under 18 -- how Add Evacuee answers "is the head a
    // minor?" when the person being added is the head (their bracket is all
    // that's known about their age until a real birthdate is recorded).
    public const MINOR_AGE_BRACKETS = ['infant', 'toddler', 'preschooler', 'school_age', 'teenage'];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function headOfFamily(): BelongsTo
    {
        return $this->belongsTo(Evacuee::class, 'head_of_family_evacuee_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Evacuee::class);
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }

    /**
     * Single-headed household: only one person heads it. Asked once per
     * household (Add Evacuee's "New household"), never derived -- it says
     * nothing about parenting, so it can't come from is_solo_parent (a
     * grandmother heading her daughter's family alone is single-headed but
     * not a solo parent; a solo-parent daughter in her father's household
     * is a solo parent in a household she doesn't head). null = not yet known.
     */
    public function isSingleHeaded(): ?bool
    {
        return $this->is_single_headed;
    }

    /**
     * Child-headed household: the head is under 18. The linked head's real
     * date_of_birth always wins once recorded (same precedence as
     * Evacuee::age_bracket over age_bracket_override); until then, the
     * head_is_minor answer. null = not yet known.
     */
    public function isChildHeaded(): ?bool
    {
        $birthdate = $this->headOfFamily?->date_of_birth;

        return $birthdate ? $birthdate->age < 18 : $this->head_is_minor;
    }

    /**
     * The head's sex, for the Male/Female columns of the child-/single-
     * headed rows: the linked head's own sex when there is one, otherwise
     * the head_sex answer. null = not yet known (counted in neither column).
     */
    public function headSex(): ?string
    {
        return $this->headOfFamily?->sex ?? $this->head_sex;
    }
}
