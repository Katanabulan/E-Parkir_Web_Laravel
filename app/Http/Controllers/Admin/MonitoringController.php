<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\ParkingTransaction;
use App\Models\RfidCard;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\OverrideController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;
    public const RATE_PER_HOUR = 2000;

    public function index(Request $request)
    {
        $devices = Device::orderBy('id', 'asc')->get();
        $selectedDevice = $request->query('device', ($devices->first()->device_code ?? 'Gate-01'));

        $totalCapacity = self::TOTAL_CAPACITY;
        $parkedVehicles = ParkingTransaction::where('status', 'PARKED')
            ->with(['card.user', 'deviceIn'])
            ->orderBy('tap_in_time', 'desc')
            ->get();

        $parkedCount = $parkedVehicles->count();
        $parkedLt1 = $parkedVehicles->where('parking_floor', 1)->count();
        $parkedLt2 = $parkedVehicles->where('parking_floor', 2)->count();
        $availableLt1 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt1);
        $availableLt2 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt2);
        $availableSlots = max(0, $totalCapacity - $parkedCount);

        // Registered cards for simulator testing buttons
        $testCards = RfidCard::with('user')->orderBy('id', 'asc')->get();

        return view('admin.monitoring', compact(
            'devices',
            'selectedDevice',
            'totalCapacity',
            'parkedVehicles',
            'parkedCount',
            'parkedLt1',
            'parkedLt2',
            'availableLt1',
            'availableLt2',
            'availableSlots',
            'testCards'
        ));
    }

    public function tap(Request $request, ScanController $scanController): JsonResponse
    {
        return $scanController->scan($request);
    }

    public function override(Request $request, OverrideController $overrideController): JsonResponse
    {
        return $overrideController->override($request);
    }

    public function checkout(Request $request, ScanController $scanController): JsonResponse
    {
        // Manual checkout via Gate-01 or GATE_KELUAR_01
        $request->merge([
            'device_code' => $request->input('device_code', 'GATE_KELUAR_01'),
            'scan_uid' => $request->input('scan_uid'),
        ]);

        return $scanController->scan($request);
    }

    public function generateTicket(Request $request, ScanController $scanController): JsonResponse
    {
        return $scanController->generateTicket($request);
    }
}
