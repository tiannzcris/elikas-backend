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
        'sex', 'date_of_birth', 'civil_status', 'contact_number',
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

    protected $appends = ['full_name', 'age', 'age_bracket'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
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

    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    // Reproduces the 7 age brackets used in the DROMIC Region V report and the
    // EC Information Board template. Computed from date_of_birth rather than
    // stored, so a record never goes stale as the evacuee ages.
    public function getAgeBracketAttribute(): string
    {
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
}
