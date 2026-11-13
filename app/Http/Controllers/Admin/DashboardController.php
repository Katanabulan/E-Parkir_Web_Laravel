<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParkingTransaction;
use App\Models\Device;
use App\Models\RfidCard;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;
    public const RATE_PER_HOUR = 2000;

    public function index()
    {
        // 1. Total kendaraan yang saat ini sedang parkir (PARKED)
        $parkedCount = ParkingTransaction::where('status', 'PARKED')->count();
        $parkedLt1 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $parkedLt2 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $availableLt1 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt1);
        $availableLt2 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt2);
        $availableSlots = max(0, self::TOTAL_CAPACITY - $parkedCount);

        // 2. Pendapatan hari ini & total kas admin
        $today = Carbon::today()->toDateString();
        $todayTx = ParkingTransaction::where('status', 'COMPLETED')
            ->whereDate('tap_out_time', $today)
            ->get();
        $todayRevenue = (int) $todayTx->sum('total_fee');
        $todayCompletedTx = $todayTx->count();

        $totalAdminCash = (int) ParkingTransaction::where('status', 'COMPLETED')->sum('total_fee');

        $todayTotalIn = ParkingTransaction::whereDate('tap_in_time', $today)->count();
        $activeCardsCount = RfidCard::where('status', 'active')->count();

        $devices = Device::orderBy('id', 'asc')->get();

        return view('admin.dashboard', compact(
            'parkedCount',
            'parkedLt1',
            'parkedLt2',
            'availableLt1',
            'availableLt2',
            'availableSlots',
            'todayRevenue',
            'todayCompletedTx',
            'totalAdminCash',
            'todayTotalIn',
            'activeCardsCount',
            'devices'
        ));
    }

    public function apiStats(): JsonResponse
    {
        $parkedCount = ParkingTransaction::where('status', 'PARKED')->count();
        $parkedLt1 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $parkedLt2 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $availableLt1 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt1);
        $availableLt2 = max(0, self::CAPACITY_PER_FLOOR - $parkedLt2);
        $availableSlots = max(0, self::TOTAL_CAPACITY - $parkedCount);

        $today = Carbon::today()->toDateString();
        $todayTx = ParkingTransaction::where('status', 'COMPLETED')
            ->whereDate('tap_out_time', $today)
            ->get();
        $todayRevenue = (int) $todayTx->sum('total_fee');
        $todayCompletedTx = $todayTx->count();

        $totalAdminCash = (int) ParkingTransaction::where('status', 'COMPLETED')->sum('total_fee');
        $todayTotalIn = ParkingTransaction::whereDate('tap_in_time', $today)->count();
        $activeCardsCount = RfidCard::where('status', 'active')->count();

        // ESP8266 check (dinamis via IP Router/DHCP + heartbeat)
        $espIp = Cache::get('esp8266_last_ip');
        $isEspReachable = false;
        if ($espIp) {
            $fp = @fsockopen($espIp, 80, $errno, $errstr, 0.3);
            if ($fp) {
                $isEspReachable = true;
                fclose($fp);
                Device::whereIn('device_code', ['Gate-01', 'GATE_MASUK_01'])->update([
                    'status' => 'online',
                    'last_seen' => now()
                ]);
            }
        }
        if (!$isEspReachable) {
            $gate = Device::whereIn('device_code', ['Gate-01', 'GATE_MASUK_01'])->first();
            if ($gate && $gate->last_seen) {
                $sec = Carbon::parse($gate->last_seen)->diffInSeconds(now());
                if ($sec <= 25) {
                    $isEspReachable = true;
                }
            }
        }

        $devices = Device::orderBy('id', 'asc')->get();

        $activeRows = ParkingTransaction::with(['card.user', 'deviceIn'])
            ->where('status', 'PARKED')
            ->orderBy('tap_in_time', 'desc')
            ->get();

        $now = Carbon::now();
        $activeParkedList = $activeRows->map(function ($r) use ($now) {
            $inTime = $r->tap_in_time ? Carbon::parse($r->tap_in_time) : $now;
            $diffMin = max(1, (int) round($inTime->diffInMinutes($now)));
            $billedHours = max(1, (int) ceil($diffMin / 60));
            $fee = $billedHours * self::RATE_PER_HOUR;

            $isTicket = !empty($r->ticket_code);
            $cardBal = (int) ($r->card ? $r->card->balance : 0);
            $isSuff = $isTicket ? true : ($cardBal >= $fee);

            return [
                'id' => $r->id,
                'is_ticket' => $isTicket,
                'parking_floor' => $r->parking_floor ?? 1,
                'ticket_code' => $r->ticket_code,
                'uid' => $isTicket ? $r->ticket_code : ($r->card ? $r->card->uid : ''),
                'license_plate' => $isTicket ? 'Karcis Masuk' : ($r->card ? ($r->card->license_plate ?? 'NO-PLAT') : 'NO-PLAT'),
                'user_name' => $r->card && $r->card->user ? $r->card->user->name : ($isTicket ? 'Pengendara Umum (Karcis)' : 'Pengendara'),
                'phone' => $r->card && $r->card->user ? $r->card->user->phone : ($isTicket ? 'Tiket Masuk' : '-'),
                'gate_in' => $r->deviceIn ? $r->deviceIn->device_code : 'Gate-01',
                'gate_location' => $r->deviceIn ? $r->deviceIn->location : '',
                'tap_in_time' => $r->tap_in_time ? Carbon::parse($r->tap_in_time)->format('Y-m-d H:i:s') : '',
                'duration_minutes' => $diffMin,
                'billed_hours' => $billedHours,
                'estimated_fee' => $fee,
                'estimated_fee_formatted' => 'Rp ' . number_format($fee, 0, ',', '.'),
                'user_balance' => $cardBal,
                'user_balance_formatted' => $isTicket ? 'Bayar Tunai' : ('Rp ' . number_format($cardBal, 0, ',', '.')),
                'is_balance_sufficient' => $isSuff,
                'remaining_balance' => max(0, $cardBal - $fee),
                'remaining_balance_formatted' => $isTicket ? 'Bayar Tunai' : ('Rp ' . number_format(max(0, $cardBal - $fee), 0, ',', '.'))
            ];
        });

        $recentLogs = AccessLog::with('device')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($al) {
                return [
                    'id' => $al->id,
                    'created_at' => $al->created_at ? Carbon::parse($al->created_at)->format('Y-m-d H:i:s') : '',
                    'device_code' => $al->device ? $al->device->device_code : ($al->device_id ?? 'Gate-01'),
                    'location' => $al->device ? $al->device->location : '',
                    'scan_uid' => $al->scan_uid,
                    'access_method' => $al->access_method,
                    'status' => $al->status,
                    'notes' => $al->notes,
                ];
            });

        return response()->json([
            'status' => 'success',
            'server_time' => date('Y-m-d H:i:s'),
            'server_ip' => request()->server('SERVER_ADDR', request()->getHost()),
            'client_ip' => request()->ip(),
            'esp_online' => $isEspReachable,
            'esp_ip' => $espIp,
            'metrics' => [
                'parked_count' => $parkedCount,
                'parked_lt1' => $parkedLt1,
                'parked_lt2' => $parkedLt2,
                'available_lt1' => $availableLt1,
                'available_lt2' => $availableLt2,
                'available_slots' => $availableSlots,
                'total_capacity' => self::TOTAL_CAPACITY,
                'total_admin_cash' => $totalAdminCash,
                'total_admin_cash_formatted' => 'Rp ' . number_format($totalAdminCash, 0, ',', '.'),
                'today_revenue' => $todayRevenue,
                'today_revenue_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
                'today_completed_tx' => $todayCompletedTx,
                'today_total_in' => $todayTotalIn,
                'active_cards_count' => $activeCardsCount
            ],
            'devices' => $devices,
            'active_parked_list' => $activeParkedList,
            'recent_logs' => $recentLogs
        ]);
    }
}
