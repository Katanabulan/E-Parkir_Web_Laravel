# 📘 Dokumentasi Upgrade Laravel 13, Arsitektur Database & Panduan Koneksi IoT/Mobile

Dokumen ini merangkum seluruh pembaruan sistem pada project **E-Parkir IoT (Sigma Boys)**, mencakup upgrade ke **Laravel 13**, perapian struktur migrasi 5 tabel utama, serta panduan operasional server agar mikrokontroler **ESP8266** dan **Aplikasi Mobile** dapat berkomunikasi tanpa error.

---

## 🚀 1. Ringkasan Upgrade Lingkungan (Stack)

| Komponen | Versi Lama | Versi Baru | Keterangan |
| :--- | :--- | :--- | :--- |
| **Laravel Framework** | `12.70.1` | **`13.40.0`** | Versi major terbaru, arsitektur lean & cepat |
| **Laravel Tinker** | `2.11.1` | **`3.0.2`** | REPL konsol interaktif Laravel 13 |
| **PHP Binary (CLI)** | `8.2.12` | **`8.5.12`** (`C:\php-8.5.12-1`) | Telah didaftarkan ke System PATH Windows |
| **Ekstensi PHP Aktif** | - | `pdo_mysql`, `fileinfo`, `gd`, `zip`, `curl`, `mbstring`, `openssl`, `pdo_sqlite` | Diaktifkan di `C:\php-8.5.12-1\php.ini` |
| **Status Automated Test** | - | **3 Passed (10 assertions)** | Semua unit & feature test lulus |

---

## 🗄️ 2. Struktur Database (5 Tabel Utama)

File migrasi database telah dirapikan sehingga **hanya berfokus pada 5 tabel inti** yang benar-benar digunakan sistem, membuang tabel default Laravel yang tidak terpakai (seperti `cache`, `jobs`, `failed_jobs`, `sessions`, `password_reset_tokens`).

### Daftar File Migrasi:
1. **[`0001_01_01_000000_create_users_table.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/database/migrations/0001_01_01_000000_create_users_table.php)**
   * **Tabel:** `users`
   * **Fungsi:** Menyimpan akun pengelola dan pengendara dengan role `admin`, `client`, dan `owner`.
2. **[`0001_01_01_000001_create_devices_table.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/database/migrations/0001_01_01_000001_create_devices_table.php)**
   * **Tabel:** `devices`
   * **Fungsi:** Menyimpan data gerbang masuk/keluar IoT ESP8266 (`GATE_MASUK_01`), status online/offline, dan emergency override.
3. **[`0001_01_01_000002_create_rfid_cards_table.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/database/migrations/0001_01_01_000002_create_rfid_cards_table.php)**
   * **Tabel:** `rfid_cards`
   * **Fungsi:** Menyimpan UID fisik kartu RFID, plat nomor kendaraan, saldo e-money, dan status aktif/blokir.
4. **[`0001_01_01_000003_create_parking_transactions_table.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/database/migrations/0001_01_01_000003_create_parking_transactions_table.php)**
   * **Tabel:** `parking_transactions`
   * **Fungsi:** Pencatatan waktu tap-in & tap-out, nomor tiket karcis, lantai parkir, kalkulasi durasi, tarif biaya, dan status parkir (`PARKED`/`COMPLETED`).
5. **[`0001_01_01_000004_create_access_logs_table.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/database/migrations/0001_01_01_000004_create_access_logs_table.php)**
   * **Tabel:** `access_logs`
   * **Fungsi:** Audit trail keamanan dan riwayat setiap kali ada kartu RFID atau tiket yang di-scan pada gate.

---

## ⚡ 3. Panduan Menjalankan Server (Operasional Harian)

Karena Laravel 13 membutuhkan PHP modern (PHP 8.5) sedangkan Apache bawaan XAMPP masih menggunakan PHP 8.2, server dijalankan langsung menggunakan perintah artisan:

### Langkah Menjalankan:
1. Buka **XAMPP Control Panel**:
   * **Apache:** Pastikan berstatus **STOP** *(agar Port 80 tidak bentrok)*.
   * **MySQL:** Pastikan berstatus **START** *(database tetap dibutuhkan)*.
2. Buka terminal di folder project `E-Parkir_Web_Laravel`, lalu jalankan:
   ```powershell
   php artisan serve --host=0.0.0.0 --port=80
   ```

### Mengapa Harus `--host=0.0.0.0 --port=80`?
* `--host=0.0.0.0`: Membuka akses jaringan agar perangkat luar (ESP8266 & HP yang terhubung via Wi-Fi) diizinkan mengakses server. Jika hanya memakai `127.0.0.1`, server hanya bisa dibuka dari laptop sendiri.
* `--port=80`: Port HTTP standar yang dicari oleh mikrokontroler ESP8266 dan aplikasi mobile.

---

## 📱 4. Kompatibilitas ESP8266 & Aplikasi Mobile

Tidak ada kode pada firmware ESP8266 maupun kodingan Mobile yang perlu diubah. Seluruh penyesuaian telah diatasi langsung di sisi backend Laravel:

### A. Rute Kompatibilitas Subfolder (URL Fallback)
Aplikasi mobile dan ESP sebelumnya mengakses URL berbasis direktori XAMPP. Laravel telah dikonfigurasikan di [`routes/web.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/routes/web.php) agar mengenali semua varian URL berikut secara otomatis:
* `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api/...`
* `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/api/...`
* `http://192.168.4.2/E-Parkir/api/...`
* `http://192.168.4.2/api/...`
* `http://192.168.4.2/client/...`

### B. Konfigurasi CORS Penuh
File [`config/cors.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/config/cors.php) telah dipasang dengan aturan global (`paths => ['*']`) untuk memastikan aplikasi Flutter / React Native / Android tidak diblokir oleh kebijakan keamanan *Cross-Origin Resource Sharing*.

### C. Penanganan Preflight `OPTIONS`
Controller [`ClientApiController.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/app/Http/Controllers/Api/ClientApiController.php) telah dilengkapi dengan pengecekan metode HTTP `OPTIONS`, sehingga permintaan verifikasi dari klien mobile langsung dijawab dengan status `200 OK`.

### D. Pengecualian Proteksi CSRF
Di [`bootstrap/app.php`](file:///c:/xampp/htdocs/E-Parkir/E-Parkir_Web_Laravel/bootstrap/app.php), seluruh endpoint mobile dan API eksternal telah dimasukkan ke dalam daftar *whitelist* CSRF token, mencegah error `419 Page Expired` saat login via mobile.

---

## 🛠️ 5. Troubleshooting Cepat

* **Kendala: Error "Address already in use" saat menjalankan serve**
  * *Solusi:* Apache di XAMPP masih aktif. Klik tombol **Stop** pada modul Apache di XAMPP Control Panel.
* **Kendala: Perintah `php` masih menampilkan versi 8.2 lama**
  * *Solusi:* Tutup jendela terminal dan buka jendela terminal yang baru agar membaca PATH PHP 8.5.
* **Kendala: HP tidak bisa terhubung ke server**
  * *Solusi:* Pastikan HP sudah terhubung ke jaringan Wi-Fi yang sama dengan laptop (misalnya Wi-Fi Access Point ESP `Parkir-IoT-Gate` atau router yang sama).

