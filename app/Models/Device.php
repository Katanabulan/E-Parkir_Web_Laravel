<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Device extends Model
{
    use HasFactory;

    protected $table = 'devices';

    public $timestamps = false;

    protected $fillable = [
        'device_code',
        'gate_type',
        'location',
        'status',
        'manual_override',
        'last_seen',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'manual_override' => 'integer',
            'last_seen' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'device_id');
    }

    public function parkingTransactionsIn(): HasMany
    {
        return $this->hasMany(ParkingTransaction::class, 'device_in_id');
    }

    public function parkingTransactionsOut(): HasMany
    {
        return $this->hasMany(ParkingTransaction::class, 'device_out_id');
    }

    public function isHeartbeatOnline(int $thresholdSeconds = 25): bool
    {
        if ($this->status === 'online') {
            return true;
        }
        if (!$this->last_seen) {
            return false;
        }
        return $this->last_seen->diffInSeconds(Carbon::now()) <= $thresholdSeconds;
    }
}
