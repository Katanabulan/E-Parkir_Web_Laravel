<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ParkingTransaction extends Model
{
    use HasFactory;

    protected $table = 'parking_transactions';

    public $timestamps = false;

    protected $fillable = [
        'ticket_code',
        'parking_floor',
        'payment_method',
        'card_id',
        'user_id',
        'device_in_id',
        'device_out_id',
        'tap_in_time',
        'tap_out_time',
        'duration_minutes',
        'total_fee',
        'balance_before',
        'balance_after',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'parking_floor' => 'integer',
            'tap_in_time' => 'datetime',
            'tap_out_time' => 'datetime',
            'duration_minutes' => 'integer',
            'total_fee' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(RfidCard::class, 'card_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deviceIn(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_in_id');
    }

    public function deviceOut(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_out_id');
    }

    public function isParked(): bool
    {
        return $this->status === 'PARKED';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isTicket(): bool
    {
        return !empty($this->ticket_code);
    }

    public function calculateEstimatedFee(int $ratePerHour = 2000): array
    {
        if (!$this->tap_in_time) {
            return [
                'duration_minutes' => 0,
                'billed_hours' => 1,
                'total_fee' => $ratePerHour,
                'total_fee_formatted' => 'Rp ' . number_format($ratePerHour, 0, ',', '.'),
            ];
        }

        $now = Carbon::now();
        $minutes = max(1, (int) round($this->tap_in_time->diffInMinutes($now)));
        $hours = (int) ceil($minutes / 60);
        $hours = max(1, $hours);
        $fee = $hours * $ratePerHour;

        return [
            'duration_minutes' => $minutes,
            'billed_hours' => $hours,
            'total_fee' => $fee,
            'total_fee_formatted' => 'Rp ' . number_format($fee, 0, ',', '.'),
        ];
    }
}
