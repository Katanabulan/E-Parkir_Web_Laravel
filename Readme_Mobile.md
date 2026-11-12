# 📱 Panduan Integrasi Aplikasi Mobile (Flutter) & Backend E-Parkir IoT

Dokumen ini merupakan panduan teknis resmi untuk menghubungkan aplikasi mobile **E-Parkir IoT** (Pengendara / `client` dan Pemilik / `owner`) dengan backend **Laravel 13** di [`E-Parkir_Web_Laravel`](file:///c:/E-Parkir/E-Parkir_Web_Laravel).

---

## 🌐 1. Arsitektur Sistem & Koneksi Jaringan

```text
[ ESP8266 Gate IoT ] <---- (Wi-Fi) ----> [ LAPTOP (Server Laravel 13) ] <---- (Wi-Fi) ----> [ HP / EMULATOR (Mobile App) ]
  (Palang, Servo, RFID)                       (MySQL & REST API)                           (Aplikasi Flutter Client & Owner)
```

1. **Laptop (Backend Server Laravel 13):** Pusat database MySQL (`iot_parking_db`), logika tarif & slot parkir, serta penyedia seluruh REST API JSON melalui [`ClientApiController.php`](file:///c:/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/ClientApiController.php).
2. **ESP8266 (Hardware Gerbang):** Hanya berkomunikasi ke endpoint hardware (`/api/scan`, `/api/ticket`, `/api/override`, `/api/telemetry`) dan tidak terlibat dalam autentikasi mobile.
3. **Aplikasi Mobile (Flutter):** Berkomunikasi langsung ke backend Laravel melalui Wi-Fi/LAN (atau `10.0.2.2` pada Android Emulator).

### Menjalankan Server Backend untuk Mobile
Karena proyek menggunakan **Laravel 13 (PHP 8.5)**, jalankan server dari folder `E-Parkir_Web_Laravel`:
1. Di **XAMPP Control Panel**: pastikan **MySQL = START** dan **Apache = STOP** (agar port `80` tidak bentrok).
2. Pastikan migrasi database sudah up to date:
   ```powershell
   php artisan migrate
   ```
3. Jalankan server agar bisa diakses HP dalam satu jaringan Wi-Fi:
   ```powershell
   php artisan serve --host=0.0.0.0 --port=80
   ```

### Pilihan Base URL
Backend mendukung **URL modern** maupun **URL kompatibilitas XAMPP** secara otomatis melalui [`routes/api.php`](file:///c:/E-Parkir/E-Parkir_Web_Laravel/routes/api.php) dan [`routes/web.php`](file:///c:/E-Parkir/E-Parkir_Web_Laravel/routes/web.php):

| Lingkungan Pengujian | Base URL yang Disarankan |
| :--- | :--- |
| **HP Fisik via Wi-Fi / AP ESP (`192.168.4.2`)** | `http://192.168.4.2/api` |
| **HP Fisik (Legacy Path XAMPP)** | `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api` |
| **Android Emulator (AVD)** | `http://10.0.2.2/api` |
| **Lokal di Laptop** | `http://127.0.0.1/api` |

> **Catatan:** CORS (`paths => ['*']`), preflight `OPTIONS`, dan pengecualian CSRF untuk semua rute API/mobile sudah aktif di [`config/cors.php`](file:///c:/E-Parkir/E-Parkir_Web_Laravel/config/cors.php) dan [`bootstrap/app.php`](file:///c:/E-Parkir/E-Parkir_Web_Laravel/bootstrap/app.php).

---

## 🔑 2. Mekanisme Autentikasi & Token Mobile

* Saat `login`, `register`, atau `google` berhasil, server mengembalikan `token` (`base64` dari `user_id|email|timestamp`).
* Simpan `token` dan `user.id` di local storage Flutter, lalu kirimkan pada request berikutnya lewat header:
  ```text
  Authorization: Bearer <token>
  Accept: application/json
  ```
  *(Alternatif: kirim parameter `user_id` di query/body).*
* **Penanganan Akun Dihapus Admin:** Endpoint `status`, `profile`, dan `update_profile` mengembalikan **HTTP `404`** dengan payload `"account_deleted": true` jika akun sudah dihapus admin dari portal web. Aplikasi mobile sebaiknya langsung menghapus sesi lokal dan mengarahkan user kembali ke layar login.

---

## 🪪 3. Alur Pendaftaran Member & Kartu RFID

1. **Daftar di Mobile (`register` atau `google`):**
   * Backend membuat akun di tabel `users` dengan `role = client` **tanpa membuat kartu RFID virtual**.
   * Respon mengembalikan:
     * `has_card: false`
     * `pending_card: true`
     * `card_status: "pending_card"`
     * `card`: objek fallback aman (`id: 0`, `uid: "-"`, `balance: 0`, `balance_formatted: "Rp 0"`, `status: "inactive"`).
2. **Pembuatan Kartu di Web Admin (`/admin/cards`):**
   * Pendaftar muncul di panel **Pendaftar Member dari Mobile**.
   * Admin men-tap kartu RFID fisik (atau input UID manual) lewat tombol **Buatkan Kartu**.
3. **Sinkronisasi Otomatis ke Mobile:**
   * Setelah kartu dibuat admin, endpoint `login`, `google`, `status`, dan `profile` otomatis mengembalikan `has_card: true`, `pending_card: false`, `card_status: "active"`, serta `uid` dan `balance` kartu asli.

---

## 📡 4. Daftar Lengkap Endpoint Mobile (`ClientApiController`)

Seluruh endpoint dapat dipanggil dengan gaya **RESTful Path** (`/api/client/<endpoint>`) maupun **Action Query** (`/api/client?action=<endpoint>` atau `/api/client.php?action=<endpoint>`).

| Fitur | Method | Path Utama | Alternatif `action` |
| :--- | :--- | :--- | :--- |
| **Login Email/Password** | `POST` | `/api/client/login` | `?action=login` |
| **Register Pengendara** | `POST` | `/api/client/register` | `?action=register` |
| **Login / Register Google** | `POST` | `/api/client/google` | `?action=google` / `google_login` / `google_register` |
| **Status & Dashboard Client** | `GET` / `POST` | `/api/client/status` | `?action=status` |
| **Ketersediaan Slot Parkir** | `GET` / `POST` | `/api/client/slots` atau `/api/status` | `?action=slots` |
| **Riwayat Transaksi Client** | `GET` / `POST` | `/api/client/history` | `?action=history` |
| **Top Up Saldo Kartu** | `POST` | `/api/client/topup` | `?action=topup` |
| **Profil Pengguna** | `GET` / `POST` | `/api/client/profile` | `?action=profile` |
| **Update Profil & Foto** | `POST` | `/api/client/update_profile` | `?action=update_profile` |
| **Cek Tagihan Karcis** | `GET` / `POST` | `/api/client?action=check_ticket` | `?action=check_ticket` |
| **Bayar Karcis Pakai Saldo** | `POST` | `/api/client?action=pay_ticket` | `?action=pay_ticket` |
| **Dashboard Owner** | `GET` / `POST` | `/api/client/owner_dashboard` atau `/api/owner/dashboard` | `?action=owner_dashboard` |
| **Laporan Transaksi Owner** | `GET` / `POST` | `/api/client/owner_reports` atau `/api/owner/reports` | `?action=owner_reports` |

---

### A. Login Email & Password (`POST /api/client/login`)
* **Request Body:**
  ```json
  {
    "email": "budi@gmail.com",
    "password": "password123"
  }
  ```
* **Response (`200 OK`):** Mengembalikan `status`, `message`, `token`, `has_card`, `pending_card`, `card_status`, `user`, `card`, dan wrapper `data` yang berisi field identik.
* **Error (`400` / `401`):** Email/password kosong atau tidak cocok.

---

### B. Register Pengendara (`POST /api/client/register`)
* **Request Body:**
  ```json
  {
    "name": "Budi Santoso",
    "email": "budi@gmail.com",
    "password": "password123",
    "phone": "081234567890",
    "license_plate": "B 1234 XYZ"
  }
  ```
  * Wajib: `name`, `email` (unik), `password` (minimal 6 karakter).
  * Opsional: `phone`, `license_plate` (otomatis di-uppercase).
* **Response (`201 Created`):** `has_card: false`, `pending_card: true`, `card_status: "pending_card"`, lengkap dengan `token`, `user`, `card`, dan `data`.
* **Error (`409 Conflict`):** Email sudah terdaftar.

---

### C. Login & Auto-Register dengan Google (`POST /api/client/google`)
Mendukung autentikasi satu pintu (Google Sign-In):
* Jika email / `google_id` sudah ada → langsung login (`200 OK`, `is_new_user: false`).
* Jika belum ada → otomatis membuat akun `role = client` dengan `auth_provider = "google"` dan `pending_card = true` (`201 Created`, `is_new_user: true`).
* Jika `phone` atau `license_plate` dikirim dan data user lama masih kosong, field tersebut ikut dilengkapi.

#### Request Body
```json
{
  "id_token": "<googleAuth.idToken>",
  "access_token": "<googleAuth.accessToken>",
  "phone": "081234567890",
  "license_plate": "B 1234 XYZ"
}
```
> Minimal salah satu dari `id_token` *(disarankan)* atau `access_token` wajib dikirim (alias `idToken`, `credential`, `accessToken` juga diterima).

#### Contoh Response Sukses (`201 Created` untuk user baru / `200 OK` untuk user lama)
```json
{
  "status": "success",
  "message": "Pendaftaran dengan Google berhasil! Data Anda telah masuk ke admin untuk pembuatan kartu member RFID.",
  "is_new_user": true,
  "auth_provider": "google",
  "token": "MTAwNHxidWRpQGdtYWlsLmNvbXwxNzk0NDEyMDAw",
  "has_card": false,
  "pending_card": true,
  "card_status": "pending_card",
  "user": {
    "id": 1004,
    "name": "Budi Santoso",
    "email": "budi@gmail.com",
    "phone": "081234567890",
    "license_plate": "B 1234 XYZ",
    "avatar": "https://lh3.googleusercontent.com/a/...",
    "avatar_url": "https://lh3.googleusercontent.com/a/...",
    "auth_provider": "google",
    "can_change_name": true,
    "name_changes_count": 0,
    "name_changed_at": null,
    "role": "client"
  },
  "card": {
    "id": 0,
    "uid": "-",
    "license_plate": "B 1234 XYZ",
    "balance": 0,
    "balance_formatted": "Rp 0",
    "status": "inactive"
  },
  "data": {
    "token": "MTAwNHxidWRpQGdtYWlsLmNvbXwxNzk0NDEyMDAw",
    "has_card": false,
    "pending_card": true,
    "card_status": "pending_card",
    "is_new_user": true,
    "auth_provider": "google",
    "user": { "...": "sama seperti di atas" },
    "card": { "...": "sama seperti di atas" }
  }
}
```

#### Konfigurasi `.env` Server & Google Cloud Console
1. Buat OAuth 2.0 Client ID di **Google Cloud Console** untuk **Web application** (dipakai sebagai `serverClientId` di Flutter) dan **Android** (isi package name + SHA-1 keystore).
2. Isi variabel berikut di `.env` Laravel:
   ```env
   GOOGLE_CLIENT_ID=<Web_OAuth_Client_ID>
   GOOGLE_CLIENT_SECRET=<Web_OAuth_Client_Secret>
   GOOGLE_ALLOWED_CLIENT_IDS=<Android_Client_ID>,<iOS_Client_ID>
   ```
   > **Catatan:** Backend memverifikasi token Google secara IPv4 ke `oauth2.googleapis.com` / `openidconnect.googleapis.com` dan mencocokkan `aud`/`azp` dengan `GOOGLE_CLIENT_ID` atau `GOOGLE_ALLOWED_CLIENT_IDS`. Error `401` muncul jika token kedaluwarsa, email belum terverifikasi, atau Client ID tidak cocok.

---

### D. Status Realtime Pengendara (`GET /api/client/status`)
Mengambil profil, status kartu, transaksi parkir yang sedang berjalan (`active_transaction`), 5 riwayat terakhir (`history`), statistik ringkas (`stats`), dan okupansi slot per lantai (`parking_slots`).

* **Parameter:** Header `Authorization: Bearer <token>`, atau query `user_id`, atau `uid`.
* **Field Penting pada `active_transaction` (bernilai `null` jika tidak sedang parkir):**
  * `id`, `ticket_code`, `parking_floor`, `parking_floor_label`
  * `tap_in_time`, `elapsed_seconds`, `elapsed_minutes`, `elapsed_formatted` (`HH:MM:SS`)
  * `billed_hours`, `rate_per_hour` (`2000`), `estimated_fee`, `estimated_fee_formatted`
  * `gate_in_code`, `gate_in_name`, `gate_in_location`
  * `is_balance_sufficient`, `remaining_balance_after`

---

### E. Ketersediaan Slot Parkir (`GET /api/client/slots` atau `GET /api/status`)
* **Kapasitas Default:** Total `20` slot (`10` slot Lantai 1 + `10` slot Lantai 2), tarif `Rp 2.000 / jam`.
* **Field Utama:** `capacity`, `parked_count`, `available_slots`, `occupancy_percentage`, `is_full`, `rate_per_hour`, `floors.lt1`, `floors.lt2`, `lt1_available`, `lt1_parked`, `lt2_available`, `lt2_parked`.

---

### F. Riwayat Transaksi (`GET /api/client/history`)
* Mengembalikan maksimal 30 transaksi `COMPLETED` terbaru milik user (`history` dan `data`).
* Setiap item berisi `id`, `ticket_code`, `is_ticket`, `access_type` (`KARCIS`/`RFID`), `tap_in_time`, `tap_out_time`, `tap_in_formatted`, `tap_out_formatted`, `duration_minutes`, `duration_formatted`, `total_fee`, `total_fee_formatted`, `balance_before`, `balance_after`, `payment_method`, `payment_method_label`, `gate_in_code`, `gate_out_code`.

---

### G. Top Up Saldo Kartu (`POST /api/client/topup`)
* **Request Body:** `amount` (integer, minimal `1000`), ditambah `Bearer <token>` / `user_id` / `uid`.
* **Error `404`:** Jika kartu member belum dibuatkan admin (`pending_card`).
* **Response (`200 OK`):** `card_id`, `uid`, `amount_added`, `balance_before`, `balance_after`, `balance_formatted`.

---

### H. Profil & Update Profil (`GET /api/client/profile`, `POST /api/client/update_profile`)
* **Aturan Update Profil (`update_profile`):**
  * `name`: **Hanya dapat diubah 1 kali seumur hidup** (`can_change_name == true` / `name_changes_count < 1`). Percobaan kedua mengembalikan **`422 Unprocessable Entity`**.
  * `phone`: Bebas diubah.
  * `license_plate`: Bebas diubah (otomatis di-uppercase dan ikut memperbarui `license_plate` pada kartu RFID jika kartu sudah ada).
  * **Foto Profil:** Mendukung upload multipart (`avatar`, `photo`, `profile_photo`, atau `image` — format `jpg/jpeg/png/webp`, maks. `5MB`) **atau** string Base64 (`avatar_base64`, `photo_base64`, atau `photo` dengan prefix `data:image/...;base64,...`). Foto disimpan ke `public/uploads/avatars/` dan diakses lewat `user.avatar_url`.

---

### I. Cek & Bayar Karcis Parkir via Saldo Member (`check_ticket` & `pay_ticket`)
Digunakan oleh member yang masuk menggunakan karcis fisik tetapi ingin membayar langsung dari saldo kartu RFID melalui aplikasi mobile:
1. **Cek Tagihan (`GET/POST /api/client?action=check_ticket&ticket_code=LT1-...`):**
   * Mengembalikan `parking_floor`, `tap_in_time`, `duration_minutes`, `billed_hours`, `total_fee`, `total_fee_formatted`.
2. **Bayar Karcis (`POST /api/client?action=pay_ticket`):**
   * **Body:** `{ "ticket_code": "LT1-...", "user_id": 1004 }`
   * Memvalidasi kartu RFID user berstatus `active` dan saldo mencukupi, memotong saldo kartu, mengubah transaksi menjadi `COMPLETED` (`payment_method = card`), mengaktifkan `manual_override = 1` pada gerbang agar palang keluar terbuka, serta mengembalikan `balance_after_formatted` dan `receipt_url`.

---

### J. Endpoint Portal Owner (`owner_dashboard` & `owner_reports`)

1. **Dashboard Owner (`GET /api/client/owner_dashboard` atau `/api/owner/dashboard`):**
   * Mengembalikan ringkasan finansial (`total_revenue`, `today_revenue`, `week_revenue`, `month_revenue` + versi `*_formatted`), statistik transaksi (`total_transactions`, `today_transactions`, `completed_transactions`, `avg_duration_minutes`, `avg_fee`), okupansi slot (`parked_count`, `available_slots`, `capacity`, `occupancy_percentage`, `floors.lt1`, `floors.lt2`, `floor_list`), grafik 7 hari terakhir (`charts`, `chart_labels`, `chart_values`), dan `recent_transactions`.
   * Seluruh metrik utama tersedia di **root JSON** maupun di dalam **`json['data']`**.

2. **Laporan Transaksi Owner (`GET /api/client/owner_reports` atau `/api/owner/reports`):**
   * **Query Parameter Opsional:**
     * `preset`: `all` *(default)*, `today`, `yesterday`, `week`, `month`
     * `start_date` & `end_date`: format `YYYY-MM-DD`
     * `status`: `all`, `COMPLETED`, `PARKED`
     * `access_type`: `all`, `ticket`, `card`
     * `floor`: `all`, `1`, `2`
     * `search`: cari ID transaksi, kode karcis, nama/HP user, UID kartu, atau plat nomor
     * `limit`: `1`–`500` *(default `100`)*
   * **Response (`200 OK`):** `total_records`, `total_revenue`, `total_revenue_formatted`, `transactions`, `reports`, `data`, dan `stats`.

---

## 💻 5. Praktik Wajib Parsing Model di Flutter (Dart)

Agar model Dart tidak crash saat parsing JSON dari backend:

1. **Selalu gunakan `num?` untuk angka:**
   ```dart
   final int totalRevenue = (json['total_revenue'] as num?)?.toInt() ?? 0;
   final double fee = (json['total_fee'] as num?)?.toDouble() ?? 0.0;
   ```
2. **Jadikan field waktu keluar / opsional sebagai nullable:**
   Kendaraan yang masih `PARKED` memiliki `tap_out_time == null`, dan `active_transaction` bernilai `null` saat tidak sedang parkir.
   ```dart
   final String? tapOutTime = json['tap_out_time']?.toString();
   final Map<String, dynamic>? activeTx = json['active_transaction'] as Map<String, dynamic>?;
   ```
3. **Dukung pembacaan root maupun `json['data']`:**
   ```dart
   final Map<String, dynamic> payload = (rawJson['data'] is Map<String, dynamic>)
       ? rawJson['data'] as Map<String, dynamic>
       : rawJson;
   ```

### Contoh Implementasi Google Sign-In di Flutter (`google_sign_in`)
```dart
import 'dart:convert';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;

final GoogleSignIn _googleSignIn = GoogleSignIn(
  scopes: ['email', 'profile'],
  serverClientId: '<WEB_OAUTH_CLIENT_ID_DARI_GOOGLE_CLOUD>',
);

Future<Map<String, dynamic>> loginOrRegisterWithGoogle({
  String baseUrl = 'http://192.168.4.2/api',
  String? phone,
  String? licensePlate,
}) async {
  final GoogleSignInAccount? account = await _googleSignIn.signIn();
  if (account == null) {
    throw Exception('Login Google dibatalkan oleh pengguna.');
  }

  final GoogleSignInAuthentication auth = await account.authentication;

  final response = await http.post(
    Uri.parse('$baseUrl/client/google'),
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode({
      'id_token': auth.idToken,
      'access_token': auth.accessToken,
      if (phone != null && phone.isNotEmpty) 'phone': phone,
      if (licensePlate != null && licensePlate.isNotEmpty) 'license_plate': licensePlate,
    }),
  );

  final body = jsonDecode(response.body) as Map<String, dynamic>;
  if (response.statusCode == 200 || response.statusCode == 201) {
    return body;
  }

  throw Exception(body['message'] ?? 'Gagal login dengan Google.');
}
```

---

## 🛠️ 6. Troubleshooting Cepat Mobile

* **Tidak bisa konek dari HP ke Laptop:** Pastikan HP dan Laptop berada di jaringan Wi-Fi yang sama, server dijalankan dengan `--host=0.0.0.0 --port=80`, dan Windows Firewall mengizinkan PHP/port `80`.
* **Google Sign-In gagal `401` (`Client ID Google tidak cocok`):** Pastikan `serverClientId` di Flutter menggunakan **Web OAuth Client ID**, dan di `.env` Laravel sudah terisi `GOOGLE_CLIENT_ID` serta `GOOGLE_ALLOWED_CLIENT_IDS` (berisi Android/iOS Client ID jika dibutuhkan).
* **Error `404` dengan `account_deleted: true`:** Akun user tersebut sudah dihapus admin melalui web; bersihkan token lokal di aplikasi mobile dan arahkan ke halaman login.
