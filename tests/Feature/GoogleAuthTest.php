<?php

namespace Tests\Feature;

use App\Models\RfidCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_google_auto_registers_new_client_without_card(): void
    {
        config([
            'services.google.client_id' => 'test-web-client-id.apps.googleusercontent.com',
            'services.google.allowed_client_ids' => ['test-android-client-id.apps.googleusercontent.com'],
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'google-sub-12345',
                'email' => 'budi.google@gmail.com',
                'email_verified' => 'true',
                'name' => 'Budi Google',
                'picture' => 'https://lh3.googleusercontent.com/a/avatar-budi',
                'aud' => 'test-android-client-id.apps.googleusercontent.com',
            ], 200),
        ]);

        $response = $this->postJson('/api/client/google', [
            'mode' => 'register',
            'id_token' => 'fake-google-id-token',
            'phone' => '081299988877',
            'license_plate' => 'd 9876 abc',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'is_new_user' => true,
                'auth_provider' => 'google',
                'has_card' => false,
                'pending_card' => true,
                'card_status' => 'pending_card',
                'user' => [
                    'name' => 'Budi Google',
                    'email' => 'budi.google@gmail.com',
                    'phone' => '081299988877',
                    'license_plate' => 'D 9876 ABC',
                    'auth_provider' => 'google',
                    'role' => 'client',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi.google@gmail.com',
            'google_id' => 'google-sub-12345',
            'auth_provider' => 'google',
            'role' => 'client',
        ]);
        $this->assertDatabaseCount('rfid_cards', 0);
    }

    public function test_mobile_google_logs_in_existing_user_and_links_google_id(): void
    {
        config([
            'services.google.client_id' => 'test-web-client-id.apps.googleusercontent.com',
            'services.google.allowed_client_ids' => [],
        ]);

        $user = User::create([
            'name' => 'Siti Member',
            'email' => 'siti@gmail.com',
            'password' => Hash::make('secret123'),
            'role' => 'client',
        ]);

        RfidCard::create([
            'user_id' => $user->id,
            'uid' => '11223344',
            'balance' => 25000,
            'status' => 'active',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'test-web-client-id.apps.googleusercontent.com',
                'email' => 'siti@gmail.com',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub-99999',
                'email' => 'siti@gmail.com',
                'email_verified' => true,
                'name' => 'Siti Member',
                'picture' => 'https://lh3.googleusercontent.com/a/avatar-siti',
            ], 200),
        ]);

        $response = $this->postJson('/api/client?action=google_login', [
            'access_token' => 'fake-access-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_new_user' => false,
                'auth_provider' => 'google',
                'has_card' => true,
                'pending_card' => false,
                'card_status' => 'active',
                'card' => [
                    'uid' => '11223344',
                    'balance' => 25000,
                ],
            ]);

        $user->refresh();
        $this->assertSame('google-sub-99999', $user->google_id);
        $this->assertSame('https://lh3.googleusercontent.com/a/avatar-siti', $user->avatar);
    }

    public function test_mobile_google_login_rejects_unregistered_account(): void
    {
        config([
            'services.google.client_id' => 'test-web-client-id.apps.googleusercontent.com',
            'services.google.allowed_client_ids' => [],
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'test-web-client-id.apps.googleusercontent.com',
                'email' => 'unknown.user@gmail.com',
                'email_verified' => 'true',
                'sub' => 'google-sub-unknown',
                'name' => 'Unknown User',
            ], 200),
        ]);

        // Menggunakan mode: login atau action: google_login saat user belum ada
        $response = $this->postJson('/api/client?action=google_login', [
            'id_token' => 'fake-token-unknown',
            'mode' => 'login',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'code' => 'ACCOUNT_NOT_FOUND',
            ]);
    }

    public function test_mobile_google_register_rejects_already_registered_account(): void
    {
        config([
            'services.google.client_id' => 'test-web-client-id.apps.googleusercontent.com',
            'services.google.allowed_client_ids' => [],
        ]);

        User::create([
            'name' => 'Existing Member',
            'email' => 'existing@gmail.com',
            'password' => Hash::make('secret123'),
            'role' => 'client',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'test-web-client-id.apps.googleusercontent.com',
                'email' => 'existing@gmail.com',
                'email_verified' => 'true',
                'sub' => 'google-sub-existing',
                'name' => 'Existing Member',
            ], 200),
        ]);

        // Menggunakan mode: register atau action: google_register saat user sudah ada
        $response = $this->postJson('/api/client?action=google_register', [
            'id_token' => 'fake-token-existing',
            'mode' => 'register',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'code' => 'ACCOUNT_ALREADY_EXISTS',
            ]);
    }

    public function test_mobile_google_rejects_invalid_or_missing_mode(): void
    {
        config([
            'services.google.client_id' => 'test-web-client-id.apps.googleusercontent.com',
            'services.google.allowed_client_ids' => [],
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'test-web-client-id.apps.googleusercontent.com',
                'email' => 'random@gmail.com',
                'email_verified' => 'true',
                'sub' => 'google-sub-random',
                'name' => 'Random User',
            ], 200),
        ]);

        $response = $this->postJson('/api/client/google', [
            'id_token' => 'fake-token-random',
            'mode' => 'invalid_mode',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'code' => 'INVALID_AUTH_MODE',
            ]);
    }

    public function test_mobile_google_rejects_missing_or_invalid_token(): void
    {
        $missing = $this->postJson('/api/client/google', []);
        $missing->assertStatus(400)->assertJson(['status' => 'error']);

        config([
            'services.google.client_id' => 'expected-client-id',
            'services.google.allowed_client_ids' => [],
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'sub' => 'sub-1',
                'email' => 'user@gmail.com',
                'email_verified' => 'true',
                'aud' => 'wrong-client-id',
            ], 200),
        ]);

        $wrongAud = $this->postJson('/api/client/google', [
            'id_token' => 'invalid-aud-token',
        ]);
        $wrongAud->assertStatus(401)->assertJson(['status' => 'error']);
    }

    public function test_web_google_oauth_logs_in_admin_and_rejects_client(): void
    {
        config([
            'services.google.client_id' => 'web-client-id',
            'services.google.client_secret' => 'web-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin.google@gmail.com',
            'password' => Hash::make('adminpass'),
            'role' => 'admin',
        ]);

        $redirect = $this->get('/auth/google');
        $redirect->assertStatus(302);
        $state = session('google_oauth_state');
        $this->assertNotEmpty($state);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::sequence()
                ->push(['access_token' => 'admin-access-token'], 200)
                ->push(['access_token' => 'client-access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::sequence()
                ->push([
                    'sub' => 'admin-google-sub',
                    'email' => 'admin.google@gmail.com',
                    'email_verified' => true,
                    'name' => 'Admin Utama',
                    'picture' => 'https://lh3.googleusercontent.com/a/admin',
                ], 200)
                ->push([
                    'sub' => 'client-google-sub',
                    'email' => 'client.google@gmail.com',
                    'email_verified' => true,
                    'name' => 'Client Biasa',
                ], 200),
        ]);

        $callback = $this->get('/auth/google/callback?code=auth-code-123&state=' . $state);
        $callback->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        // Logout & uji client ditolak di portal web
        $this->post('/logout');

        User::create([
            'name' => 'Client Biasa',
            'email' => 'client.google@gmail.com',
            'password' => Hash::make('clientpass'),
            'role' => 'client',
        ]);

        $this->get('/auth/google');
        $clientState = session('google_oauth_state');

        $clientCallback = $this->get('/auth/google/callback?code=client-code&state=' . $clientState);
        $clientCallback->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_web_google_redirect_normalizes_host_to_match_callback_uri(): void
    {
        config([
            'services.google.client_id' => 'web-client-id',
            'services.google.client_secret' => 'web-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get('http://127.0.0.1/auth/google');
        $response->assertRedirect('http://localhost/auth/google');
    }
}
