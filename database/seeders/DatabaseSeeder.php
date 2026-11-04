<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Device;
use App\Models\RfidCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@eparkir.local'],
            [
                'name' => 'Admin Parkir (Sigma)',
                'password' => Hash::make('admin123'),
                'phone' => '081122334455',
                'role' => 'admin',
            ]
        );

        // 1b. Owner User (Hanya Akses Laporan & Rekapitulasi)
        $owner = User::firstOrCreate(
            ['email' => 'owner@eparkir.local'],
            [
                'name' => 'Owner Parkir (Owner)',
                'password' => Hash::make('owner123'),
                'phone' => '081199887766',
                'role' => 'owner',
            ]
        );

        // 2. Devices (Gate-01 Bidirectional, plus IN and OUT aliases)
        $gate1 = Device::firstOrCreate(
            ['device_code' => 'Gate-01'],
            [
                'gate_type' => 'BIDIRECTIONAL',
                'location' => 'Gerbang Utama (Masuk & Keluar)',
                'status' => 'online',
                'last_seen' => now(),
            ]
        );

        Device::firstOrCreate(
            ['device_code' => 'GATE_MASUK_01'],
            [
                'gate_type' => 'IN',
                'location' => 'Gerbang Masuk 01',
                'status' => 'online',
                'last_seen' => now(),
            ]
        );

        Device::firstOrCreate(
            ['device_code' => 'GATE_KELUAR_01'],
            [
                'gate_type' => 'OUT',
                'location' => 'Gerbang Keluar 01',
                'status' => 'online',
                'last_seen' => now(),
            ]
        );

        // 3. Demo Client if empty
        $client = User::firstOrCreate(
            ['email' => 'fardhan@gmail.com'],
            [
                'name' => 'Fardhan',
                'password' => Hash::make('user123'),
                'phone' => '081234567801',
                'role' => 'client',
            ]
        );

        RfidCard::firstOrCreate(
            ['uid' => 'E2000019'],
            [
                'user_id' => $client->id,
                'license_plate' => 'B 1234 SGM',
                'balance' => 50000,
                'status' => 'active',
            ]
        );
    }
}
