<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;

    public function index()
    {
        $totalCapacity = self::TOTAL_CAPACITY;
        $parkedVehicles = ParkingTransaction::where('status', 'PARKED')
            ->with(['card.user', 'deviceIn'])
            ->orderBy('tap_in_time', 'asc')
            ->get();

        $parkedCount = $parkedVehicles->count();
        $parkedLt1 = $parkedVehicles->where('parking_floor', 1)->count();
        $parkedLt2 = $parkedVehicles->where('parking_floor', 2)->count();
        $availableLt1 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt1);
        $availableLt2 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt2);
        $availableSlots = max(0, $totalCapacity - $parkedCount);
        $occupancyRate = round(($parkedCount / $totalCapacity) * 100);

        // Map 20 parking slots (L1-1..10 dan L2-1..10)
        $slots = [];
        for ($i = 1; $i <= $totalCapacity; $i++) {
            $floor = $i <= 10 ? 1 : 2;
            $slotNum = $i <= 10 ? $i : ($i - 10);
            $slotCode = 'LT' . $floor . '-' . $slotNum;
            $vehicle = $parkedVehicles->get($i - 1); // 0-indexed
            $slots[] = [
                'code' => $slotCode,
                'floor' => $floor,
                'is_occupied' => $vehicle !== null,
                'vehicle' => $vehicle,
            ];
        }

        return view('admin.status', compact(
            'totalCapacity',
            'parkedVehicles',
            'parkedCount',
            'parkedLt1',
            'parkedLt2',
            'availableLt1',
            'availableLt2',
            'availableSlots',
            'occupancyRate',
            'slots'
        ));
    }
}
