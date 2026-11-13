# 🔧 Dokumentasi Firmware ESP8266 E-Parkir IoT (`esp8266_gate_ap`)

Dokumen ini menjelaskan arsitektur, pemetaan pin hardware, alur logika, komunikasi backend, dan REST API internal dari firmware mikrokontroler **ESP8266** pada sistem **E-Parkir IoT (Sigma Boys)**.

* **File Firmware:** [`esp8266_gate_ap.ino`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino)
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

### A. Wi-Fi Station Mode (`WIFI_STA`)
ESP8266 beroperasi dalam mode `WIFI_STA` (terhubung ke router Wi-Fi yang sama dengan laptop backend server) dengan `WiFi.setAutoReconnect(true)` dan `WiFi.persistent(false)`:
* **SSID Router (`router_ssid`):** `Day One`
* **Password Router (`router_password`):** `error0110`
* **IP ESP8266:** Dinamis dari DHCP router (`WiFi.localIP()`), ditampilkan di Serial Monitor dan endpoint `/api/status` (`esp_ip`)
* **Port Web Server ESP:** `80`

### B. Target Backend Server (Laptop)
* **Host Backend (`backend_host`):** `192.168.4.158` (IP awal di jaringan router, otomatis diperbarui secara dinamis via REST API `/api/set_backend?ip=<IP_LAPTOP>` oleh Web Admin tanpa perlu reflash)
* **Port Web Server:** `80`

---

## 🔌 3. Pemetaan Pin Hardware (NodeMCU V3)

| Komponen | Pin NodeMCU | GPIO | Keterangan |
| :--- | :---: | :---: | :--- |
| **RFID RC522 — RST** | `D0` | `GPIO16` | Reset modul MFRC522 |
| **RFID RC522 — SDA (SS)** | `D8` | `GPIO15` | Chip Select SPI |
| **RFID RC522 — SCK** | `D5` | `GPIO14` | SPI Clock (default) |
| **RFID RC522 — MISO** | `D6` | `GPIO12` | SPI MISO (default) |
| **RFID RC522 — MOSI** | `D7` | `GPIO13` | SPI MOSI (default) |
| **LED Merah (`PIN_LED_RED`)** | `D1` | `GPIO5` | Standby / Palang tertutup |
| **LED Kuning (`PIN_LED_YELLOW`)** | `D2` | `GPIO4` | Transisi gerak / Proses cetak tiket |
| **LED Hijau (`PIN_LED_GREEN`)** | `-1` *(Nonaktif)* | `-` | Dinonaktifkan karena `D3` (`GPIO0`) dipakai untuk Servo Masuk |
| **Servo Palang Masuk (`PIN_SERVO_IN`)** | `D3` | `GPIO0` | Sinyal PWM (`500–2400 us`, `0°` tutup, `90°` buka) |
| **Servo Palang Keluar (`PIN_SERVO_OUT`)** | `D4` | `GPIO2` | Sinyal PWM (`500–2400 us`, `0°` tutup, `90°` buka) |
| **Buzzer (`PIN_BUZZER`)** | `RX` | `GPIO3` | Indikator audio |
| **Tombol Karcis Masuk (`PIN_BUTTON`)** | `TX` | `GPIO1` | Push button ke `GND` (`INPUT_PULLUP`) |

### Konfigurasi Polaritas & Sudut Servo
```cpp
const bool BUZZER_ACTIVE_LOW = false;
const bool LED_ACTIVE_LOW    = false;
const bool BUTTON_ACTIVE_LOW = true;

const int ANGLE_CLOSED     = 0;
const int ANGLE_OPEN       = 90;
const bool SERVO_IN_MIRROR = true; // Servo Masuk (D3) berlawanan arah dengan Servo Keluar (D4) (D4: 0°->90°, D3: 90°->0°)
```

---

## 🔄 4. Alur Kerja & Gerakan Palang

### A. Self-Test Saat Booting (`setup()`)
1. Menyalakan **LED Merah**, **Kuning**, lalu **Hijau** secara bergantian (masing-masing `300 ms`; karena `PIN_LED_GREEN = -1`, tidak ada pin fisik yang aktif pada langkah hijau).
2. Membunyikan **Buzzer** 2x (`80 ms`, jeda `50 ms`).
3. Menggerakkan **Servo Masuk (`D3`)** dan **Servo Keluar (`D4`)** ke `40°` (`250 ms`) lalu kembali ke `0°` (`250 ms`), kemudian `detach()` keduanya untuk menghemat daya.
4. Menyalakan **LED Merah** (status standby).
5. Menginisialisasi SPI dan membaca `VersionReg` chip **RC522**. Jika bernilai `0x00` atau `0xFF`, LED merah+kuning berkedip 4x (`100 ms`) sebagai peringatan sensor belum terbaca.
6. Menghubungkan ke Wi-Fi Router (`WIFI_STA`, tunggu awal hingga `15 detik`). Jika terhubung, mencetak IP lokal ESP8266 dan membunyikan buzzer 2x (`900 ms`, jeda `200 ms`). Jika belum, `autoReconnect` tetap berjalan.
7. Mengaktifkan server HTTP port `80` dengan CORS aktif (`server.enableCORS(true)`).

### B. Siklus Buka & Tutup Palang (`openGate()` / `closeGate()`)
Firmware mendukung kontrol palang terpisah melalui enum `GateTarget`: `GATE_NONE`, `GATE_IN` (servo `D3`), `GATE_OUT` (servo `D4`), dan `GATE_BOTH` (keduanya).

* **Membuka Palang (`0°` → `90°`):**
  1. LED **Kuning** menyala + buzzer bunyi 2x (`120 ms`, jeda `100 ms`) + `delay(680)` (jeda total ~`1 detik`).
  2. Servo sesuai `target` (`GATE_IN`, `GATE_OUT`, atau `GATE_BOTH`) bergerak halus per `2°` setiap `16 ms` (`moveServoSmooth`), menunggu `250 ms`, lalu `detach()`.
  3. Memanggil `setLED(false, false, true)` (lampu merah dan kuning padam; LED hijau fisik nonaktif).
  4. Durasi buka default `5000 ms` (untuk scan RFID) atau `8000 ms` (untuk tiket karcis, override web, dan REST API `/api/open`). Jika `openGate()` dipanggil saat palang sudah terbuka, timer `openGateStartTime` dan `gateOpenDuration` diperbarui.
* **Menutup Palang (`90°` → `0°`):**
  1. Otomatis dipicu secara non-blocking dari `loop()` setelah durasi buka habis.
  2. LED **Kuning** menyala + buzzer bunyi 1x (`300 ms`) + jeda `700 ms`.
  3. Servo pada `activeGateTarget` turun halus ke `0°`, lalu `detach()`.
  4. LED **Merah** menyala kembali (Standby), `activeGateTarget` direset ke `GATE_NONE`, dan cooldown gerbang `2000 ms` dimulai.

### C. Mode Hemat Arus Servo
Pada [`moveServoSmooth()`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino#L157-L185), hanya servo target yang di-`attach()`, digerakkan bertahap per `2°` agar lonjakan arus tidak membuat tegangan `3V3` drop, tidak dibunyikan bersamaan dengan buzzer, dan langsung di-`detach()` setelah mencapai posisi target.

---

## 🌐 5. Komunikasi HTTP ke Backend Laravel

Firmware bertindak sebagai HTTP client menuju `backend_host` (default `192.168.4.158`):

| Fungsi | Method | URL Utama & Fallback | Payload / Query | Timeout |
| :--- | :---: | :--- | :--- | :---: |
| **Scan Kartu RFID** (`syncScanWithBackend`) | `POST` | 1. `http://<backend_host>/E-Parkir/E-Parkir_Web_Laravel/public/api/scan`<br>2. `http://<backend_host>/E-Parkir/api/scan.php` | `{"device_code":"GATE_MASUK_01","scan_uid":"<UID>"}` | `5000 ms` |
| **Request Tiket Karcis** (`requestTicketFromBackend`) | `POST` | 1. `http://<backend_host>/E-Parkir/E-Parkir_Web_Laravel/public/api/ticket`<br>2. `http://<backend_host>/E-Parkir/api/ticket.php` | `{"device_code":"GATE_MASUK_01","action":"ticket","scan_uid":"TICKET_BUTTON"}` | `5000 ms` |
| **Polling Override** (`checkServerOverride`) | `GET` | `http://<backend_host>/E-Parkir/E-Parkir_Web_Laravel/public/api/override?check=1&device_code=GATE_MASUK_01` | Query parameter `check=1&device_code=GATE_MASUK_01` (interval `1200 ms`, aktif saat `WiFi.status() == WL_CONNECTED`) | `800 ms` |

### Parsing Respons Aksi & Arah Palang
Whitespace pada JSON respons dibuang terlebih dahulu agar kompatibel dengan `JSON_PRETTY_PRINT`, lalu dicocokkan pada [`parseActionFromResponse()`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino#L267-L282) dan [`parseGateTargetFromResponse()`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino#L284-L295):

| Nilai `action` dari Server | Perilaku ESP8266 |
| :--- | :--- |
| `OPEN` | Membuka palang (`5000 ms` pada tap RFID sesuai `lastScanGateTarget`, `8000 ms` pada override untuk kedua palang). |
| `TICKET_DELAY_OPEN` | LED Kuning menyala, buzzer 2x (`90 ms`, jeda `80 ms`), jeda `2500 ms` untuk ambil karcis, buzzer 1x (`150 ms`), lalu buka palang masuk (`GATE_IN`) selama `8000 ms`. |
| `UNREGISTERED` | Palang tetap tertutup, LED Kuning + buzzer 1x (`500 ms`), jeda `500 ms`, kembali ke LED Merah. |
| `RATE_LIMITED` | Palang tetap tertutup, LED Kuning + buzzer 1x (`500 ms`), jeda `500 ms`, kembali ke LED Merah. |
| `DENIED` / `REJECTED` | Menjalankan `accessDeniedSequence()`: LED Merah berkedip 3x + buzzer 3x, lalu kembali standby Merah. |
| `UNKNOWN` *(atau gagal koneksi)* | LED Merah berkedip cepat 5x + buzzer 5x (`50 ms`). |

| Nilai `flow` pada JSON Respons | Target Servo (`GateTarget`) |
| :--- | :--- |
| Diawali `"TAP_OUT"` | `GATE_OUT` (hanya Servo Palang Keluar `D4`) |
| `"TAP_IN"` atau `"TICKET_ENTRY"` | `GATE_IN` (hanya Servo Palang Masuk `D3`) |
| Lainnya / tidak tersedia | `GATE_BOTH` (Servo Masuk `D3` & Keluar `D4`) |

---

## 🛠️ 6. REST API Internal ESP8266 (`http://<esp_ip>`)

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
    "backend_ip": "192.168.4.158",
    "wifi_connected": true,
    "esp_ip": "192.168.4.105",
    "reset_reason": "External System",
    "free_heap": 45120,
    "scan_count": 0,
    "scan_http_code": 0,
    "scan_action": "-",
    "uptime_sec": 12
  }
  ```
* **`GET/POST /api/open`** atau **`/open`**
  Membuka kedua palang (`GATE_BOTH`) selama `8000 ms` dan mengembalikan:
  ```json
  {"status":"success","action":"OPEN","servo_angle":90,"duration_ms":8000}
  ```
* **`GET/POST /api/close`** atau **`/close`**
  Menutup palang yang sedang aktif dan mengembalikan:
  ```json
  {"status":"success","action":"CLOSED","servo_angle":0}
  ```
* **`GET/POST /api/ticket`** atau **`/ticket`**
  Memanggil `requestTicketFromBackend()`. Jika server membalas `OPEN` atau `TICKET_DELAY_OPEN`, menunggu `2500 ms` lalu membuka palang masuk (`GATE_IN`) selama `8000 ms` (`200 OK`). Jika gagal, menjalankan `accessDeniedSequence()` dan membalas `400 Bad Request`.
* **`GET/POST /api/set_backend?ip=<IP_LAPTOP>`**
  Memperbarui variabel `backend_host` di memori ESP8266 secara dinamis saat runtime tanpa perlu compile/flash ulang. Dipanggil otomatis oleh Web Admin Dashboard saat mendeteksi IP laptop berubah. Mengembalikan:
  ```json
  {"status":"success","message":"Backend IP updated","backend_ip":"192.168.4.158"}
  ```

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
2. **LED Hijau Fisik Dinonaktifkan (`PIN_LED_GREEN = -1`):** Pin `D3` (`GPIO0`) dialihkan untuk `PIN_SERVO_IN`, sehingga `setLED(false, false, true)` hanya memadamkan LED Merah dan Kuning tanpa menyalakan LED Hijau fisik.
3. **Komentar Header vs Implementasi:** Komentar di bagian atas `esp8266_gate_ap.ino` masih menyebut IP `192.168.4.2`, sudut `180 derajat`, dan URL diagnostik `http://192.168.4.1/api/status`, sedangkan implementasi aktif memakai `WIFI_STA`, `backend_host = "192.168.4.158"`, dan `ANGLE_OPEN = 90`.
4. **Cabang `NO_CLIENT` pada `loop()`:** Blok penanganan `serverAction == "NO_CLIENT"` tersedia di `loop()`, tetapi `syncScanWithBackend()` tidak pernah mengembalikan string `"NO_CLIENT"` (saat gagal terhubung akan mengembalikan `"UNKNOWN"`).
5. **Reset LED Kuning di `accessDeniedSequence()`:** Baris `digitalWrite(PIN_LED_YELLOW, LOW);` dipanggil langsung tanpa memperhatikan `LED_ACTIVE_LOW` (jika diubah ke `true`, LED kuning akan menyala sesaat sebelum kedip merah).
