<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id', 'barangay_id', 'name', 'email', 'password', 'contact_number', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function reliefDistributions(): HasMany
    {
        return $this->hasMany(ReliefDistribution::class, 'distributed_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    public function alertsSent(): HasMany
    {
        return $this->hasMany(Alert::class, 'sent_by');
    }

    // Convenience role checks used throughout controllers/policies instead of
    // comparing role_id magic numbers everywhere.
    public function hasRole(string $roleName): bool
    {
        return $this->role?->name === $roleName;
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrator');
    }

    public function isCswdPersonnel(): bool
    {
        return $this->hasRole('cswd_personnel');
    }

    public function isBarangayOfficial(): bool
    {
        return $this->hasRole('barangay_official');
    }
}
