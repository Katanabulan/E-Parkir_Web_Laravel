# 🔧 Dokumentasi Firmware ESP8266 E-Parkir IoT (`esp8266_gate_ap`)

Dokumen ini menjelaskan arsitektur, pemetaan pin hardware, alur logika, komunikasi backend, dan REST API internal dari firmware mikrokontroler **ESP8266** pada sistem **E-Parkir IoT (Sigma Boys)**.

- **File Firmware:** [`esp8266_gate_ap.ino`](file:///C:/E-Parkir/firmware/esp8266_gate_ap/esp8266_gate_ap.ino)
- **Target Board:** NodeMCU V3 ESP8266 (`ESP-12E`)
- **Device Code Default:** `GATE_MASUK_01`

---

## 📦 1. Library yang Digunakan

Firmware membutuhkan library berikut pada Arduino IDE / PlatformIO:
- `ESP8266WiFi.h`
- `ESP8266WebServer.h`
- `ESP8266HTTPClient.h`
- `WiFiClient.h`
- `SPI.h`
- `MFRC522.h`
- `Servo.h`

---

## 📡 2. Konfigurasi Jaringan & Backend

### A. Wi-Fi Station Mode (`WIFI_STA`)
ESP8266 beroperasi dalam mode `WIFI_STA` (terhubung ke router Wi-Fi yang sama dengan laptop backend server) dengan `WiFi.setAutoReconnect(true)` dan `WiFi.persistent(false)`:
- **SSID Router (`router_ssid`):** `Day One`
- **Password Router (`router_password`):** `error0110`
- **IP ESP8266:** Dinamis dari DHCP router (`WiFi.localIP()`), ditampilkan di Serial Monitor dan endpoint `/api/status` (`esp_ip`)
- **Port Web Server ESP:** `80`

### B. Target Backend Server (Laptop)
- **Host Backend (`backend_host`):** `192.168.4.158` (IP awal di jaringan router, otomatis diperbarui secara dinamis via REST API `/api/set_backend?ip=<IP_LAPTOP>` oleh Web Admin tanpa perlu reflash)
- **Port Web Server:** `80`

---

## 🔌 3. Pemetaan Pin Hardware (NodeMCU V3)

| Komponen | Pin NodeMCU | Fungsi / Keterangan |
| :--- | :---: | :--- |
| **RFID — RST** | `D0` | Reset modul RC522 |
| **RFID — SDA (SS)** | `D8` | Chip Select SPI |
| **RFID — SCK / MISO / MOSI** | `D5` / `D6` / `D7` | Jalur komunikasi data SPI |
| **LED Gerbang Masuk** | `D1` | Kontrol LED Merah & Hijau Masuk |
| **LED Gerbang Keluar** | `D2` | Kontrol LED Merah & Hijau Keluar |
| **Servo Palang Masuk** | `D3` | PWM (`0°` Tutup, `90°` Buka) |
| **Servo Palang Keluar** | `D4` | PWM (`0°` Tutup, `90°` Buka) |
| **Buzzer** | `RX` | Indikator suara |
| **Tombol Karcis** | `TX` | Push button ke `GND` |

### 💡 Panduan Colok 4 LED (Langsung Pakai Jumper)

| Gerbang | Warna LED | Kaki Panjang (`+`) | Kaki Pendek (`-`) |
| :--- | :--- | :---: | :---: |
| **Masuk** | 🟢 **Hijau** *(Buka)* | `D1` | `GND` |
| **Masuk** | 🔴 **Merah** *(Tutup)* | `3V3` | `D1` |
| **Keluar** | 🟢 **Hijau** *(Buka)* | `D2` | `GND` |
| **Keluar** | 🔴 **Merah** *(Tutup)* | `3V3` | `D2` |

> **Cara Kerja Singkat:** Saat pin `D1`/`D2` bernilai `LOW`, lampu **Merah** otomatis menyala. Saat berubah menjadi `HIGH`, lampu otomatis berganti ke **Hijau**.

### Konfigurasi Polaritas & Sudut Servo
```cpp
const bool BUZZER_ACTIVE_LOW = false;
const bool BUTTON_ACTIVE_LOW = true;

const int ANGLE_CLOSED     = 0;  // 0° = Palang Tertutup Horizontal
const int ANGLE_OPEN       = 90; // 90° = Palang Terbuka Vertikal Tegak Lurus

// Kalibrasi pulse width presisi SG90 (544us - 2400us)
const int SERVO_MIN_US     = 544;
const int SERVO_MAX_US     = 2400;

// Mode 1 Servo: Kirim sinyal PWM ke D3 & D4 sekaligus agar servo bebas dicolok ke D3/D4
bool SINGLE_SERVO_MODE     = true;

// Detach servo saat standby agar tidak bergetar liar (anti-jitter akibat WiFi), dingin, dan hemat daya
bool DETACH_SERVO_IDLE     = true;

// Flag orientasi arah putar jika servo dipasang terbalik dari fisik tiang
bool SERVO_IN_REVERSE      = false;
bool SERVO_OUT_REVERSE     = false;