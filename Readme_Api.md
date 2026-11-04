# 📡 Dokumentasi Endpoint REST API E-Parkir IoT (Laravel Backend)

Seluruh endpoint API sistem E-Parkir IoT berada **murni di dalam proyek Laravel** (`E-Parkir_Web_Laravel`), terdaftar resmi pada rute Laravel [`routes/api.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/routes/api.php), dan ditangani langsung oleh Controller:
- [`app/Http/Controllers/Api/ClientApiController.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/ClientApiController.php)
- [`app/Http/Controllers/Api/ScanController.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/ScanController.php)
- [`app/Http/Controllers/Api/OverrideController.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/OverrideController.php)
- [`app/Http/Controllers/Api/TelemetryController.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/TelemetryController.php)

> **Catatan Arsitektur:** Tidak ada folder atau file script PHP di luar folder Laravel. Struktur direktori bersih dan terpusat di dalam `E-Parkir_Web_Laravel`.

---

## 🌐 Base URL API

Tergantung pada konfigurasi jaringan Anda:
- **Jaringan Wi-Fi ESP8266 SoftAP (Default):**
  - Standar Laravel: `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api`
  - Shorthand Apache: `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/api`
- **Testing Lokal di Laptop:**
  - `http://127.0.0.1:8000/api` (jika menggunakan `php artisan serve`)
  - `http://127.0.0.1/E-Parkir/E-Parkir_Web_Laravel/public/api` (melalui XAMPP Apache)

---

## 📱 1. Endpoint Aplikasi Mobile Pengendara (Client)

Aplikasi mobile mendukung dua jenis pemanggilan rute:
1. **Model URL Parameter (Legacy Compatible):**  
   `POST/GET /api/client?action={nama_action}` atau `/api/client.php?action={nama_action}`
2. **Model RESTful Path (Modern):**  
   `POST/GET /api/client/{nama_action}`

---

### A. Autentikasi Pengendara (Login)
* **URL:** `/api/client?action=login` atau `/api/client/login`
* **Method:** `POST`
* **Content-Type:** `application/json` atau `application/x-www-form-urlencoded`
* **Request Body:**
  ```json
  {
    "email": "tester@gmail.com",
    "password": "password123"
  }
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Login berhasil!",
    "token": "MTAwM3x0ZXN0ZXJAZ21haWwuY29tfDE3OTM2ODQ4",
    "user": {
      "id": 1003,
      "name": "tester",
      "email": "tester@gmail.com",
      "phone": "08123456789",
      "role": "client"
    },
    "card": {
      "id": 16,
      "uid": "A1E29566",
      "license_plate": "B 1234 XYZ",
      "balance": 36000,
      "balance_formatted": "Rp 36.000",
      "status": "active"
    }
  }
  ```

---

### B. Cek Status Ketersediaan Slot Parkir (Lantai 1 & Lantai 2)
Endpoint ini digunakan aplikasi mobile untuk menampilkan status slot parkir realtime, kapasitas per lantai, dan apakah area parkir sudah penuh.

* **URL:** `/api/client?action=slots` atau `/api/client/slots` atau `/api/status`
* **Method:** `GET`
* **Response (200 OK):**
  ```json
  {
    "status": "success",
    "total_capacity": 20,
    "capacity": 20,
    "parked_count": 10,
    "available_slots": 10,
    "occupancy_percentage": 50,
    "rate_per_hour": 2000,
    "is_full": false,
    "floors": {
      "lt1": {
        "floor_number": 1,
        "floor_code": "LT 1",
        "capacity": 10,
        "parked": 10,
        "available": 0,
        "is_full": true
      },
      "lt2": {
        "floor_number": 2,
        "floor_code": "LT 2",
        "capacity": 10,
        "parked": 0,
        "available": 10,
        "is_full": false
      }
    },
    "lt1_available": 0,
    "lt1_parked": 10,
    "lt1_capacity": 10,
    "lt2_available": 10,
    "lt2_parked": 0,
    "lt2_capacity": 10
  }
  ```

---

### C. Status Realtime Kendaraan & Saldo Pengendara
Mengambil status apakah kendaraan pengendara sedang parkir di dalam, menghitung durasi berjalan, serta estimasi biaya parkir realtime.

* **URL:** `/api/client?action=status&user_id={id}` atau `/api/client/status?user_id={id}`
* **Method:** `GET`
* **Response (200 OK):**
  ```json
  {
    "status": "success",
    "server_time": "2026-11-03 17:15:00",
    "is_parked": true,
    "status_code": "PARKED",
    "status_label": "Sedang Parkir",
    "user": {
      "id": 1003,
      "name": "tester",
      "email": "tester@gmail.com"
    },
    "card": {
      "uid": "A1E29566",
      "balance": 36000,
      "balance_formatted": "Rp 36.000"
    },
    "active_transaction": {
      "id": 149,
      "ticket_code": "LT1-261103-8A2F",
      "tap_in_time": "2026-11-03 16:30:00",
      "elapsed_minutes": 45,
      "elapsed_formatted": "00:45:00",
      "billed_hours": 1,
      "rate_per_hour": 2000,
      "estimated_fee": 2000,
      "estimated_fee_formatted": "Rp 2.000",
      "is_balance_sufficient": true,
      "remaining_balance_after": 34000
    },
    "parking_slots": {
      "total_capacity": 20,
      "available_slots": 10,
      "floors": {
        "lt1": { "capacity": 10, "parked": 10, "available": 0 },
        "lt2": { "capacity": 10, "parked": 0, "available": 10 }
      }
    }
  }
  ```

---

### D. Riwayat Transaksi Parkir (History)
* **URL:** `/api/client?action=history&user_id={id}` atau `/api/client/history?user_id={id}`
* **Method:** `GET`
* **Response:** Mengembalikan daftar 30 transaksi parkir terakhir milik pengendara lengkap dengan durasi dan tarif.

---

### E. Top Up Saldo Kartu RFID
* **URL:** `/api/client?action=topup` atau `/api/client/topup`
* **Method:** `POST`
* **Request Body:**
  ```json
  {
    "user_id": 1003,
    "amount": 50000
  }
  ```

---

## 🤖 2. Endpoint Perangkat Keras Hardware IoT (ESP8266)

### A. Tap RFID Gerbang (Scan Masuk / Keluar)
* **URL:** `/api/scan` atau `/api/scan.php`
* **Method:** `POST` atau `GET`
* **Query/Body:**
  - `uid` : UID Kartu RFID (Contoh: `A1E29566`)
  - `device_code` : Kode gerbang (Contoh: `GATE_MASUK_01` atau `Gate-01`)
  - `type` : `entry` atau `exit` (opsional, auto-detect berdasarkan riwayat)

---

### B. Pencet Tombol Tiket Karcis Manual di Gerbang
Dipanggil saat pengendara tanpa RFID menekan tombol push-button di gerbang masuk. Sistem otomatis mengalokasikan lantai (**LT 1** jika masih tersedia, atau **LT 2** jika LT 1 penuh).
* **URL:** `/api/ticket` atau `/api/ticket.php`
* **Method:** `POST` atau `GET`
* **Query/Body:**
  - `device_code` : `GATE_MASUK_01`
* **Response (201 Created):**
  ```json
  {
    "status": "success",
    "action": "OPEN_GATE",
    "parking_floor": 1,
    "floor_code": "LT 1",
    "ticket_code": "LT1-261103-9F12",
    "gate_status": "OPEN",
    "sound_buzzer": true,
    "message": "Karcis berhasil diterbitkan untuk Lantai 1 (LT 1). Silakan masuk!"
  }
  ```

---

### C. Emergency Override Gerbang (Buka Paksa)
* **URL:** `/api/override` atau `/api/override.php`
* **Method:** `GET` (Dipolling oleh ESP8266 secara berkala)
* **Query:** `check=1&device_code=GATE_MASUK_01`
* **Response:**
  - Jika normal: `{"action": "NONE", "message": "Tidak ada perintah darurat"}`
  - Jika admin menekan Buka Gerbang di Web: `{"action": "OPEN_GATE", "gate_status": "OPEN"}`

---

## 🛠️ Ringkasan untuk Developer Mobile App

Jika Anda memperbarui konfigurasi di aplikasi mobile (Flutter / Dart / Kotlin / Java):
1. **Ubah `baseUrl` aplikasi mobile menjadi:**
   ```dart
   // Mengarah langsung ke public api Laravel
   static const String baseUrl = "http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api";
   ```
2. Endpoint login:
   ```dart
   "$baseUrl/client/login"
   // atau
   "$baseUrl/client?action=login"
   ```
3. Endpoint slot 2 lantai:
   ```dart
   "$baseUrl/client/slots"
   // Akses response['floors']['lt1']['available'] dan response['floors']['lt2']['available']
   ```

