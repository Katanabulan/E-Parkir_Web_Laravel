<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RfidCard;
use App\Models\ParkingTransaction;
use App\Models\Device;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use InvalidArgumentException;

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
            case 'google':
            case 'google_login':
            case 'google_register':
            case 'google_auth':
                return $this->googleAuth($request);
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
            case 'dashboard':
            case 'owner_dashboard':
            case 'admin_dashboard':
            case 'owner_stats':
            case 'stats':
            case 'statistics':
            case 'overview':
            case 'home_owner':
                return $this->ownerDashboard($request);
            case 'owner_reports':
            case 'admin_reports':
            case 'reports':
            case 'transactions':
                return $this->ownerReports($request);
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
        $hasCard = $card !== null;
        $pendingCard = $user->role === 'client' && !$hasCard;
        $cardStatus = $hasCard ? (string) $card->status : 'pending_card';

        $userData = $this->formatUserData($user, $card);
        $cardData = $this->formatCardData($card, $user);

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil!',
            'token' => $token,
            'has_card' => $hasCard,
            'pending_card' => $pendingCard,
            'card_status' => $cardStatus,
            // 1. Root level (Dibaca langsung oleh Flutter & Android Kotlin)
            'user' => $userData,
            'card' => $cardData,
            // 2. Data wrapper level (Untuk fleksibilitas REST Client)
            'data' => [
                'token' => $token,
                'has_card' => $hasCard,
                'pending_card' => $pendingCard,
                'card_status' => $cardStatus,
                'user' => $userData,
                'card' => $cardData,
            ],
        ]);
    }

    /**
     * 2. REGISTER API MOBILE (Pendaftaran Data Diri Pengendara - Menunggu Pembuatan Kartu Member di Web Admin)
     */
    public function register(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $password = trim($request->input('password', ''));
        $phone = trim($request->input('phone', ''));
        $licensePlate = strtoupper(trim($request->input('license_plate', '')));

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

        if (User::whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email ini sudah terdaftar di sistem!'
            ], 409);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => !empty($phone) ? $phone : null,
            'license_plate' => !empty($licensePlate) ? $licensePlate : null,
            'role' => 'client',
            'password' => Hash::make($password),
        ]);

        $userData = $this->formatUserData($user);
        $cardData = $this->formatCardData(null, $user);

        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran data diri berhasil! Data Anda telah masuk ke admin untuk pembuatan kartu member RFID.',
            'token' => $token,
            'has_card' => false,
            'pending_card' => true,
            'card_status' => 'pending_card',
            'user' => $userData,
            'card' => $cardData,
            'data' => [
                'token' => $token,
                'has_card' => false,
                'pending_card' => true,
                'card_status' => 'pending_card',
                'user' => $userData,
                'card' => $cardData,
            ]
        ], 201);
    }

    /**
     * 2B. LOGIN / REGISTER VIA GOOGLE API MOBILE (id_token / access_token)
     */
    public function googleAuth(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $idToken = trim((string) ($request->input('id_token') ?? $request->input('idToken') ?? $request->input('credential') ?? ''));
        $accessToken = trim((string) ($request->input('access_token') ?? $request->input('accessToken') ?? ''));

        if ($idToken === '' && $accessToken === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Token Google (id_token atau access_token) wajib dikirim!'
            ], 400);
        }

        try {
            $googleUser = app(GoogleAuthService::class)->verifyMobileToken($idToken, $accessToken);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memverifikasi akun Google. Silakan coba lagi.',
            ], 401);
        }

        $phone = trim((string) $request->input('phone', ''));
        $licensePlate = strtoupper(trim((string) $request->input('license_plate', '')));

        $user = User::where('google_id', $googleUser['google_id'])
            ->orWhereRaw('LOWER(email) = ?', [$googleUser['email']])
            ->first();

        // Validasi Mode Google:
        // - 'login': HANYA untuk akun yang sudah terdaftar di sistem
        // - 'register': HANYA untuk akun baru yang belum terdaftar di sistem
        // - Di luar kedua mode tersebut tidak diperbolehkan
        $mode = strtolower(trim((string) ($request->input('mode') ?? '')));
        if ($mode === '') {
            $action = strtolower(trim((string) ($request->input('action') ?? '')));
            if ($action === 'google_login' || $action === 'login') {
                $mode = 'login';
            } elseif ($action === 'google_register' || $action === 'register') {
                $mode = 'register';
            } elseif ($request->is('*google_login*') || $request->is('*client/login*')) {
                $mode = 'login';
            } elseif ($request->is('*google_register*') || $request->is('*client/register*')) {
                $mode = 'register';
            } elseif ($request->has('phone') || $request->has('license_plate')) {
                $mode = 'register';
            }
        }

        if ($mode !== 'login' && $mode !== 'register') {
            return response()->json([
                'status' => 'error',
                'code' => 'INVALID_AUTH_MODE',
                'message' => 'Mode autentikasi Google tidak valid. Hanya diperbolehkan mode "login" (untuk akun yang sudah terdaftar) atau "register" (untuk akun baru yang belum terdaftar).'
            ], 422);
        }

        if ($mode === 'login') {
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'Akun Google ini belum terdaftar. Fitur Masuk hanya untuk akun yang sudah punya akun. Silakan lakukan pendaftaran terlebih dahulu melalui menu Daftar.'
                ], 404);
            }
        } elseif ($mode === 'register') {
            if ($user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'ACCOUNT_ALREADY_EXISTS',
                    'message' => 'Akun Google ini sudah terdaftar. Fitur Daftar hanya untuk yang belum punya akun. Silakan masuk melalui menu Masuk.'
                ], 409);
            }
        }

        $isNewUser = false;

        if ($user) {
            $updates = [];
            if (empty($user->google_id)) {
                $updates['google_id'] = $googleUser['google_id'];
            }
            if (empty($user->avatar) && !empty($googleUser['avatar'])) {
                $updates['avatar'] = $googleUser['avatar'];
            }
            if (empty($user->phone) && $phone !== '') {
                $updates['phone'] = $phone;
            }
            if (empty($user->license_plate) && $licensePlate !== '') {
                $updates['license_plate'] = $licensePlate;
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        } else {
            $isNewUser = true;
            $user = User::create([
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'google_id' => $googleUser['google_id'],
                'auth_provider' => 'google',
                'avatar' => $googleUser['avatar'],
                'phone' => $phone !== '' ? $phone : null,
                'license_plate' => $licensePlate !== '' ? $licensePlate : null,
                'role' => 'client',
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $card = RfidCard::where('user_id', $user->id)->first();
        $message = $isNewUser
            ? 'Pendaftaran dengan Google berhasil! Data Anda telah masuk ke admin untuk pembuatan kartu member RFID.'
            : 'Login dengan Google berhasil!';

        return $this->buildAuthResponse($user, $card, $message, $isNewUser ? 201 : 200, [
            'is_new_user' => $isNewUser,
            'auth_provider' => 'google',
        ]);
    }

    private function buildAuthResponse(User $user, ?RfidCard $card, string $message, int $statusCode = 200, array $extra = []): JsonResponse
    {
        $hasCard = $card !== null;
        $pendingCard = $user->role === 'client' && !$hasCard;
        $cardStatus = $hasCard ? (string) $card->status : 'pending_card';

        $userData = $this->formatUserData($user, $card);
        $cardData = $this->formatCardData($card, $user);
        $token = base64_encode($user->id . '|' . $user->email . '|' . time());

        $payload = array_merge([
            'status' => 'success',
            'message' => $message,
            'token' => $token,
            'has_card' => $hasCard,
            'pending_card' => $pendingCard,
            'card_status' => $cardStatus,
            'user' => $userData,
            'card' => $cardData,
        ], $extra);

        $payload['data'] = array_merge([
            'token' => $token,
            'has_card' => $hasCard,
            'pending_card' => $pendingCard,
            'card_status' => $cardStatus,
            'user' => $userData,
            'card' => $cardData,
        ], $extra);

        return response()->json($payload, $statusCode);
    }

    private function formatUserData(User $user, ?RfidCard $card = null): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'license_plate' => (string) ($card?->license_plate ?? $user->license_plate ?? '-'),
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'auth_provider' => (string) ($user->auth_provider ?? 'local'),
            'can_change_name' => $user->can_change_name,
            'name_changes_count' => (int) ($user->name_changes_count ?? 0),
            'name_changed_at' => $user->name_changed_at ? $user->name_changed_at->format('Y-m-d H:i:s') : null,
            'role' => (string) $user->role,
        ];
    }

    private function formatCardData(?RfidCard $card, ?User $user = null): array
    {
        if ($card) {
            return [
                'id' => (int) $card->id,
                'uid' => (string) $card->uid,
                'license_plate' => (string) ($card->license_plate ?? ($user?->license_plate ?? '-')),
                'balance' => (int) $card->balance,
                'balance_formatted' => 'Rp ' . number_format($card->balance, 0, ',', '.'),
                'status' => (string) $card->status,
            ];
        }

        return [
            'id' => 0,
            'uid' => '-',
            'license_plate' => (string) ($user?->license_plate ?? '-'),
            'balance' => 0,
            'balance_formatted' => 'Rp 0',
            'status' => 'inactive',
        ];
    }

    /**
     * 3. STATUS & DASHBOARD API MOBILE
     */
    public function status(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && $request->bearerToken()) {
            $decoded = base64_decode($request->bearerToken(), true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $userId = (int) $parts[0];
            }
        }
        if ($userId <= 0 && Auth::check()) {
            $userId = (int) Auth::id();
        }

        $uid = trim($request->input('uid', $request->query('uid', '')));

        $user = null;
        if ($userId > 0) {
            $user = User::find($userId);
            if (!$user && empty($uid)) {
                return response()->json([
                    'status' => 'error',
                    'account_deleted' => true,
                    'message' => 'Akun pendaftaran tidak ditemukan atau telah dihapus oleh admin.'
                ], 404);
            }
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
                    $isTicket = !empty($t->ticket_code);
                    return [
                        'id' => (int) $t->id,
                        'transaction_id' => (int) $t->id,
                        'ticket_code' => $t->ticket_code,
                        'is_ticket' => (bool) $isTicket,
                        'access_type' => $isTicket ? 'KARCIS' : 'RFID',
                        'access_method' => $isTicket ? 'ticket' : 'rfid',
                        'method' => $isTicket ? 'karcis' : 'rfid',
                        'metode' => $isTicket ? 'Karcis' : 'RFID',
                        'metode_transaksi' => $isTicket ? 'Karcis Parkir' : 'Kartu RFID',
                        'metode_akses' => $isTicket ? 'Karcis' : 'RFID',
                        'metode_pembayaran' => $isTicket ? 'Tunai di Kasir' : 'Potong Saldo Kartu RFID',
                        'tap_in_time' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->toDateTimeString() : null,
                        'tap_out_time' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->toDateTimeString() : null,
                        'duration_minutes' => (int) $t->duration_minutes,
                        'total_fee' => (int) $t->total_fee,
                        'total_fee_formatted' => 'Rp ' . number_format($t->total_fee ?? 0, 0, ',', '.'),
                        'balance_before' => $t->balance_before,
                        'balance_after' => $t->balance_after,
                        'payment_method' => $isTicket ? 'cash' : ($t->payment_method ?? 'card'),
                        'payment_method_label' => $isTicket ? 'Tunai di Kasir (Karcis)' : 'Potong Saldo Kartu RFID',
                        'payment_type' => $isTicket ? 'karcis' : 'rfid',
                        'type' => $isTicket ? 'karcis' : 'rfid',
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

        // Finansial & Operasional Global (Owner / Admin)
        $totalRevenueAll = (int) ParkingTransaction::where('status', 'COMPLETED')->sum('total_fee');
        $todayRevenueAll = (int) ParkingTransaction::where('status', 'COMPLETED')
            ->whereDate('tap_in_time', Carbon::today()->toDateString())
            ->sum('total_fee');
        $totalTransactionsAll = ParkingTransaction::count();
        $completedTransactionsAll = ParkingTransaction::where('status', 'COMPLETED')->count();

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

        $hasCard = $card !== null;
        $pendingCard = $user ? ($user->role === 'client' && !$hasCard) : false;
        $cardStatus = $hasCard ? (string) $card->status : 'pending_card';

        $userData = $user ? $this->formatUserData($user, $card) : null;
        $cardData = $this->formatCardData($card, $user);

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
            'total_revenue' => $totalRevenueAll,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenueAll, 0, ',', '.'),
            'total_income' => $totalRevenueAll,
            'total_income_formatted' => 'Rp ' . number_format($totalRevenueAll, 0, ',', '.'),
            'today_revenue' => $todayRevenueAll,
            'today_revenue_formatted' => 'Rp ' . number_format($todayRevenueAll, 0, ',', '.'),
            'today_income' => $todayRevenueAll,
            'today_income_formatted' => 'Rp ' . number_format($todayRevenueAll, 0, ',', '.'),
            'total_transactions' => $totalTransactionsAll,
            'completed_transactions' => $completedTransactionsAll,
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
            'total_revenue' => $totalRevenueAll,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenueAll, 0, ',', '.'),
            'total_income' => $totalRevenueAll,
            'total_income_formatted' => 'Rp ' . number_format($totalRevenueAll, 0, ',', '.'),
            'today_revenue' => $todayRevenueAll,
            'today_revenue_formatted' => 'Rp ' . number_format($todayRevenueAll, 0, ',', '.'),
            'today_income' => $todayRevenueAll,
            'today_income_formatted' => 'Rp ' . number_format($todayRevenueAll, 0, ',', '.'),
            'total_transactions' => $totalTransactionsAll,
            'is_parked' => $isParked,
            'status_code' => $statusCode,
            'status_label' => $statusLabel,
            'has_card' => $hasCard,
            'pending_card' => $pendingCard,
            'card_status' => $cardStatus,
            'user' => $userData,
            'card' => $cardData,
            'active_transaction' => $activeTxData,
            'history' => $history,
            'stats' => $statsData,
            'parking_slots' => $slotData,
            'parking_area' => $slotData,
            'floors' => $slotData['floors'],
            // Wrapper data untuk fleksibilitas klien
            'data' => [
                'total_revenue' => $totalRevenueAll,
                'total_revenue_formatted' => 'Rp ' . number_format($totalRevenueAll, 0, ',', '.'),
                'total_income' => $totalRevenueAll,
                'today_revenue' => $todayRevenueAll,
                'today_revenue_formatted' => 'Rp ' . number_format($todayRevenueAll, 0, ',', '.'),
                'today_income' => $todayRevenueAll,
                'total_transactions' => $totalTransactionsAll,
                'is_parked' => $isParked,
                'status_code' => $statusCode,
                'status_label' => $statusLabel,
                'has_card' => $hasCard,
                'pending_card' => $pendingCard,
                'card_status' => $cardStatus,
                'user' => $userData,
                'card' => $cardData,
                'active_transaction' => $activeTxData,
                'history' => $history,
                'stats' => $statsData,
                'slots' => $slotData,
                'floors' => $slotData['floors'],
            ]
        ]);
    }

    /**
     * 4. RIWAYAT TRANSAKSI PARKIR (HISTORY)
     */
    public function history(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        if ($userId <= 0 && $request->bearerToken()) {
            $decoded = base64_decode($request->bearerToken(), true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $userId = (int) $parts[0];
            }
        }
        if ($userId <= 0 && Auth::check()) {
            $userId = (int) Auth::id();
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
            $isTicket = !empty($t->ticket_code);
            return [
                'id' => (int) $t->id,
                'transaction_id' => (int) $t->id,
                'ticket_code' => $t->ticket_code,
                'is_ticket' => (bool) $isTicket,
                'access_type' => $isTicket ? 'KARCIS' : 'RFID',
                'access_method' => $isTicket ? 'ticket' : 'rfid',
                'method' => $isTicket ? 'karcis' : 'rfid',
                'metode' => $isTicket ? 'Karcis' : 'RFID',
                'metode_transaksi' => $isTicket ? 'Karcis Parkir' : 'Kartu RFID',
                'metode_akses' => $isTicket ? 'Karcis' : 'RFID',
                'metode_pembayaran' => $isTicket ? 'Tunai di Kasir' : 'Potong Saldo Kartu RFID',
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
                'payment_method' => $isTicket ? 'cash' : ($t->payment_method ?? 'card'),
                'payment_method_label' => $isTicket ? 'Tunai di Kasir (Karcis)' : 'Potong Saldo Kartu RFID',
                'payment_type' => $isTicket ? 'karcis' : 'rfid',
                'type' => $isTicket ? 'karcis' : 'rfid',
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
        if ($userId <= 0 && $request->bearerToken()) {
            $decoded = base64_decode($request->bearerToken(), true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $userId = (int) $parts[0];
            }
        }
        if ($userId <= 0 && Auth::check()) {
            $userId = (int) Auth::id();
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
            $userExists = $userId > 0 && User::where('id', $userId)->exists();
            return response()->json([
                'status' => 'error',
                'message' => $userExists
                    ? 'Kartu member RFID Anda belum dibuat oleh admin. Silakan tunggu pembuatan kartu member terlebih dahulu.'
                    : 'Kartu RFID pengendara tidak ditemukan!'
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
        if ($userId <= 0 && $request->bearerToken()) {
            $decoded = base64_decode($request->bearerToken(), true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $userId = (int) $parts[0];
            }
        }
        if ($userId <= 0 && Auth::check()) {
            $userId = (int) Auth::id();
        }

        $user = User::with('rfidCard')->find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'account_deleted' => true,
                'message' => 'User tidak ditemukan atau pendaftaran telah dihapus oleh admin.'
            ], 404);
        }

        $card = $user->rfidCard;
        $hasCard = $card !== null;
        $pendingCard = $user->role === 'client' && !$hasCard;
        $cardStatus = $hasCard ? (string) $card->status : 'pending_card';

        $userData = $this->formatUserData($user, $card);
        $cardData = $this->formatCardData($card, $user);

        return response()->json([
            'status' => 'success',
            'has_card' => $hasCard,
            'pending_card' => $pendingCard,
            'card_status' => $cardStatus,
            'user' => $userData,
            'card' => $cardData,
            'data' => [
                'has_card' => $hasCard,
                'pending_card' => $pendingCard,
                'card_status' => $cardStatus,
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
     * 8. UPDATE PROFIL PENGENDARA (Foto Profil, Nama 1x Ganti, Nomor HP)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', $request->query('user_id', 0));
        
        // Coba ekstrak user ID dari Bearer token jika user_id tidak dikirim di body
        if ($userId <= 0 && $request->bearerToken()) {
            $decoded = base64_decode($request->bearerToken(), true);
            if ($decoded && str_contains($decoded, '|')) {
                $parts = explode('|', $decoded);
                $userId = (int) $parts[0];
            }
        }

        if ($userId <= 0 && Auth::check()) {
            $userId = (int) Auth::id();
        }

        if ($userId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parameter user_id atau otentikasi wajib disertakan!'
            ], 400);
        }

        $user = User::with('rfidCard')->find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'account_deleted' => true,
                'message' => 'User tidak ditemukan atau pendaftaran telah dihapus oleh admin.'
            ], 404);
        }

        // 1. ATURAN GANTI NAMA: HANYA BOLEH 1 KALI SEUMUR HIDUP
        if ($request->has('name')) {
            $newName = trim((string) $request->input('name'));
            if ($newName === '') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nama tidak boleh kosong!'
                ], 422);
            }

            if ($newName !== $user->name) {
                if ((int) ($user->name_changes_count ?? 0) >= 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Nama hanya dapat diubah 1 kali saja. Anda sudah pernah mengubah nama sebelumnya!',
                        'can_change_name' => false,
                        'name_changes_count' => (int) $user->name_changes_count,
                        'name_changed_at' => $user->name_changed_at ? $user->name_changed_at->format('Y-m-d H:i:s') : null,
                    ], 422);
                }

                $user->name = $newName;
                $user->name_changes_count = ((int) ($user->name_changes_count ?? 0)) + 1;
                $user->name_changed_at = Carbon::now();
            }
        }

        // 2. ATURAN NOMOR HP: BEBAS DIUBAH
        if ($request->has('phone')) {
            $user->phone = trim((string) $request->input('phone'));
        }

        // 3. ATURAN FOTO PROFIL: MULTIPART FILE ATAU BASE64
        $uploadedFile = $request->file('avatar') 
            ?? $request->file('photo') 
            ?? $request->file('profile_photo') 
            ?? $request->file('image');

        $destinationPath = public_path('uploads/avatars');
        if (!file_exists($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        if ($uploadedFile) {
            if (!$uploadedFile->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File foto yang diunggah tidak valid atau rusak!'
                ], 422);
            }

            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format file foto harus berupa JPG, JPEG, PNG, atau WEBP!'
                ], 422);
            }

            // Maksimal 5MB
            if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ukuran file foto maksimal adalah 5MB!'
                ], 422);
            }

            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $extension;

            // Hapus foto lama jika ada
            if (!empty($user->avatar) && file_exists($destinationPath . '/' . $user->avatar)) {
                @unlink($destinationPath . '/' . $user->avatar);
            }

            $uploadedFile->move($destinationPath, $filename);
            $user->avatar = $filename;

        } elseif ($request->filled('avatar_base64') || $request->filled('photo_base64') || (is_string($request->input('photo')) && str_starts_with($request->input('photo'), 'data:image'))) {
            // Dukungan payload Base64 untuk Mobile
            $rawBase64 = (string) ($request->input('avatar_base64') ?: $request->input('photo_base64') ?: $request->input('photo'));
            $ext = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $rawBase64, $type)) {
                $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
            }

            $decoded = base64_decode($rawBase64);
            if ($decoded !== false) {
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;
                if (!empty($user->avatar) && file_exists($destinationPath . '/' . $user->avatar)) {
                    @unlink($destinationPath . '/' . $user->avatar);
                }
                file_put_contents($destinationPath . '/' . $filename, $decoded);
                $user->avatar = $filename;
            }
        }

        // 4. PLAT NOMOR KENDARAAN (Simpan di user & kartu jika sudah dibuat)
        if ($request->has('license_plate')) {
            $plate = strtoupper(trim((string) $request->input('license_plate')));
            $user->license_plate = $plate !== '' ? $plate : null;
            if ($user->rfidCard) {
                $user->rfidCard->update([
                    'license_plate' => $user->license_plate,
                ]);
            }
        }

        $user->save();

        $userData = $this->formatUserData($user, $user->rfidCard);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui!',
            'user' => $userData,
            'data' => $userData
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
            'access_method' => 'ticket',
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

    /**
     * 8. OWNER DASHBOARD API (Statistik & Metrik Real-Time untuk Akun Owner di Mobile)
     */
    public function ownerDashboard(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateTimeString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateTimeString();
        $startOfWeek = Carbon::now()->subDays(6)->startOfDay()->toDateTimeString();
        $endOfWeek = Carbon::now()->endOfDay()->toDateTimeString();

        // 1. Finansial
        $totalRevenue = (int) ParkingTransaction::where('status', 'COMPLETED')->sum('total_fee');
        $todayRevenue = (int) ParkingTransaction::where('status', 'COMPLETED')
            ->where(function ($q) use ($today) {
                $q->whereDate('tap_out_time', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('tap_out_time')->whereDate('tap_in_time', $today);
                  });
            })
            ->sum('total_fee');

        $monthRevenue = (int) ParkingTransaction::where('status', 'COMPLETED')
            ->whereBetween('tap_in_time', [$startOfMonth, $endOfMonth])
            ->sum('total_fee');

        $weekRevenue = (int) ParkingTransaction::where('status', 'COMPLETED')
            ->whereBetween('tap_in_time', [$startOfWeek, $endOfWeek])
            ->sum('total_fee');

        $totalTransactions = (int) ParkingTransaction::count();
        $completedTransactions = (int) ParkingTransaction::where('status', 'COMPLETED')->count();
        $todayTransactions = (int) ParkingTransaction::whereDate('tap_in_time', $today)->count();

        // 2. Utilisasi Slot & Okupansi Lantai
        $parkedCount = (int) ParkingTransaction::where('status', 'PARKED')->count();
        $floor1Parked = (int) ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $floor2Parked = (int) ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $floor1Available = max(0, self::CAPACITY_PER_FLOOR - $floor1Parked);
        $floor2Available = max(0, self::CAPACITY_PER_FLOOR - $floor2Parked);
        $availableSlots = max(0, self::TOTAL_CAPACITY - $parkedCount);
        $occupancyPercent = (int) round(($parkedCount / self::TOTAL_CAPACITY) * 100);

        // 3. Rata-rata durasi & tarif transaksi selesai
        $avgMinutes = (int) round(ParkingTransaction::where('status', 'COMPLETED')->avg('duration_minutes') ?? 0);
        $avgFee = (int) round(ParkingTransaction::where('status', 'COMPLETED')->avg('total_fee') ?? 0);

        // 4. Trend 7 Hari Terakhir untuk Grafik / Chart di Mobile
        $indonesianDays = [
            0 => 'Min', 1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab'
        ];
        $indonesianDaysFull = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'
        ];

        $last7Days = [];
        $chartLabels = [];
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $dStr = $d->toDateString();
            $dRev = (int) ParkingTransaction::where('status', 'COMPLETED')
                ->where(function ($q) use ($dStr) {
                    $q->whereDate('tap_out_time', $dStr)
                      ->orWhere(function ($sub) use ($dStr) {
                          $sub->whereNull('tap_out_time')->whereDate('tap_in_time', $dStr);
                      });
                })
                ->sum('total_fee');
            $dTx = (int) ParkingTransaction::whereDate('tap_in_time', $dStr)->count();
            
            $dayShort = $indonesianDays[$d->dayOfWeek] ?? $d->format('D');
            $dayFull = $indonesianDaysFull[$d->dayOfWeek] ?? $d->format('l');

            $last7Days[] = [
                'date' => $dStr,
                'label' => $d->format('d/m'),
                'day' => $dayShort,
                'day_name' => $dayFull,
                'day_id' => $dayShort,
                'day_en' => $d->format('D'),
                'revenue' => $dRev,
                'income' => $dRev,
                'amount' => $dRev,
                'value' => $dRev,
                'total' => $dRev,
                'total_fee' => $dRev,
                'y' => $dRev,
                'x' => (int) (6 - $i),
                'transactions' => $dTx,
                'count' => $dTx,
                'tx_count' => $dTx,
            ];
            $chartLabels[] = $d->format('d/m');
            $chartValues[] = $dRev;
        }

        // 5. 10 Transaksi Terakhir (Identik dengan format owner_reports yang terbukti sukses dibaca Flutter)
        $recentTransactions = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($t) {
                $isTicket = !empty($t->ticket_code);
                $userName = $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member');
                $codeOrUid = $isTicket ? $t->ticket_code : ($t->card->license_plate ?? ($t->card->uid ?? '-'));
                return [
                    'id' => (int) $t->id,
                    'transaction_id' => (int) $t->id,
                    'ticket_code' => $t->ticket_code,
                    'is_ticket' => (bool) $isTicket,
                    'access_type' => $isTicket ? 'KARCIS' : 'RFID',
                    'access_method' => $isTicket ? 'ticket' : 'rfid',
                    'method' => $isTicket ? 'karcis' : 'rfid',
                    'metode' => $isTicket ? 'Karcis' : 'RFID',
                    'metode_transaksi' => $isTicket ? 'Karcis Parkir' : 'Kartu RFID',
                    'metode_akses' => $isTicket ? 'Karcis' : 'RFID',
                    'metode_pembayaran' => $isTicket ? 'Tunai di Kasir' : 'Potong Saldo Kartu RFID',
                    'parking_floor' => (int) ($t->parking_floor ?? 1),
                    'parking_floor_label' => ($t->parking_floor == 2) ? 'Lantai 2 (LT 2)' : 'Lantai 1 (LT 1)',
                    'name' => (string) $userName,
                    'user_name' => (string) $userName,
                    'driver_name' => (string) $userName,
                    'code' => (string) $codeOrUid,
                    'license_plate' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                    'plate_number' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                    'plat_nomor' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                    'uid' => (string) ($t->card->uid ?? ($isTicket ? $t->ticket_code : '-')),
                    'tap_in_time' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->toDateTimeString() : null,
                    'tap_out_time' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->toDateTimeString() : null,
                    'tap_in_formatted' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-',
                    'tap_out_formatted' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->format('d/m/Y H:i') : '-',
                    'duration_minutes' => (int) ($t->duration_minutes ?? 0),
                    'duration' => (int) ($t->duration_minutes ?? 0),
                    'duration_formatted' => $t->duration_minutes ? $t->duration_minutes . ' menit' : '-',
                    'total_fee' => (int) ($t->total_fee ?? 0),
                    'total_fee_formatted' => 'Rp ' . number_format($t->total_fee ?? 0, 0, ',', '.'),
                    'fee' => (int) ($t->total_fee ?? 0),
                    'amount' => (int) ($t->total_fee ?? 0),
                    'status' => (string) $t->status,
                    'payment_method' => (string) ($isTicket ? 'cash' : ($t->payment_method ?? 'card')),
                    'payment_method_label' => (string) ($isTicket ? 'Tunai di Kasir (Karcis)' : 'Potong Saldo Kartu RFID'),
                    'payment_type' => (string) ($isTicket ? 'karcis' : 'rfid'),
                    'type' => (string) ($isTicket ? 'karcis' : 'rfid'),
                    'gate_in_code' => (string) ($t->deviceIn ? $t->deviceIn->device_code : 'Gate In'),
                    'gate_out_code' => (string) ($t->deviceOut ? $t->deviceOut->device_code : 'Gate Out'),
                ];
            });

        // 6. Rincian Lantai (Format Map dan List untuk kompatibilitas penuh Flutter Model)
        $floor1Data = [
            'floor_number' => 1,
            'floor' => 1,
            'floor_code' => 'LT 1',
            'code' => 'LT 1',
            'name' => 'Lantai 1',
            'title' => 'Lantai 1',
            'capacity' => self::CAPACITY_PER_FLOOR,
            'total' => self::CAPACITY_PER_FLOOR,
            'total_slots' => self::CAPACITY_PER_FLOOR,
            'parked' => $floor1Parked,
            'parked_count' => $floor1Parked,
            'used' => $floor1Parked,
            'terisi' => $floor1Parked,
            'available' => $floor1Available,
            'available_slots' => $floor1Available,
            'empty' => $floor1Available,
            'tersedia' => $floor1Available,
            'is_full' => $floor1Available <= 0,
            'full' => $floor1Available <= 0,
        ];

        $floor2Data = [
            'floor_number' => 2,
            'floor' => 2,
            'floor_code' => 'LT 2',
            'code' => 'LT 2',
            'name' => 'Lantai 2',
            'title' => 'Lantai 2',
            'capacity' => self::CAPACITY_PER_FLOOR,
            'total' => self::CAPACITY_PER_FLOOR,
            'total_slots' => self::CAPACITY_PER_FLOOR,
            'parked' => $floor2Parked,
            'parked_count' => $floor2Parked,
            'used' => $floor2Parked,
            'terisi' => $floor2Parked,
            'available' => $floor2Available,
            'available_slots' => $floor2Available,
            'empty' => $floor2Available,
            'tersedia' => $floor2Available,
            'is_full' => $floor2Available <= 0,
            'full' => $floor2Available <= 0,
        ];

        $floorsMap = [
            'lt1' => $floor1Data,
            'lt2' => $floor2Data,
        ];
        $floorsList = [
            $floor1Data,
            $floor2Data,
        ];

        // 7. Objek Stats Ramping (Sesuai dengan owner_reports yang terbukti bekerja di mobile)
        $statsObject = [
            'total_records' => $totalTransactions,
            'total_count' => $totalTransactions,
            'total_transactions' => $totalTransactions,
            'today_transactions' => $todayTransactions,
            'completed_transactions' => $completedTransactions,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'today_revenue' => $todayRevenue,
            'today_revenue_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
            'total_income' => $totalRevenue,
            'total_income_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'today_income' => $todayRevenue,
            'today_income_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
            'month_revenue' => $monthRevenue,
            'month_revenue_formatted' => 'Rp ' . number_format($monthRevenue, 0, ',', '.'),
            'week_revenue' => $weekRevenue,
            'active_parked' => $parkedCount,
            'parked_count' => $parkedCount,
            'total_parked' => $parkedCount,
            'available_slots' => $availableSlots,
            'empty_slots' => $availableSlots,
            'capacity' => self::TOTAL_CAPACITY,
            'total_capacity' => self::TOTAL_CAPACITY,
            'occupancy_percentage' => $occupancyPercent,
            'occupancy_rate' => $occupancyPercent,
            'occupancy_rate_percent' => $occupancyPercent,
            'avg_duration_minutes' => $avgMinutes,
            'avg_fee' => $avgFee,
            'rate_per_hour' => self::RATE_PER_HOUR,
        ];

        // 8. Ringkasan Metrik Tunggal
        $summaryData = [
            'total_records' => $totalTransactions,
            'total_count' => $totalTransactions,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'total_revenue_str' => (string) $totalRevenue,
            'total_income' => $totalRevenue,
            'total_income_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'revenue' => $totalRevenue,
            'income' => $totalRevenue,
            'total_earnings' => $totalRevenue,
            'earnings' => $totalRevenue,
            'today_revenue' => $todayRevenue,
            'today_revenue_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
            'today_revenue_str' => (string) $todayRevenue,
            'today_income' => $todayRevenue,
            'today_income_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
            'revenue_today' => $todayRevenue,
            'income_today' => $todayRevenue,
            'month_revenue' => $monthRevenue,
            'month_revenue_formatted' => 'Rp ' . number_format($monthRevenue, 0, ',', '.'),
            'monthly_revenue' => $monthRevenue,
            'month_income' => $monthRevenue,
            'monthly_income' => $monthRevenue,
            'week_revenue' => $weekRevenue,
            'weekly_revenue' => $weekRevenue,
            'week_income' => $weekRevenue,
            'total_transactions' => $totalTransactions,
            'transactions_count' => $totalTransactions,
            'total_tx' => $totalTransactions,
            'today_transactions' => $todayTransactions,
            'today_tx' => $todayTransactions,
            'completed_transactions' => $completedTransactions,
            'completed_count' => $completedTransactions,
            'total_completed' => $completedTransactions,
            'active_parked' => $parkedCount,
            'parked_count' => $parkedCount,
            'total_parked' => $parkedCount,
            'parked_vehicles' => $parkedCount,
            'currently_parked' => $parkedCount,
            'parked_lt1' => $floor1Parked,
            'parked_lt2' => $floor2Parked,
            'available_slots' => $availableSlots,
            'empty_slots' => $availableSlots,
            'slots_available' => $availableSlots,
            'capacity' => self::TOTAL_CAPACITY,
            'total_capacity' => self::TOTAL_CAPACITY,
            'slots_capacity' => self::TOTAL_CAPACITY,
            'occupancy_percentage' => $occupancyPercent,
            'occupancy_rate_percent' => $occupancyPercent,
            'occupancy_rate' => $occupancyPercent,
            'occupancy' => $occupancyPercent,
            'avg_duration_minutes' => $avgMinutes,
            'avg_fee' => $avgFee,
            'rate_per_hour' => self::RATE_PER_HOUR,
            'lt1_available' => $floor1Available,
            'lt1_parked' => $floor1Parked,
            'lt1_capacity' => self::CAPACITY_PER_FLOOR,
            'lt2_available' => $floor2Available,
            'lt2_parked' => $floor2Parked,
            'lt2_capacity' => self::CAPACITY_PER_FLOOR,
            // Bahasa Indonesia
            'total_pendapatan' => $totalRevenue,
            'total_pendapatan_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'pendapatan_hari_ini' => $todayRevenue,
            'pendapatan_hari_ini_formatted' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'),
            'total_transaksi' => $totalTransactions,
            'transaksi_hari_ini' => $todayTransactions,
            'kendaraan_parkir' => $parkedCount,
            'slot_tersedia' => $availableSlots,
            'kapasitas' => self::TOTAL_CAPACITY,
            'persentase_okupansi' => $occupancyPercent,
            'persentase' => $occupancyPercent,
        ];

        // 9. Root Payload
        $payload = array_merge([
            'status' => 'success',
            'success' => true,
            'code' => 200,
            'error' => false,
            'message' => 'Owner dashboard data fetched successfully',
            'server_time' => $now->toDateTimeString(),
            'preset' => 'today',
        ], $summaryData);

        // Pasang objek floors, charts, dan transactions langsung di root level
        $payload['floors'] = $floorsMap;
        $payload['floor_list'] = $floorsList;
        $payload['floors_list'] = $floorsList;
        $payload['floor_1'] = $floor1Data;
        $payload['floor_2'] = $floor2Data;
        $payload['floor1'] = $floor1Data;
        $payload['floor2'] = $floor2Data;
        
        $payload['charts'] = $last7Days;
        $payload['chart'] = $last7Days;
        $payload['chart_data'] = $last7Days;
        $payload['trend'] = $last7Days;
        $payload['trend_data'] = $last7Days;
        $payload['daily_trend'] = $last7Days;
        $payload['weekly_trend'] = $last7Days;
        $payload['chart_labels'] = $chartLabels;
        $payload['chart_values'] = $chartValues;

        $payload['recent_transactions'] = $recentTransactions;
        $payload['transactions'] = $recentTransactions;
        $payload['reports'] = $recentTransactions;
        $payload['recent'] = $recentTransactions;
        $payload['history'] = $recentTransactions;
        $payload['stats'] = $statsObject;

        // 10. Data Wrapper untuk Mobile Model yang membaca response.data
        $dataWrapper = array_merge($summaryData, [
            'status' => 'success',
            'success' => true,
            'code' => 200,
            'error' => false,
            'message' => 'Owner dashboard data fetched successfully',
            'floors' => $floorsMap,
            'floor_list' => $floorsList,
            'floors_list' => $floorsList,
            'floor_1' => $floor1Data,
            'floor_2' => $floor2Data,
            'charts' => $last7Days,
            'chart' => $last7Days,
            'trend' => $last7Days,
            'recent_transactions' => $recentTransactions,
            'transactions' => $recentTransactions,
            'reports' => $recentTransactions,
            'stats' => $statsObject,
        ]);

        $payload['data'] = $dataWrapper;
        $payload['summary'] = $statsObject;
        $payload['overview'] = $statsObject;
        $payload['metrics'] = $statsObject;

        return response()->json($payload);
    }

    /**
     * 9. OWNER REPORTS API (Daftar Riwayat Laporan Transaksi untuk Akun Owner di Mobile)
     */
    public function ownerReports(Request $request): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['status' => 'ok'], 200);
        }

        $preset = $request->input('preset', $request->query('preset', 'all'));
        $search = trim($request->input('search', $request->query('search', '')));
        $startDate = $request->input('start_date', $request->query('start_date', ''));
        $endDate = $request->input('end_date', $request->query('end_date', ''));
        $statusFilter = $request->input('status', $request->query('status', 'all'));
        $accessType = $request->input('access_type', $request->query('access_type', 'all'));
        $floorFilter = $request->input('floor', $request->query('floor', 'all'));
        $limit = (int) $request->input('limit', $request->query('limit', 100));
        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        if ($preset === 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'yesterday') {
            $startDate = Carbon::yesterday()->toDateString();
            $endDate = Carbon::yesterday()->toDateString();
        } elseif ($preset === 'week') {
            $startDate = Carbon::now()->subDays(6)->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'month') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $query = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut']);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('tap_in_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif (!empty($startDate)) {
            $query->where('tap_in_time', '>=', $startDate . ' 00:00:00');
        } elseif (!empty($endDate)) {
            $query->where('tap_in_time', '<=', $endDate . ' 23:59:59');
        }

        if ($statusFilter === 'COMPLETED' || $statusFilter === 'PARKED') {
            $query->where('status', $statusFilter);
        }

        if ($accessType === 'ticket') {
            $query->whereNotNull('ticket_code');
        } elseif ($accessType === 'card') {
            $query->whereNull('ticket_code')->whereNotNull('card_id');
        }

        if ($floorFilter === '1') {
            $query->where('parking_floor', 1);
        } elseif ($floorFilter === '2') {
            $query->where('parking_floor', 2);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('card', function ($qc) use ($search) {
                      $qc->where('uid', 'like', "%{$search}%")
                         ->orWhere('license_plate', 'like', "%{$search}%");
                  });
            });
        }

        $totalRecords = (clone $query)->count();
        $totalRevenue = (int) (clone $query)->where('status', 'COMPLETED')->sum('total_fee');
        $completedCount = (int) (clone $query)->where('status', 'COMPLETED')->count();
        $parkedCount = (int) (clone $query)->where('status', '!=', 'COMPLETED')->count();

        $transactions = $query->orderBy('id', 'desc')->limit($limit)->get()->map(function ($t) {
            $isTicket = !empty($t->ticket_code);
            $userName = $isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member');
            $codeOrUid = $isTicket ? $t->ticket_code : ($t->card->license_plate ?? ($t->card->uid ?? '-'));
            $durMin = (int) ($t->duration_minutes ?? 0);
            $billedHours = (int) ceil(max(1, $durMin) / 60);

            return [
                'id' => (int) $t->id,
                'transaction_id' => (int) $t->id,
                'ticket_code' => $t->ticket_code,
                'is_ticket' => (bool) $isTicket,
                'access_type' => $isTicket ? 'KARCIS' : 'RFID',
                'access_method' => $isTicket ? 'ticket' : 'rfid',
                'method' => $isTicket ? 'karcis' : 'rfid',
                'metode' => $isTicket ? 'Karcis' : 'RFID',
                'metode_transaksi' => $isTicket ? 'Karcis Parkir' : 'Kartu RFID',
                'metode_akses' => $isTicket ? 'Karcis' : 'RFID',
                'metode_pembayaran' => $isTicket ? 'Tunai di Kasir' : 'Potong Saldo Kartu RFID',
                'parking_floor' => (int) ($t->parking_floor ?? 1),
                'parking_floor_label' => ($t->parking_floor == 2) ? 'Lantai 2 (LT 2)' : 'Lantai 1 (LT 1)',
                'name' => (string) $userName,
                'user_name' => (string) $userName,
                'driver_name' => (string) $userName,
                'code' => (string) $codeOrUid,
                'license_plate' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                'plate_number' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                'plat_nomor' => (string) ($t->card->license_plate ?? ($isTicket ? 'Karcis' : '-')),
                'uid' => (string) ($t->card->uid ?? ($isTicket ? $t->ticket_code : '-')),
                'tap_in_time' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->toDateTimeString() : null,
                'tap_out_time' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->toDateTimeString() : null,
                'tap_in_formatted' => $t->tap_in_time ? Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-',
                'tap_out_formatted' => $t->tap_out_time ? Carbon::parse($t->tap_out_time)->format('d/m/Y H:i') : '-',
                'duration_minutes' => $durMin,
                'duration' => $durMin,
                'duration_formatted' => $durMin ? $durMin . ' menit' : '-',
                'billed_hours' => $billedHours,
                'hours' => $billedHours,
                'total_fee' => (int) ($t->total_fee ?? 0),
                'total_fee_formatted' => 'Rp ' . number_format($t->total_fee ?? 0, 0, ',', '.'),
                'fee' => (int) ($t->total_fee ?? 0),
                'amount' => (int) ($t->total_fee ?? 0),
                'status' => (string) $t->status,
                'payment_method' => (string) ($isTicket ? 'cash' : ($t->payment_method ?? 'card')),
                'payment_method_label' => (string) ($isTicket ? 'Tunai di Kasir (Karcis)' : 'Potong Saldo Kartu RFID'),
                'payment_type' => (string) ($isTicket ? 'karcis' : 'rfid'),
                'type' => (string) ($isTicket ? 'karcis' : 'rfid'),
                'gate_in_code' => (string) ($t->deviceIn ? $t->deviceIn->device_code : 'Gate In'),
                'gate_out_code' => (string) ($t->deviceOut ? $t->deviceOut->device_code : 'Gate Out'),
            ];
        });

        $payload = [
            'status' => 'success',
            'preset' => $preset,
            'total_records' => $totalRecords,
            'total_count' => $totalRecords,
            'total_revenue' => $totalRevenue,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'completed_count' => $completedCount,
            'parked_count' => $parkedCount,
            'transactions' => $transactions,
            'reports' => $transactions,
            'data' => $transactions,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'completed_count' => $completedCount,
                'completed_transactions' => $completedCount,
                'parked_count' => $parkedCount,
            ],
            'stats' => [
                'total_records' => $totalRecords,
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'completed_count' => $completedCount,
                'completed_transactions' => $completedCount,
                'parked_count' => $parkedCount,
            ]
        ];

        return response()->json($payload);
    }
}
