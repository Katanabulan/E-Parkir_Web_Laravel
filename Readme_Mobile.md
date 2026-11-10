# 📱 Panduan Integrasi Aplikasi Mobile (Flutter) & Backend E-Parkir IoT

Dokumen ini merupakan panduan teknis resmi bagi pengembang aplikasi mobile (Flutter / Dart / Android / iOS) untuk menghubungkan aplikasi dengan backend server Laravel E-Parkir IoT.

---

## 🌐 1. Arsitektur Sistem & Peran Perangkat

Sistem E-Parkir IoT terdiri dari 3 komponen utama yang bekerja secara terpisah:

```
[ ESP8266 Gate IoT ] <---- (Wi-Fi) ----> [ LAPTOP (Server Laravel) ] <---- (Wi-Fi) ----> [ HP / EMULATOR (Mobile App) ]
  (Palang, Servo, RFID)                      (Database & REST API)                       (Aplikasi Flutter Pengendara & Owner)
```

1. **Laptop (Backend Server Laravel - `192.168.4.2:80`)**:
   - Berperan sebagai pusat database (MySQL), pengelola bisnis logic parkir, dan penyedia seluruh REST API JSON.
2. **ESP8266 (Hardware Controller Gerbang Fisik)**:
   - Mengontrol servo palang pintu, membaca kartu RFID di pos masuk/keluar, dan membunyikan buzzer.
   - Hanya memanggil endpoint hardware: `/api/scan` dan `/api/override`.
   - **TIDAK TERLIBAT** dalam komunikasi data akun pengendara atau dashboard Owner di HP.
   - ⚠️ **Firmware ESP8266 sama sekali TIDAK PERLU DIUBAH.**
3. **Aplikasi Mobile (Flutter)**:
   - Berkomunikasi **langsung** dengan Laptop (Server Laravel) melalui koneksi Wi-Fi ke endpoint `/api/client`.

---

## 🔍 2. Penjelasan Masalah: Mengapa Dashboard Owner Belum Muncul?

### Ringkasan Masalah:
* **Server Laptop:** **SUDAH MENGIRIM DATA DENGAN SUKSES (Status 200 OK)** setiap 5 detik. Data total transaksi (89 data), total uang (Rp 1.530.000), okupansi lantai, grafik mingguan, dan riwayat transaksi sudah terkirim lengkap.
* **Aplikasi Mobile:** **Mengalami Parsing Exception (Error Pembacaan Model di Flutter)** saat menerima JSON tersebut.
* **Akibatnya:** Ketika Flutter melempar exception di dalam blok `try { ... } catch (e) { ... }`, proses render terhenti secara diam-diam (*silent error*) dan layar dashboard dibiarkan dalam kondisi kosong / loading.

---

### Mengapa Layar "Rincian Transaksi" Bisa Terbaca, Tapi "Dashboard" Tidak?

| Halaman | Struktur Data | Karakteristik Parsing di Flutter | Status |
| :--- | :--- | :--- | :--- |
| **Rincian Transaksi** (`action=owner_reports`) | **List / Array Sederhana** `[{...}, {...}]` | Sangat mudah dibaca. Hanya me-looping daftar transaksi tabel. | ✅ **BERHASIL** |
| **Dashboard Owner** (`action=owner_dashboard`) | **Kompleks (Map, Numbers, List, Charts)** | Memiliki banyak variabel berbeda (uang, grafik, lantai, slot, stats). Sangat rentan *Type Mismatch*. | ⚠️ **PARSING ERROR DI MOBILE** |

---

## ⚠️ 3 Kesalahan Umum Deserialisasi Model di Flutter (Dart)

Flutter memiliki aturan **Strict Type Safety & Null Safety**. Tiga hal berikut adalah penyebab paling umum model Flutter gagal membaca JSON:

### 1. Kesalahan Tipe Angka (`int` vs `double`)
Di Dart, angka bulat tidak otomatis bisa di-cast menjadi `double`:
```dart
// ❌ SALAH (Akan crash jika server mengirim integer 1530000):
double totalRevenue = json['total_revenue'] as double;

// ✅ BENAR (Aman untuk integer maupun double):
double totalRevenue = (json['total_revenue'] as num?)?.toDouble() ?? 0.0;
// Atau jika menggunakan int:
int totalRevenue = (json['total_revenue'] as num?)?.toInt() ?? 0;
```

### 2. Nilai `null` pada Variabel Non-Nullable
Kendaraan yang **sedang parkir di dalam** belum memiliki waktu keluar (`tap_out_time = null`). Jika model Flutter mewajibkan String non-nullable:
```dart
// ❌ SALAH (Akan crash jika ada mobil yang masih parkir):
String tapOutTime = json['tap_out_time']; 

// ✅ BENAR (Null-safe):
String tapOutTime = json['tap_out_time'] ?? '-';
// Atau gunakan tipe nullable:
String? tapOutTime = json['tap_out_time'];
```

### 3. Model Membaca dari `json` Langsung vs `json['data']`
Beberapa arsitektur Flutter membaca dari `json['data']`, sementara sebagian lain membaca langsung dari root `json`:
```dart
// ✅ BACKEND LARAVEL SAAT INI SUDAH MENDUKUNG KEDUANYA:
// Baik Anda membaca json['total_revenue'] MAUPUN json['data']['total_revenue'],
// keduanya sudah tersedia lengkap dan identik.
```

---

## 📡 3. Spesifikasi Endpoint REST API Mobile

### Base URL:
```text
http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api
```

---

### A. Endpoint Dashboard Owner
* **Method:** `GET` atau `POST`
* **URL:** `/client?action=owner_dashboard` (atau `/client/owner_dashboard`)
* **Headers:** `Authorization: Bearer <token_owner>`

#### Contoh Respon JSON Aktual (Status 200 OK):
```json
{
  "status": "success",
  "success": true,
  "code": 200,
  "message": "Owner dashboard data fetched successfully",
  "server_time": "2026-11-05 11:43:23",
  "total_records": 89,
  "total_transactions": 89,
  "today_transactions": 1,
  "completed_transactions": 89,
  "total_revenue": 1530000,
  "total_revenue_formatted": "Rp 1.530.000",
  "today_revenue": 44000,
  "today_revenue_formatted": "Rp 44.000",
  "total_income": 1530000,
  "today_income": 44000,
  "month_revenue": 436000,
  "month_revenue_formatted": "Rp 436.000",
  "week_revenue": 1240000,
  "active_parked": 0,
  "parked_count": 0,
  "available_slots": 20,
  "empty_slots": 20,
  "capacity": 20,
  "total_capacity": 20,
  "occupancy_percentage": 0,
  "occupancy_rate": 0,
  "rate_per_hour": 2000,
  "lt1_available": 10,
  "lt1_parked": 0,
  "lt1_capacity": 10,
  "lt2_available": 10,
  "lt2_parked": 0,
  "lt2_capacity": 10,
  "floors": {
    "lt1": {
      "floor_number": 1,
      "floor_code": "LT 1",
      "name": "Lantai 1",
      "capacity": 10,
      "parked": 0,
      "available": 10,
      "is_full": false
    },
    "lt2": {
      "floor_number": 2,
      "floor_code": "LT 2",
      "name": "Lantai 2",
      "capacity": 10,
      "parked": 0,
      "available": 10,
      "is_full": false
    }
  },
  "floor_list": [
    {
      "floor_number": 1,
      "floor_code": "LT 1",
      "capacity": 10,
      "parked": 0,
      "available": 10,
      "is_full": false
    },
    {
      "floor_number": 2,
      "floor_code": "LT 2",
      "capacity": 10,
      "parked": 0,
      "available": 10,
      "is_full": false
    }
  ],
  "charts": [
    {
      "date": "2026-11-05",
      "label": "05/11",
      "day": "Kam",
      "revenue": 44000,
      "amount": 44000,
      "transactions": 1
    }
  ],
  "stats": {
    "total_records": 89,
    "total_revenue": 1530000,
    "total_revenue_formatted": "Rp 1.530.000",
    "today_revenue": 44000,
    "today_revenue_formatted": "Rp 44.000",
    "available_slots": 20,
    "parked_count": 0,
    "capacity": 20,
    "occupancy_percentage": 0
  },
  "recent_transactions": [
    {
      "id": 160,
      "ticket_code": "LT1-261104-C3B7",
      "is_ticket": true,
      "access_type": "KARCIS",
      "parking_floor": 1,
      "parking_floor_label": "Lantai 1 (LT 1)",
      "name": "Pengendara Umum",
      "license_plate": "Karcis",
      "tap_in_formatted": "04/11/2026 16:38",
      "tap_out_formatted": "04/11/2026 17:15",
      "duration_formatted": "38 menit",
      "total_fee": 2000,
      "total_fee_formatted": "Rp 2.000",
      "status": "COMPLETED",
      "payment_method": "cash"
    }
  ],
  "data": {
    "total_records": 89,
    "total_revenue": 1530000,
    "today_revenue": 44000,
    "available_slots": 20,
    "capacity": 20,
    "floors": { ... },
    "floor_list": [ ... ],
    "charts": [ ... ],
    "recent_transactions": [ ... ],
    "stats": { ... }
  }
}
```

---

### B. Endpoint Rincian Transaksi (Owner Reports)
* **Method:** `GET`
* **URL:** `/client?action=owner_reports&preset=all&limit=100`
* **Status:** ✅ **Sudah terbukti normal dan tampil di aplikasi mobile.**

---

## 💻 4. Contoh Model Flutter (Dart) yang 100% Aman & Null-Safe

Jika Anda ingin memperbaiki model di Flutter agar anti-error terhadap variasi tipe data, gunakan template model berikut:

```dart
class OwnerDashboardModel {
  final int totalRecords;
  final int totalRevenue;
  final String totalRevenueFormatted;
  final int todayRevenue;
  final String todayRevenueFormatted;
  final int availableSlots;
  final int capacity;
  final int parkedCount;
  final int occupancyPercentage;
  final List<dynamic> charts;
  final List<dynamic> recentTransactions;

  OwnerDashboardModel({
    required this.totalRecords,
    required this.totalRevenue,
    required this.totalRevenueFormatted,
    required this.todayRevenue,
    required this.todayRevenueFormatted,
    required this.availableSlots,
    required this.capacity,
    required this.parkedCount,
    required this.occupancyPercentage,
    required this.charts,
    required this.recentTransactions,
  });

  factory OwnerDashboardModel.fromJson(Map<String, dynamic> rawJson) {
    // Toleran: membaca dari root langsung atau dari wrapper 'data'
    final json = (rawJson['data'] is Map<String, dynamic>) 
        ? rawJson['data'] as Map<String, dynamic> 
        : rawJson;

    return OwnerDashboardModel(
      totalRecords: (json['total_records'] as num?)?.toInt() ?? 
                    (json['total_transactions'] as num?)?.toInt() ?? 0,
      totalRevenue: (json['total_revenue'] as num?)?.toInt() ?? 
                    (json['total_income'] as num?)?.toInt() ?? 0,
      totalRevenueFormatted: json['total_revenue_formatted']?.toString() ?? 'Rp 0',
      todayRevenue: (json['today_revenue'] as num?)?.toInt() ?? 
                    (json['today_income'] as num?)?.toInt() ?? 0,
      todayRevenueFormatted: json['today_revenue_formatted']?.toString() ?? 'Rp 0',
      availableSlots: (json['available_slots'] as num?)?.toInt() ?? 0,
      capacity: (json['capacity'] as num?)?.toInt() ?? 20,
      parkedCount: (json['parked_count'] as num?)?.toInt() ?? 0,
      occupancyPercentage: (json['occupancy_percentage'] as num?)?.toInt() ?? 0,
      charts: (json['charts'] as List<dynamic>?) ?? [],
      recentTransactions: (json['recent_transactions'] as List<dynamic>?) ?? 
                          (json['transactions'] as List<dynamic>?) ?? [],
    );
  }
}
```

---

## 🛠️ 5. Cara Cek Error di Flutter Secara Langsung

Jika data masih belum muncul di HP:
1. Buka project Flutter di **VS Code** atau **Android Studio**.
2. Jalankan aplikasi via mode Debug (`F5` atau jalankan `flutter run` di terminal).
3. Buka tab **Debug Console** di bagian bawah.
4. Buka menu Dashboard Owner di HP.
5. Anda akan melihat tulisan pesan berwarna merah. Contoh pesan error umum:
   - `type 'int' is not a subtype of type 'double'` $\rightarrow$ Model di Flutter meminta `double`, ganti parsing-nya dengan `(json['...'] as num).toDouble()`.
   - `type '_Map<String, dynamic>' is not a subtype of type 'List<dynamic>'` $\rightarrow$ Model di Flutter salah mengira object Map sebagai List array.
   - `NoSuchMethodError: The method '[]' was called on null` $\rightarrow$ Ada sub-objek yang dicari tetapi bernilai null.

Cukup kirimkan pesan error merah tersebut, maka backend atau kode mobile dapat langsung disinkronkan secara sempurna!

---

## 🪪 6. Alur Pendaftaran Member dari Mobile & Pembuatan Kartu di Web Admin

1. **Client Daftar Data Diri di Mobile (`POST /api/client/register` atau `POST /api/client?action=register`)**:
   - **Body JSON / Form**: `name`, `email`, `password` (min. 6 karakter), `phone` (opsional), `license_plate` (opsional).
   - Backend menyimpan akun ke tabel `users` (`role = client`) **tanpa langsung membuat kartu RFID virtual**.
   - Respon JSON (`201 Created`):
     - `has_card`: `false`
     - `pending_card`: `true`
     - `card_status`: `"pending_card"`
     - `card`: fallback objek aman (`id: 0`, `uid: "-"`, `balance: 0`, `status: "inactive"`).
2. **Data Masuk ke Web Admin (`/admin/cards`)**:
   - Pendaftar tampil pada panel **Pendaftar Member dari Mobile** (daftar calon member yang belum memiliki kartu RFID).
   - Admin menekan tombol **Buatkan Kartu**, men-tap kartu RFID fisik pada reader (atau input UID manual), lalu menyimpan kartu.
3. **Sinkronisasi Otomatis ke Mobile (`login`, `status`, `profile`)**:
   - Setelah kartu dibuat oleh admin, endpoint `login`, `status`, dan `profile` otomatis mengembalikan `has_card: true`, `pending_card: false`, `card_status: "active"`, serta objek `card` dengan `uid` fisik dan saldo aktif.
