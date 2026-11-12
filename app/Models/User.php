<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'auth_provider',
        'password',
        'phone',
        'license_plate',
        'avatar',
        'name_changes_count',
        'name_changed_at',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
        'can_change_name',
    ];

    protected function casts(): array
    {
        return [
            'name_changes_count' => 'integer',
            'name_changed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        if (!empty($this->avatar)) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }
            return asset('uploads/avatars/' . $this->avatar);
        }

        // Fallback default avatar jika dummy / tidak ada foto (cukup icon, bukan wajah orang)
        return asset('assets/images/default-avatar.svg');
    }

    public function getCanChangeNameAttribute(): bool
    {
        return (int) ($this->name_changes_count ?? 0) < 1;
    }

    public function rfidCard(): HasOne
    {
        return $this->hasOne(RfidCard::class, 'user_id');
    }

    public function parkingTransactions(): HasMany
    {
        return $this->hasMany(ParkingTransaction::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
