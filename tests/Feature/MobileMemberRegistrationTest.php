<?php

namespace Tests\Feature;

use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileMemberRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_client_registers_then_admin_creates_member_card(): void
    {
        // 1. Client mendaftar data diri via Mobile API
        $registerResponse = $this->postJson('/api/client/register', [
            'name' => 'Andi Pratama',
            'email' => 'andi@example.com',
            'password' => 'rahasia123',
            'phone' => '081234567890',
            'license_plate' => 'b 1234 xyz',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'has_card' => false,
                'pending_card' => true,
                'card_status' => 'pending_card',
            ]);

        $user = User::where('email', 'andi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('client', $user->role);
        $this->assertSame('B 1234 XYZ', $user->license_plate);
        $this->assertDatabaseCount('rfid_cards', 0);

        // 2. Client login & cek profile sebelum kartu dibuat admin
        $loginBefore = $this->postJson('/api/client/login', [
            'email' => 'andi@example.com',
            'password' => 'rahasia123',
        ]);
        $loginBefore->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'has_card' => false,
                'pending_card' => true,
                'card_status' => 'pending_card',
            ]);

        $profileBefore = $this->getJson('/api/client/profile?user_id=' . $user->id);
        $profileBefore->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'has_card' => false,
                'pending_card' => true,
            ]);

        // 3. Topup ditolak sebelum kartu dibuatkan oleh admin
        $topupBefore = $this->postJson('/api/client/topup', [
            'user_id' => $user->id,
            'amount' => 20000,
        ]);
        $topupBefore->assertStatus(404);

        // 4. Admin melihat pendaftar di halaman Kelola Kartu RFID
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $cardsPage = $this->actingAs($admin)->get('/admin/cards');
        $cardsPage->assertStatus(200)
            ->assertSee('Andi Pratama')
            ->assertSee('andi@example.com')
            ->assertSee('Buatkan Kartu');

        // 5. Admin membuatkan kartu member fisik untuk client (password dikosongkan)
        $storeResponse = $this->actingAs($admin)->post('/admin/cards', [
            'user_id' => $user->id,
            'uid' => 'AABBCCDD',
            'name' => 'Andi Pratama',
            'email' => 'andi@example.com',
            'phone' => '081234567890',
            'license_plate' => 'B 1234 XYZ',
            'password' => '',
            'status' => 'active',
            'balance' => 50000,
        ]);
        $storeResponse->assertRedirect(route('admin.cards.index'))
            ->assertSessionHas('success');

        $card = RfidCard::where('user_id', $user->id)->first();
        $this->assertNotNull($card);
        $this->assertSame('AABBCCDD', $card->uid);
        $this->assertSame('B 1234 XYZ', $card->license_plate);
        $this->assertSame(50000, (int) $card->balance);
        $this->assertSame('active', $card->status);

        // 6. Client masih bisa login dengan password mobile dan kini memiliki kartu aktif
        $loginAfter = $this->postJson('/api/client/login', [
            'email' => 'andi@example.com',
            'password' => 'rahasia123',
        ]);
        $loginAfter->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'has_card' => true,
                'pending_card' => false,
                'card_status' => 'active',
                'card' => [
                    'uid' => 'AABBCCDD',
                    'balance' => 50000,
                    'status' => 'active',
                ],
            ]);
    }

    public function test_admin_can_delete_pending_mobile_member(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin2@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $pending = User::create([
            'name' => 'Calon Dihapus',
            'email' => 'hapus@example.com',
            'password' => Hash::make('123456'),
            'role' => 'client',
        ]);

        $response = $this->actingAs($admin)->post("/admin/cards/pending/{$pending->id}/delete");
        $response->assertRedirect(route('admin.cards.index'));

        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
    }
}

