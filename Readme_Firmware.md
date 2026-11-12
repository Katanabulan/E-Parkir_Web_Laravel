# 🔧 Dokumentasi Firmware ESP8266 E-Parkir IoT (`esp8266_gate_ap`)

Dokumen ini menjelaskan arsitektur, pemetaan pin hardware, alur logika, komunikasi backend, dan REST API internal dari firmware mikrokontroler **ESP8266** pada sistem **E-Parkir IoT (Sigma Boys)**.

* **File Firmware:** [`C:\E-Parkir\firmware\esp8266_gate_ap\esp8266_gate_ap.ino`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino)
* **Target Board:** NodeMCU V3 ESP8266 (`ESP-12E`)
* **Device Code Default:** `GATE_MASUK_01`

---

## 📦 1. Library yang Digunakan

Firmware membutuhkan library berikut pada Arduino IDE / PlatformIO:
* `ESP8266WiFi.h`
* `ESP8266WebServer.h`
* `ESP8266HTTPClient.h`
* `WiFiClient.h`
* `SPI.h`
* `MFRC522.h`
* `Servo.h`

---

## 📡 2. Konfigurasi Jaringan & Backend

### A. Wi-Fi Access Point (SoftAP Mandiri)
ESP8266 beroperasi dalam mode `WIFI_AP` sehingga laptop server dan perangkat lain terhubung ke jaringan Wi-Fi yang dibuat oleh mikrokontroler:
* **SSID:** `Parkir-IoT-Gate`
* **Password:** `password123`
* **IP ESP8266 / Gateway:** `192.168.4.1`
* **Subnet Mask:** `255.255.255.0`
* **Port Web Server ESP:** `80`

### B. Target Backend Server (Laptop)
* **IP Utama (`backend_ip_primary`):** `192.168.4.2` (port `80`)
* **IP Fallback (`backend_ip_fallback`):** `192.168.4.100` *(dideklarasikan di konstanta konfigurasi)*

---

## 🔌 3. Pemetaan Pin Hardware (NodeMCU V3)

| Komponen | Pin NodeMCU | GPIO | Keterangan |
| :--- | :---: | :---: | :--- |
| **RFID RC522 — RST** | `D0` | `GPIO16` | Reset modul MFRC522 |
| **RFID RC522 — SDA (SS)** | `D8` | `GPIO15` | Chip Select SPI |
| **RFID RC522 — SCK** | `D5` | `GPIO14` | SPI Clock (default) |
| **RFID RC522 — MISO** | `D6` | `GPIO12` | SPI MISO (default) |
| **RFID RC522 — MOSI** | `D7` | `GPIO13` | SPI MOSI (default) |
| **LED Merah** | `D1` | `GPIO5` | Standby / Palang tertutup |
| **LED Kuning** | `D2` | `GPIO4` | Transisi gerak / Proses cetak tiket |
| **LED Hijau** | `D3` | `GPIO0` | Palang terbuka penuh |
| **Motor Servo** | `D4` | `GPIO2` | Sinyal PWM (`500–2400 us`, `0°` tutup, `90°` buka) |
| **Buzzer** | `RX` | `GPIO3` | Indikator audio |
| **Tombol Karcis Masuk** | `TX` | `GPIO1` | Push button ke `GND` (`INPUT_PULLUP`) |

### Konfigurasi Polaritas Modul
```cpp
const bool BUZZER_ACTIVE_LOW = false;
const bool LED_ACTIVE_LOW    = false;
const bool BUTTON_ACTIVE_LOW = true;
```

---

## 🔄 4. Alur Kerja & Gerakan Palang

### A. Self-Test Saat Booting (`setup()`)
1. Menyalakan **LED Merah**, **Kuning**, lalu **Hijau** secara bergantian (masing-masing `300 ms`).
2. Membunyikan **Buzzer** 2x (`80 ms`, jeda `50 ms`).
3. Menggerakkan **Servo** ke `40°` lalu kembali ke `0°`, kemudian `detach()` untuk menghemat daya.
4. Menyalakan **LED Merah** (status standby).
5. Menginisialisasi SPI dan membaca `VersionReg` chip **RC522**. Jika bernilai `0x00` atau `0xFF`, LED merah+kuning berkedip 4x sebagai peringatan sensor belum terbaca.
6. Mengaktifkan SoftAP `Parkir-IoT-Gate` dan memulai server HTTP port `80`.

### B. Siklus Buka & Tutup Palang (`openGate()` / `closeGate()`)
* **Membuka Palang (`0°` → `90°`):**
  1. LED **Kuning** menyala + buzzer bunyi 2x (`120 ms`) + jeda total ~`1 detik`.
  2. Servo bergerak halus per `2°` setiap `16 ms` (`moveServoSmooth`), lalu `detach()` setelah mencapai `90°`.
  3. LED **Hijau** menyala. Durasi buka default `5000 ms` (untuk scan RFID) atau `8000 ms` (untuk tiket karcis, override web, dan REST API `/api/open`).
* **Menutup Palang (`90°` → `0°`):**
  1. Otomatis dipicu secara non-blocking dari `loop()` setelah durasi buka habis.
  2. LED **Kuning** menyala + buzzer bunyi 1x (`300 ms`) + jeda `700 ms`.
  3. Servo turun halus ke `0°`, lalu `detach()`.
  4. LED **Merah** menyala kembali (Standby) dan cooldown gerbang `2000 ms` dimulai.

### C. Mode Hemat Arus Servo
Pada [`moveServoSmooth()`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino#L152-L168), servo digerakkan bertahap per `2°` agar lonjakan arus tidak membuat tegangan `3V3` drop, tidak dibunyikan bersamaan dengan buzzer, dan di-`detach()` segera setelah mencapai posisi target.

---

## 🌐 5. Komunikasi HTTP ke Backend Laravel

Firmware bertindak sebagai HTTP client menuju backend di `192.168.4.2`:

| Fungsi | Method | URL Utama & Fallback | Payload / Query | Timeout |
| :--- | :---: | :--- | :--- | :---: |
| **Scan Kartu RFID** (`syncScanWithBackend`) | `POST` | 1. `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api/scan`<br>2. `http://192.168.4.2/E-Parkir/api/scan.php` | `{"device_code":"GATE_MASUK_01","scan_uid":"<UID>"}` | `5000 ms` |
| **Request Tiket Karcis** (`requestTicketFromBackend`) | `POST` | 1. `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api/ticket`<br>2. `http://192.168.4.2/E-Parkir/api/ticket.php` | `{"device_code":"GATE_MASUK_01","action":"ticket","scan_uid":"TICKET_BUTTON"}` | `5000 ms` |
| **Polling Override** (`checkServerOverride`) | `GET` | `http://192.168.4.2/E-Parkir/E-Parkir_Web_Laravel/public/api/override?check=1&device_code=GATE_MASUK_01` | Query parameter `check=1&device_code=GATE_MASUK_01` (interval `1200 ms`) | `800 ms` |

### Parsing Respons Aksi (`parseActionFromResponse`)
Whitespace pada JSON respons dibuang terlebih dahulu agar kompatibel dengan `JSON_PRETTY_PRINT`, lalu dicocokkan dengan nilai `action`:

| Nilai `action` dari Server | Perilaku ESP8266 |
| :--- | :--- |
| `OPEN` | Membuka palang (`5000 ms` pada tap RFID, `8000 ms` pada override). |
| `TICKET_DELAY_OPEN` | LED Kuning menyala, buzzer 2x, jeda `2500 ms` untuk ambil karcis, buzzer 1x, lalu buka palang `8000 ms`. |
| `UNREGISTERED` | Palang tetap tertutup, LED Kuning + buzzer 1x (`500 ms`), kembali ke LED Merah. |
| `RATE_LIMITED` | Palang tetap tertutup, LED Kuning + buzzer 1x (`500 ms`), kembali ke LED Merah. |
| `DENIED` / `REJECTED` | Menjalankan `accessDeniedSequence()`: LED Merah berkedip 3x + buzzer 3x, lalu kembali standby Merah. |
| `UNKNOWN` *(atau gagal koneksi)* | LED Merah berkedip cepat 5x + buzzer 5x (`50 ms`). |

---

## 🛠️ 6. REST API Internal ESP8266 (`http://192.168.4.1`)

ESP8266 menyediakan endpoint JSON murni dengan header `Access-Control-Allow-Origin: *` (`server.enableCORS(true)`):

* **`GET /`**, **`GET /api/status`**, **`GET /status`**
  Mengembalikan status operasional dan diagnostik perangkat:
  ```json
  {
    "status": "online",
    "device_code": "GATE_MASUK_01",
    "gate_state": "CLOSED",
    "servo_angle": 0,
    "last_uid": "-",
    "last_action": "IDLE",
    "last_message": "Standby menunggu tap kartu RFID / tombol karcis",
    "backend_ip": "192.168.4.2",
    "wifi_clients": 1,
    "reset_reason": "External System",
    "free_heap": 45120,
    "scan_count": 0,
    "scan_http_code": 0,
    "scan_action": "-",
    "uptime_sec": 12
  }
  ```
* **`GET/POST /api/open`** atau **`/open`**
  Membuka palang selama `8000 ms` dan mengembalikan:
  ```json
  {"status":"success","action":"OPEN","servo_angle":90,"duration_ms":8000}
  ```
* **`GET/POST /api/close`** atau **`/close`**
  Menutup palang dan mengembalikan:
  ```json
  {"status":"success","action":"CLOSED","servo_angle":0}
  ```
* **`GET/POST /api/ticket`** atau **`/ticket`**
  Memanggil `requestTicketFromBackend()`. Jika server membalas `OPEN` atau `TICKET_DELAY_OPEN`, menunggu `2500 ms` lalu membuka palang `8000 ms` (`200 OK`). Jika gagal, menjalankan `accessDeniedSequence()` dan membalas `400 Bad Request`.

---

## ⏱️ 7. Parameter Timer & Anti-Spam

| Konstanta | Nilai | Fungsi |
| :--- | :---: | :--- |
| `OVERRIDE_INTERVAL` | `1200 ms` | Interval polling perintah darurat/tiket dari backend |
| `CARD_DEBOUNCE_MS` | `2500 ms` | Mencegah pembacaan ganda kartu RFID yang sama |
| `BUTTON_DEBOUNCE_MS` | `2500 ms` | Debounce tombol fisik karcis masuk |
| `GATE_COOLDOWN_MS` | `2000 ms` | Jeda aman setelah palang tertutup sebelum tap/tombol berikutnya |
| `gateOpenDuration` (default) | `5000 ms` | Durasi palang terbuka untuk scan RFID normal |

---

## ⚠️ 8. Catatan Teknis Kode Saat Ini

1. **Pin `TX` (`GPIO1`) & `RX` (`GPIO3`) bersama `Serial`:** `PIN_BUZZER` memakai `GPIO3` (`RX`) dan `PIN_BUTTON` memakai `GPIO1` (`TX`) sementara `Serial.begin(115200)` aktif. Saat debugging melalui kabel USB Serial, sinyal UART TX/RX dapat memengaruhi pembacaan tombol atau bunyi buzzer.
2. **Konstanta IP Fallback `192.168.4.100`:** Variabel `url_scan_fallback_ip` dan `url_override_fallback_ip` dideklarasikan di bagian atas file, namun fungsi `syncScanWithBackend()`, `requestTicketFromBackend()`, dan `checkServerOverride()` saat ini hanya mencoba IP `192.168.4.2`.
3. **Polling Override Tanpa Cek Jumlah Klien:** `checkServerOverride()` tetap melakukan request HTTP (`timeout 800 ms` tiap `1200 ms`) meskipun belum ada perangkat yang terhubung ke SoftAP (`WiFi.softAPgetStationNum() == 0`).
4. **Cabang `NO_CLIENT` pada `loop()`:** Blok penanganan `serverAction == "NO_CLIENT"` tersedia di `loop()`, tetapi `syncScanWithBackend()` tidak pernah mengembalikan string `"NO_CLIENT"` (saat gagal terhubung akan mengembalikan `"UNKNOWN"`).

