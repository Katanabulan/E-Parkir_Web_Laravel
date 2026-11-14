<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\RfidCard;
use App\Models\ParkingTransaction;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\ReceiptPrinterService;

class ScanController extends Controller
{
    public const MINIMUM_PARKING_BALANCE = 2000;
    public const RATE_PER_HOUR = 2000;
    public const COOLDOWN_SECONDS = 5;
    public const CAPACITY_PER_FLOOR = 10;
    public const TOTAL_CAPACITY = 20;

    /**
     * Hitung alokasi lantai parkir:
     * - Lantai 1 diisi terlebih dahulu (kapasitas 10).
     * - Jika Lantai 1 penuh (10), alokasi berpindah ke Lantai 2 (kapasitas 10).
     * - Jika Lantai 1 dan Lantai 2 penuh (20), return null (parkir penuh).
     */
    public static function allocateParkingFloor(): ?int
    {
        $parkedLt1 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        if ($parkedLt1 < self::CAPACITY_PER_FLOOR) {
            return 1;
        }

        $parkedLt2 = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        if ($parkedLt2 < self::CAPACITY_PER_FLOOR) {
            return 2;
        }

        return null;
    }

    public function scan(Request $request): JsonResponse
    {
        $deviceCode = trim($request->input('device_code', $request->query('device_code', '')));
        $scanUid = strtoupper(trim($request->input('scan_uid', $request->query('scan_uid', ''))));

        if (empty($deviceCode) || empty($scanUid)) {
            return response()->json([
                'status' => 'error',
                'action' => 'REJECTED',
                'message' => 'Parameter device_code dan scan_uid wajib diisi'
            ], 400);
        }

        // 1. Validasi Device
        $device = Device::where('device_code', $deviceCode)
            ->orWhere(function ($query) use ($deviceCode) {
                if (in_array($deviceCode, ['Gate-01', 'GATE_MASUK_01', 'GATE_KELUAR_01'])) {
                    $query->where('device_code', 'Gate-01');
                }
            })
            ->first();

        if (!$device) {
            $device = Device::orderBy('id', 'asc')->first();
        }

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'action' => 'REJECTED',
                'message' => "Device dengan kode '{$deviceCode}' tidak terdaftar"
            ], 404);
        }

        // Perbarui status device dan last_seen
        $clientIp = $request->ip();
        if ($clientIp && !in_array($clientIp, ['127.0.0.1', '::1'])) {
            Cache::put('esp8266_last_ip', $clientIp, now()->addMinutes(5));
        }
        $device->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        // 2. Validasi Anti-Spam / Cooldown Gerbang (3 detik antar tap)
        $lastLog = AccessLog::where('device_id', $device->id)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLog && $lastLog->created_at) {
            $elapsed = time() - strtotime($lastLog->created_at);
            if ($elapsed >= 0 && $elapsed < self::COOLDOWN_SECONDS) {
                $waitSec = self::COOLDOWN_SECONDS - $elapsed;
                return response()->json([
                    'status' => 'error',
                    'action' => 'RATE_LIMITED',
                    'beep_count' => 1,
                    'led' => 'YELLOW',
                    'message' => "Mohon tunggu {$waitSec} detik. Gerbang sedang memproses kendaraan sebelumnya.",
                    'cooldown_remaining' => $waitSec
                ], 429);
            }
        }

        // 2.1 Cek Tombol Tiket Karcis Masuk (Pengendara Tanpa Kartu)
        if ($scanUid === 'TICKET_BUTTON' || $scanUid === 'TICKET' || in_array($request->input('action'), ['ticket', 'generate_ticket'])) {
            return $this->generateTicket($request);
        }

        // 2.2 Cek Apakah Ini Scan / Input Kode Tiket Karcis (LT1-..., LT2-..., TKT-... atau scan URL karcis)
        if (preg_match('/((?:LT\d+|TKT)-[A-Z0-9\-]+)/i', $scanUid, $m)) {
            return $this->handleTicketExit($device, $m[1], $request);
        }
        if (str_starts_with($scanUid, 'LT') || str_starts_with($scanUid, 'TKT') || ParkingTransaction::where('ticket_code', $scanUid)->exists()) {
            return $this->handleTicketExit($device, $scanUid, $request);
        }

        // 3. Validasi Kartu RFID
        $card = RfidCard::with('user')->where('uid', $scanUid)->first();

        // JIKA KARTU BELUM TERDAFTAR
        if (!$card) {
            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => $scanUid,
                'access_method' => 'rfid',
                'status' => 'denied',
                'notes' => 'Kartu belum terdaftar (Menunggu pendaftaran di admin)',
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'unregistered',
                'action' => 'UNREGISTERED',
                'beep_count' => 1,
                'led' => 'YELLOW',
                'message' => 'Kartu belum terdaftar! Silakan daftarkan kartu pada menu Kelola Kartu RFID.',
                'scan_uid' => $scanUid
            ], 200);
        }

        $userName = $card->user ? $card->user->name : 'Pengendara';

        // JIKA KARTU DIBLOKIR
        if ($card->isBlocked()) {
            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => $scanUid,
                'access_method' => 'rfid',
                'status' => 'denied',
                'notes' => "Kartu diblokir oleh sistem (User: {$userName})",
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'error',
                'action' => 'DENIED',
                'beep_count' => 3,
                'led' => 'RED',
                'message' => 'Akses Ditolak: Kartu dinonaktifkan/diblokir!',
                'scan_uid' => $scanUid,
                'user_name' => $userName
            ], 403);
        }

        // 4. Cek Transaksi Parkir Aktif
        $activeTx = ParkingTransaction::where('card_id', $card->id)
            ->where('status', 'PARKED')
            ->orderBy('id', 'desc')
            ->first();

        $gateType = $device->gate_type; // 'IN', 'OUT', 'BIDIRECTIONAL'

        if ($gateType === 'IN') {
            if ($activeTx) {
                AccessLog::create([
                    'device_id' => $device->id,
                    'scan_uid' => $scanUid,
                    'access_method' => 'rfid',
                    'status' => 'denied',
                    'notes' => "Ditolak: Pengendara ({$userName}) sudah berada di dalam area parkir",
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'action' => 'DENIED',
                    'beep_count' => 3,
                    'led' => 'RED',
                    'message' => 'Akses Ditolak: Kendaraan sudah berada di dalam area parkir!'
                ], 400);
            }
            $flow = 'TAP_IN';
        } elseif ($gateType === 'OUT') {
            if (!$activeTx) {
                AccessLog::create([
                    'device_id' => $device->id,
                    'scan_uid' => $scanUid,
                    'access_method' => 'rfid',
                    'status' => 'denied',
                    'notes' => "Ditolak: Pengendara ({$userName}) belum memiliki riwayat tap-in",
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'action' => 'DENIED',
                    'beep_count' => 3,
                    'led' => 'RED',
                    'message' => 'Akses Ditolak: Kendaraan belum tercatat masuk (Tap-In)!'
                ], 400);
            }
            $flow = 'TAP_OUT';
        } else {
            $flow = $activeTx ? 'TAP_OUT' : 'TAP_IN';
        }

        // 5. Eksekusi Berdasarkan Flow
        if ($flow === 'TAP_IN') {
            $currentBalance = (int) $card->balance;
            if ($currentBalance < self::MINIMUM_PARKING_BALANCE) {
                $minFormatted = 'Rp ' . number_format(self::MINIMUM_PARKING_BALANCE, 0, ',', '.');
                $balFormatted = 'Rp ' . number_format($currentBalance, 0, ',', '.');

                AccessLog::create([
                    'device_id' => $device->id,
                    'scan_uid' => $scanUid,
                    'access_method' => 'rfid',
                    'status' => 'denied',
                    'notes' => "Tap-in Ditolak: Saldo kartu {$balFormatted} tidak mencukupi (Minimal {$minFormatted})",
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'action' => 'DENIED',
                    'beep_count' => 3,
                    'led' => 'RED',
                    'message' => "Akses Ditolak: Saldo tidak mencukupi! Minimal saldo {$minFormatted}. Saldo Anda: {$balFormatted}. Silakan isi ulang saldo di dashboard pengendara.",
                    'balance' => $currentBalance,
                    'balance_formatted' => $balFormatted,
                    'scan_uid' => $scanUid,
                    'user_name' => $userName
                ], 403);
            }

            // Cek Kapasitas Parkir per Lantai (LT 1 maks 10, jika penuh beralih ke LT 2 maks 10)
            $allocatedFloor = self::allocateParkingFloor();
            if ($allocatedFloor === null) {
                AccessLog::create([
                    'device_id' => $device->id,
                    'scan_uid' => $scanUid,
                    'access_method' => 'rfid',
                    'status' => 'denied',
                    'notes' => "Tap-in Ditolak: Seluruh area parkir penuh (LT 1 & LT 2: 20/20). Pengendara: {$userName}",
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'action' => 'DENIED',
                    'beep_count' => 3,
                    'led' => 'RED',
                    'message' => 'Area Parkir Penuh! Lantai 1 & Lantai 2 telah terisi penuh (20/20). Mohon tunggu kendaraan lain keluar.'
                ], 400);
            }

            // Simpan Transaksi Baru
            $now = Carbon::now();
            $tx = ParkingTransaction::create([
                'card_id' => $card->id,
                'user_id' => $card->user_id,
                'parking_floor' => $allocatedFloor,
                'device_in_id' => $device->id,
                'tap_in_time' => $now,
                'status' => 'PARKED',
                'created_at' => $now,
            ]);

            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => $scanUid,
                'access_method' => 'rfid',
                'status' => 'granted',
                'notes' => "Tap-in Berhasil (Lantai {$allocatedFloor}). Pengendara: {$userName}. Saldo: Rp " . number_format($currentBalance, 0, ',', '.'),
                'created_at' => $now,
            ]);

            return response()->json([
                'status' => 'success',
                'action' => 'OPEN',
                'gate_target' => 'IN',
                'duration_ms' => 5000,
                'beep_count' => 2,
                'led' => 'GREEN',
                'flow' => 'TAP_IN',
                'parking_floor' => $allocatedFloor,
                'message' => "Akses Masuk Diterima. Silakan parkir di LANTAI {$allocatedFloor} (LT {$allocatedFloor}). Selamat datang, {$userName}! Saldo Kartu: Rp " . number_format($currentBalance, 0, ',', '.'),
                'data' => [
                    'transaction_id' => $tx->id,
                    'user_name' => $userName,
                    'parking_floor' => $allocatedFloor,
                    'parking_floor_label' => "Lantai {$allocatedFloor} (LT {$allocatedFloor})",
                    'balance' => $currentBalance,
                    'balance_formatted' => 'Rp ' . number_format($currentBalance, 0, ',', '.'),
                    'tap_in_time' => $now->toDateTimeString(),
                    'device_code' => $device->device_code,
                    'gate_location' => $device->location
                ]
            ], 200);

        } else { // TAP_OUT
            $now = Carbon::now();
            $tapInTime = $activeTx->tap_in_time ? Carbon::parse($activeTx->tap_in_time) : $now;
            $durationSeconds = max(0, $tapInTime->diffInSeconds($now));
            $durationMinutes = max(1, (int) ceil($durationSeconds / 60));

            $billedHours = max(1, (int) ceil($durationMinutes / 60));
            $totalFee = $billedHours * self::RATE_PER_HOUR;

            $currentBalance = (int) $card->balance;
            if ($currentBalance < $totalFee) {
                $shortage = $totalFee - $currentBalance;
                $feeFormatted = 'Rp ' . number_format($totalFee, 0, ',', '.');
                $balFormatted = 'Rp ' . number_format($currentBalance, 0, ',', '.');
                $shortFormatted = 'Rp ' . number_format($shortage, 0, ',', '.');

                AccessLog::create([
                    'device_id' => $device->id,
                    'scan_uid' => $scanUid,
                    'access_method' => 'rfid',
                    'status' => 'denied',
                    'notes' => "Tap-out Ditolak: Saldo kartu {$balFormatted} kurang untuk biaya {$feeFormatted} (Kurang {$shortFormatted})",
                    'created_at' => now(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'action' => 'DENIED',
                    'beep_count' => 3,
                    'led' => 'RED',
                    'message' => "Akses Keluar Ditolak: Saldo tidak mencukupi! Biaya Parkir: {$feeFormatted}, Saldo Kartu Anda: {$balFormatted} (Kurang {$shortFormatted}). Silakan isi ulang saldo di menu pengendara.",
                    'total_fee' => $totalFee,
                    'balance' => $currentBalance,
                    'shortage' => $shortage,
                    'scan_uid' => $scanUid,
                    'user_name' => $userName
                ], 403);
            }

            // Potong Saldo Kartu
            $newBalance = $currentBalance - $totalFee;
            $card->update(['balance' => $newBalance]);

            // Update Transaksi Parkir
            $activeTx->update([
                'device_out_id' => $device->id,
                'tap_out_time' => $now,
                'duration_minutes' => $durationMinutes,
                'total_fee' => $totalFee,
                'balance_before' => $currentBalance,
                'balance_after' => $newBalance,
                'status' => 'COMPLETED',
            ]);

            $feeFormatted = 'Rp ' . number_format($totalFee, 0, ',', '.');
            $newBalFormatted = 'Rp ' . number_format($newBalance, 0, ',', '.');

            $notes = "Tap-out Berhasil. Biaya: {$feeFormatted} dipotong dari saldo (Sisa Saldo: {$newBalFormatted}). Uang masuk ke Kas Admin. (Durasi: {$durationMinutes} Menit / {$billedHours} Jam)";
            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => $scanUid,
                'access_method' => 'rfid',
                'status' => 'granted',
                'notes' => $notes,
                'created_at' => $now,
            ]);

            // Cetak langsung otomatis ke printer thermal jika terpasang di Windows
            $printedDirect = ReceiptPrinterService::autoPrintDirect($activeTx);

            return response()->json([
                'status' => 'success',
                'action' => 'OPEN',
                'gate_target' => 'OUT',
                'duration_ms' => 5000,
                'beep_count' => 2,
                'led' => 'GREEN',
                'flow' => 'TAP_OUT',
                'message' => "Parkir Selesai. Biaya: {$feeFormatted} berhasil dipotong dari saldo. Sisa Saldo: {$newBalFormatted}. Uang diterima di kas Admin.",
                'data' => [
                    'transaction_id' => $activeTx->id,
                    'user_name' => $userName,
                    'tap_in_time' => $activeTx->tap_in_time ? $activeTx->tap_in_time->toDateTimeString() : '',
                    'tap_out_time' => $now->toDateTimeString(),
                    'duration_minutes' => $durationMinutes,
                    'billed_hours' => $billedHours,
                    'total_fee' => $totalFee,
                    'total_fee_formatted' => $feeFormatted,
                    'balance_before' => $currentBalance,
                    'balance_before_formatted' => 'Rp ' . number_format($currentBalance, 0, ',', '.'),
                    'balance_after' => $newBalance,
                    'balance_after_formatted' => $newBalFormatted,
                    'admin_received_fee' => $totalFee,
                    'device_code' => $device->device_code,
                    'gate_location' => $device->location,
                    'receipt_id' => $activeTx->id,
                    'receipt_url' => route('receipt.show', $activeTx->id),
                    'receipt_print_url' => route('receipt.show', ['id' => $activeTx->id, 'autoprint' => 1]),
                    'printed_direct' => $printedDirect
                ]
            ], 200);
        }
    }

    /**
     * Alur Masuk Karcis Tiket (Pengendara Tanpa Kartu RFID menekan tombol)
     */
    public function generateTicket(Request $request): JsonResponse
    {
        $deviceCode = trim($request->input('device_code', $request->query('device_code', 'GATE_MASUK_01')));

        // 1. Validasi Device
        $device = Device::where('device_code', $deviceCode)
            ->orWhere(function ($query) use ($deviceCode) {
                if (in_array($deviceCode, ['Gate-01', 'GATE_MASUK_01', 'GATE_KELUAR_01'])) {
                    $query->where('device_code', 'Gate-01')->orWhere('device_code', 'GATE_MASUK_01');
                }
            })
            ->first();

        if (!$device) {
            $device = Device::orderBy('id', 'asc')->first();
        }

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'action' => 'REJECTED',
                'message' => "Device dengan kode '{$deviceCode}' tidak terdaftar"
            ], 404);
        }

        $device->update([
            'status' => 'online',
            'last_seen' => now(),
        ]);

        // 2. Cooldown Gerbang
        $lastLog = AccessLog::where('device_id', $device->id)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLog && $lastLog->created_at) {
            $elapsed = time() - strtotime($lastLog->created_at);
            if ($elapsed >= 0 && $elapsed < self::COOLDOWN_SECONDS) {
                $waitSec = self::COOLDOWN_SECONDS - $elapsed;
                return response()->json([
                    'status' => 'error',
                    'action' => 'RATE_LIMITED',
                    'beep_count' => 1,
                    'led' => 'YELLOW',
                    'message' => "Mohon tunggu {$waitSec} detik. Gerbang sedang memproses kendaraan sebelumnya.",
                    'cooldown_remaining' => $waitSec
                ], 429);
            }
        }

        // 3. Cek Kapasitas Parkir per Lantai (LT 1 maks 10, jika penuh ke LT 2 maks 10)
        $allocatedFloor = self::allocateParkingFloor();
        if ($allocatedFloor === null) {
            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => 'BUTTON_FULL',
                'access_method' => 'ticket',
                'status' => 'denied',
                'notes' => 'Tiket Masuk Ditolak: Seluruh area parkir sudah penuh (LT 1 & LT 2: 20/20)',
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'error',
                'action' => 'DENIED',
                'beep_count' => 3,
                'led' => 'RED',
                'message' => 'Area Parkir Penuh! Lantai 1 & Lantai 2 telah terisi penuh (20/20). Mohon tunggu kendaraan lain keluar.'
            ], 400);
        }

        $floorPrefix = 'LT' . $allocatedFloor;

        // 4. Generate Kode Tiket Unik Sesuai Lantai (LT1-... atau LT2-...)
        $now = Carbon::now();
        $randomHex = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $ticketCode = $floorPrefix . '-' . $now->format('ymd') . '-' . $randomHex;

        while (ParkingTransaction::where('ticket_code', $ticketCode)->exists()) {
            $randomHex = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $ticketCode = $floorPrefix . '-' . $now->format('ymd') . '-' . $randomHex;
        }

        // 5. Simpan Transaksi Parkir Karcis
        $tx = ParkingTransaction::create([
            'ticket_code' => $ticketCode,
            'parking_floor' => $allocatedFloor,
            'card_id' => null,
            'user_id' => null,
            'device_in_id' => $device->id,
            'tap_in_time' => $now,
            'status' => 'PARKED',
            'payment_method' => 'cash',
            'created_at' => $now,
        ]);

        AccessLog::create([
            'device_id' => $device->id,
            'scan_uid' => $ticketCode,
            'access_method' => 'ticket',
            'status' => 'granted',
            'notes' => "Tombol Karcis Masuk Ditekan. Tiket #{$ticketCode} (Lantai {$allocatedFloor}) berhasil dicetak. Pengendara Umum masuk.",
            'created_at' => $now,
        ]);

        // 5.1 Jika request datang dari Web Simulator (bukan tombol fisik ESP8266),
        // picu manual_override = 2 (tipe tiket dengan jeda pengambilan karcis)
        // agar ESP8266 memberi jeda 4.5 detik untuk ambil tiket sebelum palang fisik dibuka.
        $cachedEspIp = Cache::get('esp8266_last_ip');
        $isFromHardwareEsp = $request->input('scan_uid') === 'TICKET_BUTTON' 
            || $request->input('access_method') === 'esp8266'
            || ($cachedEspIp && $request->ip() === $cachedEspIp);

        if (!$isFromHardwareEsp) {
            Device::whereIn('device_code', [$device->device_code, 'Gate-01', 'GATE_MASUK_01'])
                ->update(['manual_override' => 2]);
        }

        // 6. Format Teks Tiket Karcis untuk Thermal Printer
        $printedDirect = false;
        $ticketText = ReceiptPrinterService::format32TicketEntry($tx);

        return response()->json([
            'status' => 'success',
            'action' => 'OPEN',
            'gate_target' => 'IN',
            'duration_ms' => 10000,
            'beep_count' => 2,
            'led' => 'GREEN',
            'flow' => 'TICKET_ENTRY',
            'ticket_code' => $ticketCode,
            'parking_floor' => $allocatedFloor,
            'message' => "Tiket {$ticketCode} berhasil dicetak! Silakan parkir di LANTAI {$allocatedFloor} (LT {$allocatedFloor}). Ambil tiket & palang dibuka.",
            'data' => [
                'transaction_id' => $tx->id,
                'ticket_code' => $ticketCode,
                'parking_floor' => $allocatedFloor,
                'parking_floor_label' => "Lantai {$allocatedFloor} (LT {$allocatedFloor})",
                'user_name' => 'Pengendara Umum (Karcis)',
                'payment_method' => 'cash',
                'tap_in_time' => $now->toDateTimeString(),
                'tap_in_formatted' => $now->format('d/m/Y H:i:s'),
                'rate_per_hour' => self::RATE_PER_HOUR,
                'device_code' => $device->device_code,
                'gate_location' => $device->location,
                'printed_direct' => $printedDirect,
                'ticket_text' => $ticketText,
                'receipt_url' => route('receipt.show', $tx->id)
            ]
        ], 200);
    }

    /**
     * Alur Keluar Karcis Tiket (Scan Kode Tiket TKT-... di Pos Keluar / Kasir)
     */
    public function handleTicketExit(Device $device, string $ticketCode, Request $request): JsonResponse
    {
        $tx = ParkingTransaction::where('ticket_code', $ticketCode)
            ->where('status', 'PARKED')
            ->orderBy('id', 'desc')
            ->first();

        if (!$tx) {
            $completedTx = ParkingTransaction::where('ticket_code', $ticketCode)
                ->where('status', 'COMPLETED')
                ->orderBy('id', 'desc')
                ->first();

            // Grace period: Jika karcis tiket baru saja diselesaikan / dibayar lunas (dalam kurun waktu 30 menit terakhir)
            // pengendara diizinkan keluar dan palang gerbang keluar wajib dibuka!
            if ($completedTx) {
                $completedTime = $completedTx->tap_out_time ? Carbon::parse($completedTx->tap_out_time) : Carbon::parse($completedTx->updated_at);
                $diffMinutes = max(0, $completedTime->diffInMinutes(Carbon::now()));

                if ($diffMinutes <= 30) {
                    // Trigger manual_override agar palang hardware ESP8266 bergerak naik
                    Device::whereIn('device_code', [$device->device_code, 'Gate-01', 'GATE_MASUK_01', 'GATE_KELUAR_01'])
                        ->update(['manual_override' => 1]);

                    $notes = "Karcis Tiket #{$ticketCode} telah lunas ({$diffMinutes} menit lalu). Akses keluar disetujui, palang gerbang dibuka.";
                    AccessLog::create([
                        'device_id' => $device->id,
                        'scan_uid' => $ticketCode,
                        'access_method' => 'ticket',
                        'status' => 'granted',
                        'notes' => $notes,
                        'created_at' => now(),
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'action' => 'OPEN',
                        'gate_target' => 'OUT',
                        'duration_ms' => 5000,
                        'beep_count' => 2,
                        'led' => 'GREEN',
                        'flow' => 'TAP_OUT_TICKET_COMPLETED',
                        'message' => "Karcis {$ticketCode} sudah lunas! Akses keluar diizinkan, palang gerbang dibuka.",
                        'data' => [
                            'transaction_id' => $completedTx->id,
                            'ticket_code' => $ticketCode,
                            'user_name' => 'Pengendara Umum (Karcis Lunas)',
                            'status' => 'COMPLETED',
                            'total_fee' => (int) $completedTx->total_fee,
                            'total_fee_formatted' => 'Rp ' . number_format($completedTx->total_fee, 0, ',', '.'),
                            'device_code' => $device->device_code,
                            'gate_location' => $device->location,
                            'has_exit_receipt' => false,
                            'printed_direct' => false
                        ]
                    ], 200);
                }
            }

            $errorMsg = $completedTx 
                ? "Tiket '{$ticketCode}' sudah pernah diselesaikan lebih dari 30 menit yang lalu! Silakan hubungi operator parkir."
                : "Kode Tiket '{$ticketCode}' tidak ditemukan dalam sistem parkir!";

            AccessLog::create([
                'device_id' => $device->id,
                'scan_uid' => $ticketCode,
                'access_method' => 'ticket',
                'status' => 'denied',
                'notes' => $errorMsg,
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'error',
                'action' => 'DENIED',
                'beep_count' => 3,
                'led' => 'RED',
                'message' => "Akses Keluar Ditolak: {$errorMsg}",
                'scan_uid' => $ticketCode
            ], 404);
        }

        $now = Carbon::now();
        $tapInTime = $tx->tap_in_time ? Carbon::parse($tx->tap_in_time) : $now;
        $durationSeconds = max(0, $tapInTime->diffInSeconds($now));
        $durationMinutes = max(1, (int) ceil($durationSeconds / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $billedHours * self::RATE_PER_HOUR;
        $feeFormatted = 'Rp ' . number_format($totalFee, 0, ',', '.');

        // Update Transaksi Parkir
        $tx->update([
            'device_out_id' => $device->id,
            'tap_out_time' => $now,
            'duration_minutes' => $durationMinutes,
            'total_fee' => $totalFee,
            'balance_before' => 0,
            'balance_after' => 0,
            'status' => 'COMPLETED',
            'payment_method' => 'cash',
        ]);

        // Trigger manual_override pada perangkat agar jika ada hardware ESP8266 terhubung, palang fisik ikut membuka
        Device::whereIn('device_code', [$device->device_code, 'Gate-01', 'GATE_MASUK_01', 'GATE_KELUAR_01'])
            ->update(['manual_override' => 1]);

        $notes = "Tap-out Karcis Tiket #{$ticketCode} Berhasil. Biaya: {$feeFormatted} dibayar tunai. Durasi: {$durationMinutes} Mnt ({$billedHours} Jam)";
        AccessLog::create([
            'device_id' => $device->id,
            'scan_uid' => $ticketCode,
            'access_method' => 'ticket',
            'status' => 'granted',
            'notes' => $notes,
            'created_at' => $now,
        ]);

        // Untuk pengendara karcis (tanpa kartu), TIDAK mencetak struk total lama parkir / struk keluar
        $printedDirect = false;

        return response()->json([
            'status' => 'success',
            'action' => 'OPEN',
            'gate_target' => 'OUT',
            'duration_ms' => 5000,
            'beep_count' => 2,
            'led' => 'GREEN',
            'flow' => 'TAP_OUT_TICKET',
            'message' => "Karcis {$ticketCode} Selesai! Biaya: {$feeFormatted} (Tunai). Uang diterima di kas Admin. Palang dibuka.",
            'data' => [
                'transaction_id' => $tx->id,
                'ticket_code' => $ticketCode,
                'user_name' => 'Pengendara Umum (Karcis)',
                'payment_method' => 'cash',
                'tap_in_time' => $tx->tap_in_time ? $tx->tap_in_time->toDateTimeString() : '',
                'tap_out_time' => $now->toDateTimeString(),
                'duration_minutes' => $durationMinutes,
                'billed_hours' => $billedHours,
                'total_fee' => $totalFee,
                'total_fee_formatted' => $feeFormatted,
                'admin_received_fee' => $totalFee,
                'device_code' => $device->device_code,
                'gate_location' => $device->location,
                'has_exit_receipt' => false,
                'printed_direct' => false
            ]
        ], 200);
    }
}
