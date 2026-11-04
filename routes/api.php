<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\OverrideController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\ClientApiController;

// IoT ESP8266 Gate RFID Scan & Tiket Masuk Karcis
Route::match(['get', 'post'], '/scan', [ScanController::class, 'scan']);
Route::match(['get', 'post'], '/scan.php', [ScanController::class, 'scan']);
Route::match(['get', 'post'], '/ticket', [ScanController::class, 'generateTicket']);
Route::match(['get', 'post'], '/ticket.php', [ScanController::class, 'generateTicket']);

// Gate Emergency Override
Route::match(['get', 'post'], '/override', [OverrideController::class, 'override']);
Route::match(['get', 'post'], '/override.php', [OverrideController::class, 'override']);

// ESP8266 Heartbeat Telemetry
Route::match(['get', 'post'], '/telemetry', [TelemetryController::class, 'telemetry']);
Route::match(['get', 'post'], '/telemetry.php', [TelemetryController::class, 'telemetry']);

// Mobile Client API (Dispatcher fleksibel + Sub-rute eksplisit)
Route::any('/client', [ClientApiController::class, 'handle']);
Route::any('/client.php', [ClientApiController::class, 'handle']);
Route::match(['get', 'post', 'options'], '/client/login', [ClientApiController::class, 'login']);
Route::match(['get', 'post', 'options'], '/client/register', [ClientApiController::class, 'register']);
Route::match(['get', 'post', 'options'], '/client/profile', [ClientApiController::class, 'profile']);
Route::match(['get', 'post', 'options'], '/client/update_profile', [ClientApiController::class, 'updateProfile']);
Route::match(['get', 'post', 'options'], '/client/status', [ClientApiController::class, 'status']);
Route::match(['get', 'post', 'options'], '/client/slots', [ClientApiController::class, 'slots']);
Route::match(['get', 'post', 'options'], '/client/history', [ClientApiController::class, 'history']);
Route::match(['get', 'post', 'options'], '/client/topup', [ClientApiController::class, 'topup']);
Route::match(['get', 'post', 'options'], '/client/owner_dashboard', [ClientApiController::class, 'ownerDashboard']);
Route::match(['get', 'post', 'options'], '/client/owner_reports', [ClientApiController::class, 'ownerReports']);

// Status check umum
Route::match(['get', 'post', 'options'], '/status', [ClientApiController::class, 'slots']);
Route::match(['get', 'post', 'options'], '/status.php', [ClientApiController::class, 'slots']);
