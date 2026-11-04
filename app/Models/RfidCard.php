<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RfidCard extends Model
{
    use HasFactory;

    protected $table = 'rfid_cards';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'uid',
        'license_plate',
        'balance',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parkingTransactions(): HasMany
    {
        return $this->hasMany(ParkingTransaction::class, 'card_id');
    }

    public function activeTransaction(): HasOne
    {
        return $this->hasOne(ParkingTransaction::class, 'card_id')->where('status', 'PARKED');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function getBalanceFormattedAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }
}
