<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class GoogleAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const TOKEN_INFO_URL = 'https://oauth2.googleapis.com/tokeninfo';
    private const USER_INFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function isWebConfigured(): bool
    {
        return !empty(config('services.google.client_id'))
            && !empty(config('services.google.client_secret'));
    }

    /**
     * Daftar Client ID Google yang diizinkan (Web, Android, iOS).
     */
    public function getAllowedClientIds(): array
    {
        $ids = [];
        $primary = trim((string) config('services.google.client_id', ''));
        if ($primary !== '') {
            $ids[] = $primary;
        }

        $extra = config('services.google.allowed_client_ids', []);
        if (is_array($extra)) {
            foreach ($extra as $id) {
                $trimmed = trim((string) $id);
                if ($trimmed !== '') {
                    $ids[] = $trimmed;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function buildAuthorizationUrl(string $state, string $redirectUri): string
    {
        $clientId = trim((string) config('services.google.client_id', ''));
        if ($clientId === '') {
            throw new RuntimeException('GOOGLE_CLIENT_ID belum dikonfigurasi.');
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::AUTH_URL . '?' . $query;
    }

    /**
     * Tukar authorization code OAuth Web menjadi profil Google terverifikasi.
     *
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    public function exchangeCodeForUserInfo(string $code, string $redirectUri): array
    {
        $clientId = trim((string) config('services.google.client_id', ''));
        $clientSecret = trim((string) config('services.google.client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Konfigurasi Google OAuth belum lengkap.');
        }

        try {
            $tokenResponse = $this->httpClient()->asForm()->post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Gagal terhubung ke server Google (DNS/koneksi timeout). Pastikan laptop terhubung ke internet.', 0, $e);
        }

        if (!$tokenResponse->successful()) {
            throw new RuntimeException('Gagal menukar kode otorisasi Google.');
        }

        $tokenData = $tokenResponse->json();
        $accessToken = trim((string) ($tokenData['access_token'] ?? ''));
        $idToken = trim((string) ($tokenData['id_token'] ?? ''));

        if ($accessToken !== '') {
            return $this->fetchUserInfoByAccessToken($accessToken);
        }

        if ($idToken !== '') {
            return $this->verifyIdToken($idToken);
        }

        throw new RuntimeException('Google tidak mengembalikan token akses yang valid.');
    }

    /**
     * Verifikasi token Google dari aplikasi Mobile (id_token atau access_token).
     *
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    public function verifyMobileToken(?string $idToken, ?string $accessToken = null): array
    {
        $idToken = trim((string) $idToken);
        $accessToken = trim((string) $accessToken);

        if ($idToken !== '') {
            return $this->verifyIdToken($idToken);
        }

        if ($accessToken !== '') {
            return $this->verifyAccessTokenForMobile($accessToken);
        }

        throw new InvalidArgumentException('Token Google (id_token atau access_token) wajib dikirim!');
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    private function verifyIdToken(string $idToken): array
    {
        $response = $this->httpClient()->get(self::TOKEN_INFO_URL, [
            'id_token' => $idToken,
        ]);

        if (!$response->successful()) {
            throw new InvalidArgumentException('Token ID Google tidak valid atau sudah kedaluwarsa.');
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Respons verifikasi token Google tidak valid.');
        }

        $allowedIds = $this->getAllowedClientIds();
        if (!empty($allowedIds)) {
            $aud = trim((string) ($payload['aud'] ?? ''));
            $azp = trim((string) ($payload['azp'] ?? ''));
            if (!in_array($aud, $allowedIds, true) && !in_array($azp, $allowedIds, true)) {
                throw new InvalidArgumentException('Client ID Google tidak cocok dengan konfigurasi server.');
            }
        }

        return $this->normalizeGoogleProfile($payload);
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    private function verifyAccessTokenForMobile(string $accessToken): array
    {
        $allowedIds = $this->getAllowedClientIds();
        if (!empty($allowedIds)) {
            $infoRes = $this->httpClient()->get(self::TOKEN_INFO_URL, [
                'access_token' => $accessToken,
            ]);

            if (!$infoRes->successful()) {
                throw new InvalidArgumentException('Access token Google tidak valid atau sudah kedaluwarsa.');
            }

            $info = $infoRes->json();
            $aud = trim((string) ($info['aud'] ?? ($info['issued_to'] ?? '')));
            $azp = trim((string) ($info['azp'] ?? ''));
            if (!in_array($aud, $allowedIds, true) && !in_array($azp, $allowedIds, true)) {
                throw new InvalidArgumentException('Client ID Google tidak cocok dengan konfigurasi server.');
            }
        }

        return $this->fetchUserInfoByAccessToken($accessToken);
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    private function fetchUserInfoByAccessToken(string $accessToken): array
    {
        $response = $this->httpClient()
            ->withToken($accessToken)
            ->get(self::USER_INFO_URL);

        if (!$response->successful()) {
            throw new InvalidArgumentException('Gagal mengambil profil akun dari Google API.');
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Respons profil Google API tidak valid.');
        }

        return $this->normalizeGoogleProfile($payload);
    }

    /**
     * @return array{google_id: string, email: string, name: string, avatar: ?string}
     */
    private function normalizeGoogleProfile(array $payload): array
    {
        $googleId = trim((string) ($payload['sub'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $emailVerifiedRaw = $payload['email_verified'] ?? false;
        $emailVerified = $emailVerifiedRaw === true || $emailVerifiedRaw === 'true' || $emailVerifiedRaw === 1 || $emailVerifiedRaw === '1';

        if ($googleId === '' || $email === '') {
            throw new InvalidArgumentException('Data akun Google tidak lengkap (ID atau email kosong).');
        }

        if (!$emailVerified) {
            throw new InvalidArgumentException('Email pada akun Google belum terverifikasi.');
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $name = strstr($email, '@', true) ?: 'Pengguna Google';
        }

        $avatar = trim((string) ($payload['picture'] ?? ''));

        return [
            'google_id' => $googleId,
            'email' => $email,
            'name' => mb_substr($name, 0, 100),
            'avatar' => $avatar !== '' ? $avatar : null,
        ];
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::connectTimeout(10)
            ->timeout(15)
            ->withOptions(['force_ip_resolve' => 'v4']);

        // Fallback CA bundle bawaan XAMPP di Windows jika curl.cainfo/openssl.cafile php.ini belum diisi
        if (empty(ini_get('curl.cainfo')) && empty(ini_get('openssl.cafile'))) {
            $xamppCaBundle = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt';
            if (is_file($xamppCaBundle)) {
                $client = $client->withOptions(['verify' => $xamppCaBundle]);
            }
        }

        return $client;
    }
}