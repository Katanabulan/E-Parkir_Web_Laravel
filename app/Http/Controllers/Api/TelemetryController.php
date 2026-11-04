<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function telemetry(Request $request): JsonResponse
    {
        $deviceCode = trim($request->input('device_code', $request->query('device_code', '')));
        $status = strtolower(trim($request->input('status', 'online')));

        if (empty($deviceCode)) {
            return response()->json(['status' => 'error', 'message' => 'device_code wajib diisi'], 400);
        }

        $device = Device::where('device_code', $deviceCode)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Device tidak ditemukan'], 404);
        }

        $device->update([
            'status' => ($status === 'online' ? 'online' : 'offline'),
            'last_seen' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry diterima',
            'server_time' => now()->toDateTimeString(),
            'device_code' => $deviceCode
        ]);
    }
}
