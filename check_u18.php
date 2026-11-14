<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Transactions: " . App\Models\ParkingTransaction::where('user_id', 18)->count() . "\n";
echo "Card: " . App\Models\RfidCard::where('user_id', 18)->count() . "\n";
echo "Access Logs: " . App\Models\AccessLog::where('user_id', 18)->count() . "\n";

