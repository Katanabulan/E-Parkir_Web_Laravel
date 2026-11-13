<?php

namespace App\Http\Controllers;

use App\Models\ParkingTransaction;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PublicStatusController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;
    public const RATE_PER_HOUR = 2000;

    public function index(Request $request)
    {
        $totalCapacity = self::TOTAL_CAPACITY;
        $now = Carbon::now();

        if ($request->ajax() || $request->query('ajax')) {
            $parkedRows = ParkingTransaction::with(['card.user', 'deviceIn'])
                ->where('status', 'PARKED')
                ->orderBy('tap_in_time', 'desc')
                ->get();

            $parkedCount = $parkedRows->count();
            $parkedLt1 = $parkedRows->where('parking_floor', 1)->count();
            $parkedLt2 = $parkedRows->where('parking_floor', 2)->count();
            $availableLt1 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt1);
            $availableLt2 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt2);
            $availableSlots = max(0, $totalCapacity - $parkedCount);

            $vehicles = $parkedRows->map(function ($r) use ($now) {
                $inTime = $r->tap_in_time ? Carbon::parse($r->tap_in_time) : $now;
                $diffSeconds = max(0, $inTime->diffInSeconds($now));
                $diffMinutes = max(1, (int) ceil($diffSeconds / 60));
                $billedHours = max(1, (int) ceil($diffMinutes / 60));
                $fee = $billedHours * self::RATE_PER_HOUR;

                $isTicket = !empty($r->ticket_code);
                return [
                    'id' => (int) $r->id,
                    'is_ticket' => $isTicket,
                    'parking_floor' => $r->parking_floor ?? 1,
                    'ticket_code' => $r->ticket_code,
                    'user_name' => $r->card && $r->card->user ? $r->card->user->name : ($isTicket ? 'Pengendara Umum' : 'Pengendara'),
                    'uid' => $isTicket ? $r->ticket_code : ($r->card ? $r->card->uid : ''),
                    'license_plate' => $r->card && $r->card->license_plate ? $r->card->license_plate : ($isTicket ? 'Karcis Masuk' : 'Non-Plat'),
                    'gate_in' => $r->deviceIn ? $r->deviceIn->device_code : 'Gate-01',
                    'gate_location' => $r->deviceIn ? $r->deviceIn->location : '',
                    'tap_in_time' => $r->tap_in_time ? Carbon::parse($r->tap_in_time)->format('Y-m-d H:i:s') : '',
                    'tap_in_formatted' => $r->tap_in_time ? Carbon::parse($r->tap_in_time)->format('H:i:s') . ' WIB' : '',
                    'duration_seconds' => $diffSeconds,
                    'duration_minutes' => $diffMinutes,
                    'billed_hours' => $billedHours,
                    'fee' => $fee,
                    'fee_formatted' => 'Rp ' . number_format($fee, 0, ',', '.')
                ];
            });

            // Status ESP8266 (dinamis via IP Router/DHCP + heartbeat)
            $espIp = Cache::get('esp8266_last_ip');
            $isEspOnline = false;
            if ($espIp) {
                $fp = @fsockopen($espIp, 80, $errno, $errstr, 0.2);
                if ($fp) {
                    $isEspOnline = true;
                    fclose($fp);
                    Device::whereIn('device_code', ['Gate-01', 'GATE_MASUK_01'])->update([
                        'status' => 'online',
                        'last_seen' => now()
                    ]);
                }
            }
            if (!$isEspOnline) {
                $gate = Device::whereIn('device_code', ['Gate-01', 'GATE_MASUK_01'])->first();
                if ($gate && $gate->last_seen) {
                    $sec = Carbon::parse($gate->last_seen)->diffInSeconds(now());
                    if ($sec <= 25) {
                        $isEspOnline = true;
                    }
                }
            }

            $today = Carbon::today()->toDateString();
            $todayIn = ParkingTransaction::whereDate('tap_in_time', $today)->count();
            $todayOut = ParkingTransaction::where('status', 'COMPLETED')->whereDate('tap_out_time', $today)->count();

            return response()->json([
                'status' => 'success',
                'server_time' => date('Y-m-d H:i:s'),
                'server_ip' => request()->server('SERVER_ADDR', request()->getHost()),
                'has_vehicles' => $parkedCount > 0,
                'parked_count' => $parkedCount,
                'parked_lt1' => $parkedLt1,
                'parked_lt2' => $parkedLt2,
                'available_lt1' => $availableLt1,
                'available_lt2' => $availableLt2,
                'capacity' => $totalCapacity,
                'available_slots' => $availableSlots,
                'esp_online' => $isEspOnline,
                'esp_ip' => $espIp,
                'today_in' => $todayIn,
                'today_out' => $todayOut,
                'vehicles' => $vehicles
            ]);
        }

        // View rendering
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

        return view('status', compact(
            'totalCapacity',
            'parkedVehicles',
            'parkedCount',
            'parkedLt1',
            'parkedLt2',
            'availableLt1',
            'availableLt2',
            'availableSlots',
            'occupancyRate'
        ));
    }
}
