<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RfidCard;
use App\Models\ParkingTransaction;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientApiController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;
    public const RATE_PER_HOUR = 2000;

    /**
     * Dispatcher utama API Mobile berdasarkan parameter action
     */
    public function handle(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $action = $request->input('action', $request->query('action', 'status'));

        switch ($action) {
            case 'login':
                return $this->login($request);
            case 'register':
                return $this->register($request);
            case 'profile':
                return $this->profile($request);
            case 'slots':
                return $this->slots($request);
            case 'history':
                return $this->history($request);
            case 'update_profile':
                return $this->updateProfile($request);
            case 'topup':
                return $this->topup($request);
            case 'check_ticket':
                return $this->checkTicket($request);
            case 'pay_ticket':
                return $this->payTicket($request);
            case 'status':
            default:
                return $this->status($request);
        }
    }

    /**
     * 1. LOGIN API MOBILE (Cerdas, Auto-Hash Bcrypt & Null-Safe)
     */
    public function login(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $email = trim($request->input('email', ''));
        $password = trim($request->input('password', ''));

        if (empty($email) || empty($password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email dan password wajib diisi!'
            ], 400);
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah!'
            ], 401);
        }

        $passwordValid = false;
        try {
            $passwordValid = Hash::check($password, $user->password) || password_verify($password, $user->password);
        } catch (\Throwable $e) {
            // Fallback jika diinput manual sebagai teks biasa di phpMyAdmin
            if ($user->password === $password) {
                $passwordValid = true;
                $user->password = Hash::make($password);
                $user->save();
            }
        }

        if (!$passwordValid && $user->password === $password) {
            $passwordValid = true;
            $user->password = Hash::make($password);
            $user->save();
        }

        if (!$passwordValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah!',
                'data' => [
                    'message' => 'Email atau password salah!'
                ]
            ], 401);
        }

        $card = RfidCard::where('user_id', $user->id)->first();

        $userData = [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'role' => (string) $user->role,
        ];

        $cardData = $card ? [
            'id' => (int) $card->id,
            'uid' => (string) $card->uid,
            'license_plate' => (string) ($card->license_plate ?? '-'),
            'balance' => (int) $card->balance,
            'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
            'status' => (string) $card->status,
        ] : [
            'id' => 0,
            'uid' => '-',
            'license_plate' => '-',
            'balance' => 0,
            'balance_formatted' => 'Rp 0',
            'status' => 'inactive',
        ];

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil!',
            'token' => $token,
            // 1. Root level (Dibaca langsung oleh Flutter & Android Kotlin)
            'user' => $userData,
            'card' => $cardData,
            // 2. Data wrapper level (Untuk fleksibilitas REST Client)
            'data' => [
                'token' => $token,
                'user' => $userData,
                'card' => $cardData,
            ],
        ]);
    }

    /**
     * 2. REGISTER API MOBILE (Pendaftaran Pengendara Baru)
     */
    public function register(Request $request): JsonResponse
    {
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $password = trim($request->input('password', ''));
        $phone = trim($request->input('phone', ''));
        $licensePlate = trim($request->input('license_plate', ''));

        if (empty($name) || empty($email) || empty($password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nama, email, dan password wajib diisi!'
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format email tidak valid!'
            ], 400);
        }

        if (strlen($password) < 6) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password minimal terdiri dari 6 karakter!'
            ], 400);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email ini sudah terdaftar di sistem!'
            ], 409);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => !empty($phone) ? $phone : null,
            'role' => 'client',
            'password' => Hash::make($password),
        ]);

        // Buat kartu RFID virtual untuk user baru jika plat diisi
        $virtualUid = strtoupper(Str::random(10));
        $card = RfidCard::create([
            'user_id' => $user->id,
            'uid' => $virtualUid,
            'license_plate' => !empty($licensePlate) ? $licensePlate : null,
            'balance' => 50000,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $userData = [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'role' => (string) $user->role,
        ];

        $cardData = [
            'id' => (int) $card->id,
            'uid' => (string) $card->uid,
            'license_plate' => (string) ($card->license_plate ?? '-'),
            'balance' => (int) $card->balance,
            'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
            'status' => (string) $card->status,
        ];

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil! Akun dan kartu telah siap digunakan.',
            'token' => $token,
            'user' => $userData,
            'card' => $cardData,
            'data' => [
                'token' => $token,
                'user' => $userData,
                'card' => $cardData,
            ]
        ], 201);
    }

    /**
     * 3. STATUS & DASHBOARD API MOBILE
     */
    public function status(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }

        $uid = trim($request->input('uid', $request->query('uid', '')));

        $user = null;
        if ($userId > 0) {
            $user = User::find($userId);
        }
        if (!$user && !empty($uid)) {
            $card = RfidCard::where('uid', $uid)->first();
            if ($card) {
                $user = $card->user;
            }
        }
        if (!$user) {
            $user = User::where('role', 'client')->orderBy('id', 'desc')->first();
        }

        $card = $user ? RfidCard::where('user_id', $user->id)->first() : null;

        // Cek transaksi parkir aktif
        $activeTx = null;
        $currentFee = 0;
        $durationSeconds = 0;
        $durationMinutes = 0;
        $billedHours = 0;

        if ($user || $card) {
            $activeTx = ParkingTransaction::where(function ($q) use ($user, $card) {
                    if ($user) $q->where('user_id', $user->id);
                    if ($card) $q->orWhere('card_id', $card->id);
                })
                ->where('status', 'PARKED')
                ->with(['deviceIn'])
                ->orderBy('id', 'desc')
                ->first();

            if ($activeTx && $activeTx->tap_in_time) {
                $now = Carbon::now();
                $tapIn = Carbon::parse($activeTx->tap_in_time);
                $durationSeconds = max(0, $tapIn->diffInSeconds($now));
                $durationMinutes = max(1, (int) ceil($durationSeconds / 60));
                $billedHours = max(1, (int) ceil($durationMinutes / 60));
                $currentFee = $billedHours * self::RATE_PER_HOUR;
            }
        }

        // 5 riwayat terakhir
        $history = [];
        if ($user || $card) {
            $history = ParkingTransaction::where(function ($q) use ($user, $card) {
                    if ($user) $q->where('user_id', $user->id);
                    if ($card) $q->orWhere('card_id', $card->id);
                })
                ->where('status', 'COMPLETED')
                ->with(['deviceIn', 'deviceOut'])
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'ticket_code' => $t->ticket_code,
                        'tap_in_time' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->toDateTimeString() : null,
                        'tap_out_time' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->toDateTimeString() : null,
                        'duration_minutes' => (int) $t->duration_minutes,
                        'total_fee' => (int) $t->total_fee,
                        'total_fee_formatted' => 'Rp ' . number_format($t->total_fee ?? 0, 0, ',', '.'),
                        'balance_before' => $t->balance_before,
                        'balance_after' => $t->balance_after,
                        'payment_method' => $t->payment_method ?? 'card',
                        'status' => $t->status,
                        'gate_in_code' => $t->deviceIn ? $t->deviceIn->device_code : 'Gate In',
                        'gate_out_code' => $t->deviceOut ? $t->deviceOut->device_code : 'Gate Out',
                    ];
                });
        }

        // Stats
        $totalTrips = 0;
        $totalSpent = 0;
        $totalMinutes = 0;
        if ($user) {
            $completedTrips = ParkingTransaction::where(function ($q) use ($user, $card) {
                $q->where('user_id', $user->id);
                if ($card) $q->orWhere('card_id', $card->id);
            })->where('status', 'COMPLETED');

            $totalTrips = (clone $completedTrips)->count();
            $totalSpent = (int) (clone $completedTrips)->sum('total_fee');
            $totalMinutes = (int) (clone $completedTrips)->sum('duration_minutes');
        }

        // Okupansi Slot Area Parkir (Real-Time per lantai & total)
        $floor1Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $floor2Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $totalParkedNow = $floor1Parked + $floor2Parked;
        $availableSlotsNow = max(0, self::TOTAL_CAPACITY - $totalParkedNow);

        $floor1Available = max(0, self::CAPACITY_PER_FLOOR - $floor1Parked);
        $floor2Available = max(0, self::CAPACITY_PER_FLOOR - $floor2Parked);

        $isParked = $activeTx !== null;
        $statusCode = $isParked ? 'PARKED' : 'IDLE';
        $statusLabel = $isParked ? 'Sedang Parkir' : 'Tidak Ada Kendaraan di Parkiran';

        $userData = $user ? [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'role' => (string) $user->role,
        ] : null;

        $cardData = $card ? [
            'id' => (int) $card->id,
            'uid' => (string) $card->uid,
            'license_plate' => (string) ($card->license_plate ?? '-'),
            'balance' => (int) $card->balance,
            'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
            'status' => (string) $card->status,
        ] : [
            'id' => 0,
            'uid' => '-',
            'license_plate' => '-',
            'balance' => 0,
            'balance_formatted' => 'Rp 0',
            'status' => 'inactive',
        ];

        $activeTxData = $activeTx ? [
            'id' => (int) $activeTx->id,
            'ticket_code' => (string) ($activeTx->ticket_code ?? ''),
            'parking_floor' => (int) ($activeTx->parking_floor ?? 1),
            'parking_floor_label' => 'Lantai ' . ($activeTx->parking_floor ?? 1) . ' (LT ' . ($activeTx->parking_floor ?? 1) . ')',
            'tap_in_time' => $activeTx->tap_in_time ? Carbon::parse($activeTx->tap_in_time)->toDateTimeString() : null,
            'elapsed_seconds' => $durationSeconds,
            'elapsed_minutes' => $durationMinutes,
            'elapsed_formatted' => sprintf('%02d:%02d:%02d', floor($durationSeconds / 3600), floor(($durationSeconds % 3600) / 60), $durationSeconds % 60),
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
            'billed_hours' => $billedHours,
            'rate_per_hour' => self::RATE_PER_HOUR,
            'estimated_fee' => $currentFee,
            'estimated_fee_formatted' => 'Rp ' . number_format($currentFee, 0, ',', '.'),
            'gate_in_code' => $activeTx->deviceIn ? $activeTx->deviceIn->device_code : 'Gate In',
            'gate_in_name' => $activeTx->deviceIn ? ($activeTx->deviceIn->device_code . ' (' . $activeTx->deviceIn->location . ')') : 'Gate-01 (Pintu Masuk)',
            'gate_in_location' => $activeTx->deviceIn ? $activeTx->deviceIn->location : 'Area Parkir Utama',
            'is_balance_sufficient' => $card ? ($card->balance >= $currentFee) : false,
            'remaining_balance_after' => $card ? max(0, $card->balance - $currentFee) : 0,
        ] : null;

        $statsData = [
            'total_trips' => $totalTrips,
            'total_spent' => $totalSpent,
            'total_spent_formatted' => 'Rp ' . number_format($totalSpent, 0, ',', '.'),
            'total_minutes' => $totalMinutes,
        ];

        $slotData = [
            'total_capacity' => self::TOTAL_CAPACITY,
            'capacity' => self::TOTAL_CAPACITY,
            'parked_count' => $totalParkedNow,
            'available_slots' => $availableSlotsNow,
            'occupancy_percentage' => round(($totalParkedNow / self::TOTAL_CAPACITY) * 100),
            'occupancy_rate_percent' => round(($totalParkedNow / self::TOTAL_CAPACITY) * 100),
            'rate_per_hour' => self::RATE_PER_HOUR,
            'is_full' => $availableSlotsNow <= 0,
            // Rincian Lantai 1 & Lantai 2
            'floors' => [
                'lt1' => [
                    'floor_number' => 1,
                    'floor_code' => 'LT 1',
                    'capacity' => self::CAPACITY_PER_FLOOR,
                    'parked' => $floor1Parked,
                    'available' => $floor1Available,
                    'is_full' => $floor1Available <= 0,
                ],
                'lt2' => [
                    'floor_number' => 2,
                    'floor_code' => 'LT 2',
                    'capacity' => self::CAPACITY_PER_FLOOR,
                    'parked' => $floor2Parked,
                    'available' => $floor2Available,
                    'is_full' => $floor2Available <= 0,
                ],
            ],
            'floor_1' => [
                'capacity' => self::CAPACITY_PER_FLOOR,
                'parked' => $floor1Parked,
                'available' => $floor1Available,
                'is_full' => $floor1Available <= 0,
            ],
            'floor_2' => [
                'capacity' => self::CAPACITY_PER_FLOOR,
                'parked' => $floor2Parked,
                'available' => $floor2Available,
                'is_full' => $floor2Available <= 0,
            ],
            'lt1_available' => $floor1Available,
            'lt1_parked' => $floor1Parked,
            'lt1_capacity' => self::CAPACITY_PER_FLOOR,
            'lt2_available' => $floor2Available,
            'lt2_parked' => $floor2Parked,
            'lt2_capacity' => self::CAPACITY_PER_FLOOR,
        ];

        return response()->json([
            'status' => 'success',
            'server_time' => now()->toDateTimeString(),
            'is_parked' => $isParked,
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'user' => $userData,
            'card' => $cardData,
            'active_transaction' => $activeTxData,
            'history' => $history,
            'stats' => $statsData,
            'parking_slots' => $slotData,
            'parking_area' => $slotData,
            // Wrapper data untuk fleksibilitas klien
            'data' => [
                'is_parked' => $isParked,
                'status_code' => $statusCode,
                'status_label' => $statusLabel,
                'user' => $userData,
                'card' => $cardData,
                'active_transaction' => $activeTxData,
                'history' => $history,
                'stats' => $statsData,
                'slots' => $slotData,
            ]
        ]);
    }

    /**
     * 4. RIWAYAT TRANSAKSI PARKIR (HISTORY)
     */
    public function history(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }

        $card = null;
        if ($userId > 0) {
            $card = RfidCard::where('user_id', $userId)->first();
        }

        $query = ParkingTransaction::where('status', 'COMPLETED')
            ->with(['deviceIn', 'deviceOut'])
            ->orderBy('id', 'desc');

        if ($userId > 0) {
            $query->where(function ($q) use ($userId, $card) {
                $q->where('user_id', $userId);
                if ($card) {
                    $q->orWhere('card_id', $card->id);
                }
            });
        }

        $history = $query->limit(30)->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'ticket_code' => $t->ticket_code,
                'tap_in_time' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->toDateTimeString() : null,
                'tap_out_time' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->toDateTimeString() : null,
                'tap_in_formatted' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-',
                'tap_out_formatted' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->format('d/m/Y H:i') : '-',
                'duration_minutes' => (int) $t->duration_minutes,
                'duration_formatted' => $t->duration_minutes ? $t->duration_minutes . ' menit' : '-',
                'total_fee' => (int) $t->total_fee,
                'total_fee_formatted' => 'Rp ' . number_format($t->total_fee ?? 0, 0, ',', '.'),
                'balance_before' => $t->balance_before,
                'balance_after' => $t->balance_after,
                'status' => $t->status,
                'payment_method' => $t->payment_method ?? 'card',
                'gate_in_code' => $t->deviceIn ? $t->deviceIn->device_code : 'Gate In',
                'gate_out_code' => $t->deviceOut ? $t->deviceOut->device_code : 'Gate Out',
            ];
        });

        return response()->json([
            'status' => 'success',
            'history' => $history,
            'data' => $history
        ]);
    }

    /**
     * 5. TOP UP SALDO KARTU VIA MOBILE
     */
    public function topup(Request $request): JsonResponse
    {
        $amount = (int) $request->input('amount', $request->query('amount', 0));
        if ($amount < 1000) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nominal top up minimal Rp 1.000'
            ], 400);
        }

        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }
        $uid = trim($request->input('uid', $request->query('uid', '')));

        $targetCard = null;
        if ($userId > 0) {
            $targetCard = RfidCard::where('user_id', $userId)->first();
        }
        if (!$targetCard && !empty($uid)) {
            $targetCard = RfidCard::where('uid', $uid)->first();
        }

        if (!$targetCard) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kartu RFID pengendara tidak ditemukan!'
            ], 404);
        }

        $oldBal = (int) $targetCard->balance;
        $newBal = $oldBal + $amount;

        $targetCard->update(['balance' => $newBal]);

        return response()->json([
            'status' => 'success',
            'message' => 'Top up saldo sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil!',
            'card_id' => $targetCard->id,
            'uid' => $targetCard->uid,
            'amount_added' => $amount,
            'balance_before' => $oldBal,
            'balance_after' => $newBal,
            'balance_formatted' => 'Rp ' . number_format($newBal, 0, ',', '.'),
        ]);
    }

    /**
     * 6. PROFIL PENGGUNA
     */
    public function profile(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }

        $user = User::with('rfidCard')->find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $userData = [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'role' => (string) $user->role,
        ];

        $cardData = $user->rfidCard ? [
            'id' => (int) $user->rfidCard->id,
            'uid' => (string) $user->rfidCard->uid,
            'license_plate' => (string) ($user->rfidCard->license_plate ?? '-'),
            'balance' => (int) $user->rfidCard->balance,
            'balance_formatted' => 'Rp ' . number_format($user->rfidCard->balance, 0, ',', '.'),
            'status' => (string) $user->rfidCard->status,
        ] : [
            'id' => 0,
            'uid' => '-',
            'license_plate' => '-',
            'balance' => 0,
            'balance_formatted' => 'Rp 0',
            'status' => 'inactive',
        ];

        return response()->json([
            'status' => 'success',
            'user' => $userData,
            'card' => $cardData,
            'data' => [
                'user' => $userData,
                'card' => $cardData,
            ]
        ]);
    }

    /**
     * 7. STATUS KETERSEDIAAN SLOT PARKIR
     */
    public function slots(Request $request): JsonResponse
    {
        $floor1Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $floor2Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $totalParked = $floor1Parked + $floor2Parked;
        $available = max(0, self::TOTAL_CAPACITY - $totalParked);
        $percent = round(($totalParked / self::TOTAL_CAPACITY) * 100);

        $floor1Available = max(0, self::CAPACITY_PER_FLOOR - $floor1Parked);
        $floor2Available = max(0, self::CAPACITY_PER_FLOOR - $floor2Parked);

        return response()->json([
            'status' => 'success',
            'capacity' => self::TOTAL_CAPACITY,
            'total_capacity' => self::TOTAL_CAPACITY,
            'parked_count' => $totalParked,
            'available_slots' => $available,
            'occupancy_percentage' => $percent,
            'occupancy_rate_percent' => $percent,
            'is_full' => $available <= 0,
            'rate_per_hour' => self::RATE_PER_HOUR,
            'floors' => [
                'lt1' => [
                    'floor_number' => 1,
                    'floor_code' => 'LT 1',
                    'capacity' => self::CAPACITY_PER_FLOOR,
                    'parked' => $floor1Parked,
                    'available' => $floor1Available,
                    'is_full' => $floor1Available <= 0,
                ],
                'lt2' => [
                    'floor_number' => 2,
                    'floor_code' => 'LT 2',
                    'capacity' => self::CAPACITY_PER_FLOOR,
                    'parked' => $floor2Parked,
                    'available' => $floor2Available,
                    'is_full' => $floor2Available <= 0,
                ],
            ],
            'floor_1' => [
                'capacity' => self::CAPACITY_PER_FLOOR,
                'parked' => $floor1Parked,
                'available' => $floor1Available,
                'is_full' => $floor1Available <= 0,
            ],
            'floor_2' => [
                'capacity' => self::CAPACITY_PER_FLOOR,
                'parked' => $floor2Parked,
                'available' => $floor2Available,
                'is_full' => $floor2Available <= 0,
            ],
            'lt1_available' => $floor1Available,
            'lt1_parked' => $floor1Parked,
            'lt1_capacity' => self::CAPACITY_PER_FLOOR,
            'lt2_available' => $floor2Available,
            'lt2_parked' => $floor2Parked,
            'lt2_capacity' => self::CAPACITY_PER_FLOOR,
        ]);
    }

    /**
     * 8. UPDATE PROFIL PENGENDARA
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($request->has('name')) {
            $user->name = trim($request->input('name'));
        }
        if ($request->has('phone')) {
            $user->phone = trim($request->input('phone'));
        }
        $user->save();

        if ($request->has('license_plate') && $user->rfidCard) {
            $user->rfidCard->update(['license_plate' => trim($request->input('license_plate'))]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'role' => $user->role,
                'license_plate' => $user->rfidCard ? ($user->rfidCard->license_plate ?? '-') : '-',
            ]
        ]);
    }

    /**
     * 9. CEK INFORMASI TIKET KARCIS (Sebelum bayar pakai saldo)
     */
    public function checkTicket(Request $request): JsonResponse
    {
        $ticketCode = trim($request->input('ticket_code', $request->query('ticket_code', '')));
        if (empty($ticketCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tiket wajib diisi!'
            ], 400);
        }

        $tx = ParkingTransaction::where('ticket_code', $ticketCode)
            ->where('status', 'PARKED')
            ->orderBy('id', 'desc')
            ->first();

        if (!$tx) {
            $completed = ParkingTransaction::where('ticket_code', $ticketCode)
                ->where('status', 'COMPLETED')
                ->first();

            return response()->json([
                'status' => 'error',
                'message' => $completed ? "Tiket {$ticketCode} sudah pernah dibayar/selesai!" : "Tiket {$ticketCode} tidak ditemukan!",
            ], 404);
        }

        $now = Carbon::now();
        $inTime = $tx->tap_in_time ? Carbon::parse($tx->tap_in_time) : $now;
        $durationSeconds = max(0, $inTime->diffInSeconds($now));
        $durationMinutes = max(1, (int) ceil($durationSeconds / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $billedHours * self::RATE_PER_HOUR;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $tx->id,
                'ticket_code' => $tx->ticket_code,
                'parking_floor' => $tx->parking_floor ?? 1,
                'parking_floor_label' => 'Lantai ' . ($tx->parking_floor ?? 1),
                'tap_in_time' => $inTime->format('d/m/Y H:i'),
                'duration_minutes' => $durationMinutes,
                'billed_hours' => $billedHours,
                'rate_per_hour' => self::RATE_PER_HOUR,
                'total_fee' => $totalFee,
                'total_fee_formatted' => 'Rp ' . number_format($totalFee, 0, ',', '.'),
            ]
        ]);
    }

    /**
     * 10. BAYAR KARCIS TIKET MENGGUNAKAN SALDO KARTU MEMBER RFID
     */
    public function payTicket(Request $request): JsonResponse
    {
        $ticketCode = trim($request->input('ticket_code', ''));
        $userId = (int) $request->input('user_id', 0);

        if ($userId <= 0 && Auth::check()) {
            $userId = Auth::id();
        }

        if (empty($ticketCode)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode tiket tidak boleh kosong!'
            ], 400);
        }

        $user = User::with('rfidCard')->find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pengguna tidak ditemukan!'
            ], 404);
        }

        $card = $user->rfidCard;
        if (!$card || $card->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pembayaran via saldo hanya dapat dilakukan oleh Member E-Parkir dengan kartu RFID aktif!'
            ], 400);
        }

        $tx = ParkingTransaction::where('ticket_code', $ticketCode)
            ->where('status', 'PARKED')
            ->orderBy('id', 'desc')
            ->first();

        if (!$tx) {
            return response()->json([
                'status' => 'error',
                'message' => "Tiket {$ticketCode} tidak ditemukan atau sudah dibayar sebelumnya!"
            ], 404);
        }

        $now = Carbon::now();
        $inTime = $tx->tap_in_time ? Carbon::parse($tx->tap_in_time) : $now;
        $durationSeconds = max(0, $inTime->diffInSeconds($now));
        $durationMinutes = max(1, (int) ceil($durationSeconds / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $billedHours * self::RATE_PER_HOUR;

        if ($card->balance < $totalFee) {
            $kurang = $totalFee - $card->balance;
            return response()->json([
                'status' => 'error',
                'message' => "Saldo kartu Anda tidak mencukupi! Saldo saat ini: Rp " . number_format($card->balance, 0, ',', '.') . ", Biaya: Rp " . number_format($totalFee, 0, ',', '.') . " (Kurang Rp " . number_format($kurang, 0, ',', '.') . "). Silakan isi saldo terlebih dahulu."
            ], 400);
        }

        $balanceBefore = $card->balance;
        $balanceAfter = $balanceBefore - $totalFee;

        // Potong Saldo Kartu RFID Member
        $card->balance = $balanceAfter;
        $card->save();

        // Update Transaksi Tiket Parkir menjadi Lunas / COMPLETED
        $tx->update([
            'user_id' => $user->id,
            'card_id' => $card->id,
            'tap_out_time' => $now,
            'duration_minutes' => $durationMinutes,
            'total_fee' => $totalFee,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'payment_method' => 'card',
            'status' => 'COMPLETED',
        ]);

        // Aktifkan juga manual_override agar palang gerbang ESP8266 langsung membuka jika kendaraan sudah di pos keluar
        \App\Models\Device::whereIn('device_code', ['Gate-01', 'GATE_MASUK_01', 'GATE_KELUAR_01'])
            ->update(['manual_override' => 1]);

        \App\Models\AccessLog::create([
            'device_id' => $tx->device_in_id,
            'scan_uid' => $ticketCode,
            'access_method' => 'mobile_app',
            'status' => 'granted',
            'notes' => "Karcis Tiket #{$ticketCode} dibayar menggunakan saldo kartu RFID {$card->uid} oleh Member '{$user->name}'. Terpotong Rp " . number_format($totalFee, 0, ',', '.') . ", Sisa saldo: Rp " . number_format($balanceAfter, 0, ',', '.'),
            'created_at' => $now,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pembayaran karcis {$ticketCode} berhasil! Saldo terpotong Rp " . number_format($totalFee, 0, ',', '.') . ". Palang keluar siap terbuka.",
            'data' => [
                'transaction_id' => $tx->id,
                'ticket_code' => $ticketCode,
                'total_fee' => $totalFee,
                'total_fee_formatted' => 'Rp ' . number_format($totalFee, 0, ',', '.'),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'balance_after_formatted' => 'Rp ' . number_format($balanceAfter, 0, ',', '.'),
                'receipt_url' => route('receipt.show', $tx->id) . '?type=receipt'
            ]
        ]);
    }
}
