<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicStatusController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\CardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\StatusController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\ClientApiController;
use App\Http\Controllers\ReceiptController;

// =========================================================================
// Dukungan kompatibilitas URL XAMPP (untuk Mobile App & ESP saat dijalankan via artisan serve)
$registerCompatibilityRoutes = function () {
    Route::prefix('api')->group(base_path('routes/api.php'));
    Route::prefix('client')->group(function () {
        Route::match(['get', 'post'], '/login', [ClientApiController::class, 'login']);
        Route::match(['get', 'post'], '/register', [ClientApiController::class, 'register']);
        Route::match(['get', 'post'], '/profile', [ClientApiController::class, 'profile']);
        Route::match(['get', 'post'], '/update_profile', [ClientApiController::class, 'updateProfile']);
        Route::match(['get', 'post'], '/status', [ClientApiController::class, 'status']);
        Route::match(['get', 'post'], '/slots', [ClientApiController::class, 'slots']);
        Route::match(['get', 'post'], '/history', [ClientApiController::class, 'history']);
        Route::match(['get', 'post'], '/topup', [ClientApiController::class, 'topup']);
        Route::match(['get', 'post'], '/api-status', [ClientApiController::class, 'handle']);
        Route::match(['get', 'post'], '/api_status.php', [ClientApiController::class, 'handle']);
        Route::any('/', [ClientApiController::class, 'handle']);
    });
    Route::match(['get', 'post'], '/api_status.php', [ClientApiController::class, 'handle']);
    Route::match(['get', 'post'], '/api-status', [ClientApiController::class, 'handle']);
    Route::match(['get', 'post'], '/scan.php', [\App\Http\Controllers\Api\ScanController::class, 'scan']);
    Route::match(['get', 'post'], '/override.php', [\App\Http\Controllers\Api\OverrideController::class, 'override']);
};

Route::prefix('E-Parkir/E-Parkir_Web_Laravel/public')->group($registerCompatibilityRoutes);
Route::prefix('E-Parkir/E-Parkir_Web_Laravel')->group($registerCompatibilityRoutes);
Route::prefix('E-Parkir')->group($registerCompatibilityRoutes);

// 1. PUBLIC & AUTHENTICATION ROUTES
// =========================================================================
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/status', [PublicStatusController::class, 'index'])->name('status');
Route::get('/status.php', [PublicStatusController::class, 'index']);

// Struk Parkir Thermal 58mm (Eppos EP589 V2 / POS-58)
Route::get('/receipt/latest-ticket/escpos', [ReceiptController::class, 'latestTicketEscpos'])->name('receipt.ticket.latest.escpos');
Route::get('/receipt/latest/escpos', function (\Illuminate\Http\Request $request) {
    return app(ReceiptController::class)->escposData('latest', $request);
})->name('receipt.latest.escpos');
Route::get('/receipt/latest', [ReceiptController::class, 'latest'])->name('receipt.latest');
Route::get('/receipt/{id}/escpos', [ReceiptController::class, 'escposData'])->name('receipt.escpos');
Route::get('/receipt/{id}', [ReceiptController::class, 'show'])->name('receipt.show');

// =========================================================================
// 2. CLIENT & OWNER MOBILE API ALIASES (Bebas Tampilan Web, Murni Backend API)
// =========================================================================
Route::prefix('client')->group(function () {
    Route::match(['get', 'post'], '/api-status', [ClientApiController::class, 'handle'])->name('client.api-status');
    Route::match(['get', 'post'], '/api_status.php', [ClientApiController::class, 'handle']);
    Route::match(['get', 'post'], '/login', [ClientApiController::class, 'login']);
    Route::match(['get', 'post'], '/register', [ClientApiController::class, 'register']);
    Route::match(['get', 'post'], '/profile', [ClientApiController::class, 'profile']);
    Route::match(['get', 'post'], '/update_profile', [ClientApiController::class, 'updateProfile']);
    Route::match(['get', 'post'], '/status', [ClientApiController::class, 'status']);
    Route::match(['get', 'post'], '/slots', [ClientApiController::class, 'slots']);
    Route::match(['get', 'post'], '/history', [ClientApiController::class, 'history']);
    Route::match(['get', 'post'], '/topup', [ClientApiController::class, 'topup']);
    Route::match(['get', 'post'], '/owner_dashboard', [ClientApiController::class, 'ownerDashboard']);
    Route::match(['get', 'post'], '/owner_reports', [ClientApiController::class, 'ownerReports']);
    Route::any('/', [ClientApiController::class, 'handle']);
});

// Alias di Root Web untuk kompatibilitas script lama/mobile
Route::match(['get', 'post'], '/api_status.php', [ClientApiController::class, 'handle']);
Route::match(['get', 'post'], '/api-status', [ClientApiController::class, 'handle']);

// Fallback Named Routes agar kebal RouteNotFoundException dari legacy links/cache
Route::redirect('/client/dashboard', '/login')->name('client.dashboard');
Route::redirect('/owner/dashboard', '/login')->name('owner.dashboard');
Route::redirect('/owner/reports', '/login')->name('owner.reports.index');
Route::redirect('/owner/transactions', '/login')->name('owner.transactions.index');
Route::redirect('/owner/reports/export', '/login')->name('owner.reports.export');

// =========================================================================
// 3. ADMIN PORTAL (Full Operasional Web: Protected by Auth and Role: admin)
// =========================================================================
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Dashboard & Live Stats
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/api-stats', [DashboardController::class, 'apiStats'])->name('admin.api.stats');
    Route::get('/api_stats.php', [DashboardController::class, 'apiStats'])->name('admin.api-stats');

    // Monitoring & Hardware Simulator
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring');
    Route::post('/monitoring/tap', [MonitoringController::class, 'tap'])->name('admin.monitoring.tap');
    Route::post('/monitoring/override', [MonitoringController::class, 'override'])->name('admin.monitoring.override');
    Route::post('/monitoring/checkout', [MonitoringController::class, 'checkout'])->name('admin.monitoring.checkout');
    Route::post('/monitoring/ticket', [MonitoringController::class, 'generateTicket'])->name('admin.monitoring.ticket');

    // RFID Cards Management
    Route::get('/cards', [CardController::class, 'index'])->name('admin.cards.index');
    Route::post('/cards', [CardController::class, 'store'])->name('admin.cards.store');
    Route::post('/cards/toggle-status', [CardController::class, 'toggleStatus'])->name('admin.cards.toggle-status');
    Route::match(['post', 'put'], '/cards/{id}', [CardController::class, 'update'])->name('admin.cards.update');
    Route::post('/cards/{id}/topup', [CardController::class, 'topup'])->name('admin.cards.topup');
    Route::match(['post', 'delete'], '/cards/{id}/delete', [CardController::class, 'destroy'])->name('admin.cards.destroy');
    Route::get('/cards-latest-scan', [CardController::class, 'latestScan'])->name('admin.cards.latest-scan');
    Route::get('/api_register_scan.php', [CardController::class, 'latestScan']);

    // Reports & Analytics (Versi Admin)
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('admin.reports.export');

    // Audit Logs
    Route::get('/logs', [LogController::class, 'index'])->name('admin.logs.index');

    // Parking Slot Map
    Route::get('/status', [StatusController::class, 'index'])->name('admin.status.index');

    // Manajemen Akun Pengelola (Admin & Owner)
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::match(['post', 'put'], '/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::match(['post', 'delete'], '/users/{id}/delete', [UserController::class, 'destroy'])->name('admin.users.destroy');
});
