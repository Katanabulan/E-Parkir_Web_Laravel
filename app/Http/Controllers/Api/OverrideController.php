<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class OverrideController extends Controller
{
    public function override(Request $request): JsonResponse
    {
        $action = trim($request->input('action', $request->query('action', '')));
        $isCheck = $request->has('check') || $action === 'check';
        $deviceCode = trim($request->input('device_code', $request->query('device_code', '')));
        $reason = trim($request->input('reason', 'Emergency override dibuka secara manual melalui Web Admin'));

        // 1. POLLING DARI ESP8266 (Memeriksa apakah ada perintah buka darurat)
        if ($isCheck) {
            $clientIp = $request->ip();
            if ($clientIp && !in_array($clientIp, ['127.0.0.1', '::1'])) {
                Cache::put('esp8266_last_ip', $clientIp, now()->addMinutes(5));
                if (!empty($deviceCode)) {
                    Cache::put("esp8266_ip_{$deviceCode}", $clientIp, now()->addMinutes(5));
                }
            }

            Device::where('device_code', $deviceCode ?: 'Gate-01')
                ->orWhereIn('device_code', ['Gate-01', 'GATE_MASUK_01'])
                ->update(['status' => 'online', 'last_seen' => now()]);

            $deviceQuery = Device::whereIn('manual_override', [1, 2]);
            if (!empty($deviceCode)) {
                $deviceQuery->where(function ($q) use ($deviceCode) {
                    $q->where('device_code', $deviceCode)->orWhere('device_code', 'Gate-01');
                });
            }

            $device = $deviceQuery->first();

            if ($device) {
                $overrideType = (int) $device->manual_override;
                // Reset flag override pada device dan seluruh aliasnya
                Device::whereIn('device_code', [$device->device_code, 'Gate-01', 'GATE_MASUK_01'])
                    ->update(['manual_override' => 0]);

                if ($overrideType === 2) {
                    return response()->json([
                        'action' => 'TICKET_DELAY_OPEN',
                        'delay_ms' => 3000,
                        'duration_ms' => 10000,
                        'device_code' => $device->device_code,
                        'message' => 'Tiket karcis masuk dicetak. Menunggu pengambilan karcis...'
                    ]);
                }

                return response()->json([
                    'action' => 'OPEN',
                    'duration_ms' => 8000,
                    'device_code' => $device->device_code,
                    'message' => 'Emergency override aktif! Palang dibuka.'
                ]);
            }

            return response()->json([
                'action' => 'NONE',
                'message' => 'Tidak ada perintah darurat'
            ]);
        }

        // 2. TRIGGER DARI WEB ADMIN
        if (empty($deviceCode)) {
            return response()->json(['status' => 'error', 'message' => 'device_code wajib diisi'], 400);
        }

        if ($deviceCode === 'ALL') {
            Device::query()->update(['manual_override' => 1]);

            $devices = Device::all();
            foreach ($devices as $d) {
                AccessLog::create([
                    'device_id' => $d->id,
                    'scan_uid' => 'ADMIN_OVERRIDE',
                    'access_method' => 'web_admin',
                    'status' => 'granted',
                    'notes' => $reason,
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'action' => 'OPEN',
                'duration_ms' => 8000,
                'beep_count' => 2,
                'led' => 'GREEN',
                'message' => 'Semua palang gerbang parkir berhasil dibuka darurat!',
                'device_code' => 'ALL'
            ]);
        }

        $device = Device::where('device_code', $deviceCode)
            ->orWhere('device_code', 'Gate-01')
            ->first();

        if (!$device) {
            $device = Device::orderBy('id', 'asc')->first();
        }

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => "Device '{$deviceCode}' tidak ditemukan"], 404);
        }

        $device->update(['manual_override' => 1]);

        AccessLog::create([
            'device_id' => $device->id,
            'scan_uid' => 'ADMIN_OVERRIDE',
            'access_method' => 'web_admin',
            'status' => 'granted',
            'notes' => $reason,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'action' => 'OPEN',
            'duration_ms' => 8000,
            'beep_count' => 2,
            'led' => 'GREEN',
            'message' => "Palang pintu gerbang {$device->device_code} ({$device->location}) berhasil dibuka darurat!",
            'device_code' => $device->device_code,
            'location' => $device->location
        ]);
    }
}
