<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetugasRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_model_helper_and_creation(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Palang Keluar',
            'email' => 'petugas@eparkir.local',
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);

        $this->assertTrue($petugas->isPetugas());
        $this->assertFalse($petugas->isAdmin());
        $this->assertFalse($petugas->isClient());
        $this->assertFalse($petugas->isOwner());
    }

    public function test_petugas_can_login_and_redirects_to_monitoring(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Palang Keluar',
            'email' => 'petugas@eparkir.local',
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);

        $response = $this->post('/login', [
            'email' => 'petugas@eparkir.local',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.monitoring'));
        $this->assertAuthenticatedAs($petugas);
    }

    public function test_petugas_can_access_monitoring_and_status(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Palang Keluar',
            'email' => 'petugas@eparkir.local',
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);

        $this->actingAs($petugas);

        $resMonitoring = $this->get(route('admin.monitoring'));
        $resMonitoring->assertStatus(200);

        $resStatus = $this->get(route('admin.status.index'));
        $resStatus->assertStatus(200);
    }

    public function test_petugas_cannot_access_admin_restricted_areas(): void
    {
        $petugas = User::create([
            'name' => 'Petugas Palang Keluar',
            'email' => 'petugas@eparkir.local',
            'password' => bcrypt('password123'),
            'role' => 'petugas',
        ]);

        $this->actingAs($petugas);

        // Akses kelola pengguna harus ditolak dan redirect ke monitoring
        $resUsers = $this->get(route('admin.users.index'));
        $resUsers->assertRedirect(route('admin.monitoring'));

        // Akses laporan finansial harus ditolak
        $resReports = $this->get(route('admin.reports.index'));
        $resReports->assertRedirect(route('admin.monitoring'));

        // Akses audit log harus ditolak
        $resLogs = $this->get(route('admin.logs.index'));
        $resLogs->assertRedirect(route('admin.monitoring'));

        // Akses kelola kartu RFID harus ditolak
        $resCards = $this->get(route('admin.cards.index'));
        $resCards->assertRedirect(route('admin.monitoring'));
    }
}

