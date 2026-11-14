# 📄 Panduan & Desain Tampilan Cetak Laporan Parkir (`Readme_Cetak_Laporan.md`)

Dokumen ini menjelaskan struktur data, format visual, tata letak print (cetak fisik/PDF), dan integrasi API laporan transaksi & pendapatan sistem **E-Parkir IoT**, agar pengembang aplikasi **Mobile (Flutter / Android)** dapat menyelaraskan tampilan cetak atau pratinjau laporan dengan sistem **Web Laravel**.

---

## 🏛️ 1. Arsitektur Cetak Laporan

Sistem laporan dapat diakses melalui dua jalur:
1. **Portal Web Admin:** `GET /reports` (Tampilan tabel web dengan tombol "Cetak Laporan" via `@media print` / `window.print()`).
2. **API Mobile Owner:** 
   - `GET /api/client/owner_reports` (atau `/api/owner/reports`) $\rightarrow$ Data mentah array transaksi lengkap dengan pagination & filter.
   - `GET /api/client/owner_dashboard` (atau `/api/owner/dashboard`) $\rightarrow$ Ringkasan finansial, grafik harian, dan agregat okupansi lantai.

---

## 📐 2. Struktur Tampilan Cetak Fisik (Kertas A4 / Thermal / PDF)

Berikut adalah tata letak resmi yang digunakan saat mencetak laporan di sistem web:

### A. Header Dokumen (Kop Laporan)
```text
========================================================================================
                      SISTEM E-PARKIR OTOMATIS BERBASIS IoT
                 LAPORAN REKAPITULASI PENDAPATAN & TRANSAKSI PARKIR
========================================================================================
Periode Laporan : [Semua / Hari Ini / Kemarin / 7 Hari Terakhir / Rentang Tanggal]
Tanggal Cetak   : 13/11/2026 13:30 WIB
Dicetak Oleh    : Administrator (admin@eparkir.local)
Filter Akses    : [Semua Akses / Karcis Tiket / Kartu RFID]
Filter Status   : [Semua Status / Selesai / Sedang Parkir]
----------------------------------------------------------------------------------------
```

### B. Kartu Ringkasan Metrik (Summary Boxes)
Sebelum tabel rincian, tampilkan 4 ringkasan angka utama:

```text
+-----------------------+-----------------------+-----------------------+-----------------------+
|   TOTAL PENDAPATAN    |   TRANSAKSI SELESAI   |    KENDARAAN PARKIR   |   RATA-RATA DURASI    |
|    Rp 1.530.000       |        89 Data        |        4 Kendaraan    |       125 Menit       |
+-----------------------+-----------------------+-----------------------+-----------------------+
```

### C. Tabel Transaksi (Data Rows)

| No | Tipe Akses | Kode / No. Plat | Nama Pengendara | Waktu Masuk | Waktu Keluar | Durasi | Tarif (Rp) | Status |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| 1 | KARCIS | TKT-261113-001 | Pengendara Umum | 13/11 08:15 (Gate-01) | 13/11 10:20 (Gate-01) | 125 mnt (~3 Jam) | Rp 6.000 | SELESAI |
| 2 | RFID | B 1234 XYZ | Budi Santoso | 13/11 09:00 (Gate-01) | 13/11 09:45 (Gate-01) | 45 mnt (~1 Jam) | Rp 2.000 | SELESAI |
| 3 | RFID | D 5678 ABC | Siti Rahma | 13/11 11:10 (Gate-01) | - | - | Rp 0 | PARKIR |

#### Baris Total (Footer Tabel):
```text
----------------------------------------------------------------------------------------
TOTAL PENDAPATAN (TRANSAKSI SELESAI) : Rp 1.530.000              | 89 Transaksi Selesai
----------------------------------------------------------------------------------------
```

### D. Footer Tanda Tangan Dokumen (Sign-off)
```text
                                                        Jakarta, 13 November 2026
                                                        Penanggung Jawab / Owner,



                                                        ( ............................ )
                                                        NIP / ID Owner: 1001
```

---

## 📡 3. Spesifikasi Endpoint API Laporan untuk Mobile

### URL Endpoint
* **URL Utama:** `GET http://<IP_SERVER>/api/client/owner_reports`
* **Alternatif:** `GET http://<IP_SERVER>/api/owner/reports` atau `/api/reports`

### Query Parameter (Filter Data):
| Parameter | Tipe | Pilihan Nilai | Keterangan |
| :--- | :---: | :--- | :--- |
| `preset` | string | `today`, `yesterday`, `week`, `month`, `all` | Filter rentang waktu otomatis |
| `start_date`| string | Format `YYYY-MM-DD` | Filter tanggal awal kustom |
| `end_date` | string | Format `YYYY-MM-DD` | Filter tanggal akhir kustom |
| `status` | string | `all`, `COMPLETED`, `PARKED` | Filter status transaksi |
| `access_type`| string| `all`, `ticket`, `card` | Filter tipe karcis atau RFID |
| `floor` | string | `all`, `1`, `2` | Filter lantai parkir |
| `search` | string | teks bebas | Cari kode tiket, nama, plat nomor, atau UID |
| `limit` | int | `1 - 500` (default `100`) | Batas jumlah data yang diambil |

---

## 📦 4. Format Respons JSON dari Backend

```json
{
  "status": "success",
  "success": true,
  "code": 200,
  "message": "Owner reports data fetched successfully",
  "preset": "today",
  "total_records": 89,
  "total_revenue": 1530000,
  "total_revenue_formatted": "Rp 1.530.000",
  "reports": [
    {
      "id": 105,
      "transaction_id": 105,
      "ticket_code": "TKT-261113-001",
      "is_ticket": true,
      "access_type": "KARCIS",
      "access_method": "ticket",
      "parking_floor": 1,
      "parking_floor_label": "Lantai 1 (LT 1)",
      "user_name": "Pengendara Umum",
      "license_plate": "Karcis",
      "phone": "-",
      "tap_in_time": "2026-11-13 08:15:20",
      "tap_out_time": "2026-11-13 10:20:45",
      "tap_in_formatted": "13/11/2026 08:15",
      "tap_out_formatted": "13/11/2026 10:20",
      "duration_minutes": 125,
      "duration_formatted": "125 menit",
      "billed_hours": 3,
      "total_fee": 6000,
      "total_fee_formatted": "Rp 6.000",
      "status": "COMPLETED",
      "payment_method": "cash",
      "payment_method_label": "Tunai di Kasir (Karcis)",
      "gate_in": "Gate-01",
      "gate_out": "Gate-01"
    }
  ]
}
```

---

## 📱 5. Contoh Implementasi Model & Cetak PDF di Flutter

### A. Model Dart (`owner_report_model.dart`)
```dart
class ReportItemModel {
  final int id;
  final String ticketCode;
  final bool isTicket;
  final String accessType;
  final String userName;
  final String licensePlate;
  final String tapInFormatted;
  final String tapOutFormatted;
  final int durationMinutes;
  final int totalFee;
  final String totalFeeFormatted;
  final String status;

  ReportItemModel({
    required this.id,
    required this.ticketCode,
    required this.isTicket,
    required this.accessType,
    required this.userName,
    required this.licensePlate,
    required this.tapInFormatted,
    required this.tapOutFormatted,
    required this.durationMinutes,
    required this.totalFee,
    required this.totalFeeFormatted,
    required this.status,
  });

  factory ReportItemModel.fromJson(Map<String, dynamic> json) {
    return ReportItemModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      ticketCode: json['ticket_code']?.toString() ?? '',
      isTicket: json['is_ticket'] == true,
      accessType: json['access_type']?.toString() ?? (json['is_ticket'] == true ? 'KARCIS' : 'RFID'),
      userName: json['user_name']?.toString() ?? 'Pengendara',
      licensePlate: json['license_plate']?.toString() ?? '-',
      tapInFormatted: json['tap_in_formatted']?.toString() ?? '-',
      tapOutFormatted: json['tap_out_formatted']?.toString() ?? '-',
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      totalFee: (json['total_fee'] as num?)?.toInt() ?? 0,
      totalFeeFormatted: json['total_fee_formatted']?.toString() ?? 'Rp 0',
      status: json['status']?.toString() ?? 'PARKED',
    );
  }
}
```

### B. Membuat Dokumen PDF / Preview Cetak Menggunakan Package `pdf` & `printing`
Di Flutter, Anda dapat menggunakan library:
```yaml
dependencies:
  pdf: ^3.10.0
  printing: ^5.11.0
```

Contoh fungsi cetak PDF:
```dart
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

Future<void> printParkingReport({
  required String periodTitle,
  required int totalRevenue,
  required List<ReportItemModel> items,
  required String adminName,
}) async {
  final pdf = pw.Document();

  pdf.addPage(
    pw.MultiPage(
      pageFormat: PdfPageFormat.a4,
      margin: const pw.EdgeInsets.all(32),
      build: (pw.Context context) {
        return [
          // Header
          pw.Center(
            child: pw.Text(
              'LAPORAN REKAPITULASI PENDAPATAN PARKIR',
              style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold),
            ),
          ),
          pw.SizedBox(height: 4),
          pw.Center(
            child: pw.Text('Sistem E-Parkir Otomatis Berbasis IoT', style: const pw.TextStyle(fontSize: 11)),
          ),
          pw.Divider(thickness: 1.5),
          pw.SizedBox(height: 8),

          // Metadata Info
          pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: [
              pw.Text('Periode: $periodTitle', style: const pw.TextStyle(fontSize: 10)),
              pw.Text('Dicetak: ${DateTime.now().toString().substring(0, 16)} WIB', style: const pw.TextStyle(fontSize: 10)),
            ],
          ),
          pw.SizedBox(height: 12),

          // Ringkasan Finansial
          pw.Container(
            padding: const pw.EdgeInsets.all(8),
            decoration: pw.BoxDecoration(
              color: PdfColors.grey200,
              borderRadius: pw.BorderRadius.circular(4),
            ),
            child: pw.Row(
              mainAxisAlignment: pw.MainAxisAlignment.spaceAround,
              children: [
                pw.Text('Total Transaksi: ${items.length}', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10)),
                pw.Text('Total Pendapatan: Rp ${totalRevenue.toString().replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]}.')}', style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10, color: PdfColors.blue800)),
              ],
            ),
          ),
          pw.SizedBox(height: 14),

          // Tabel Transaksi
          pw.TableHelper.fromTextArray(
            headers: ['No', 'Tipe', 'Kode / Plat', 'Pengendara', 'Waktu Masuk', 'Waktu Keluar', 'Durasi', 'Tarif'],
            headerStyle: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9),
            headerDecoration: const pw.BoxDecoration(color: PdfColors.grey300),
            cellStyle: const pw.TextStyle(fontSize: 8),
            cellAlignment: pw.Alignment.centerLeft,
            data: List.generate(items.length, (index) {
              final item = items[index];
              return [
                (index + 1).toString(),
                item.accessType,
                item.isTicket ? item.ticketCode : item.licensePlate,
                item.userName,
                item.tapInFormatted,
                item.tapOutFormatted,
                '${item.durationMinutes} mnt',
                item.totalFeeFormatted,
              ];
            }),
          ),
          pw.SizedBox(height: 24),

          // Tanda Tangan
          pw.Align(
            alignment: pw.Alignment.centerRight,
            child: pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.center,
              children: [
                pw.Text('Jakarta, ${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}', style: const pw.TextStyle(fontSize: 10)),
                pw.SizedBox(height: 4),
                pw.Text('Penanggung Jawab / Administrator', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
                pw.SizedBox(height: 40),
                pw.Text('( $adminName )', style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
              ],
            ),
          ),
        ];
      },
    ),
  );

  // Menampilkan Pratinjau Cetak / Ekspor PDF langsung di HP
  await Printing.layoutPdf(
    onLayout: (PdfPageFormat format) async => pdf.save(),
    name: 'Laporan_Parkir_${DateTime.now().millisecondsSinceEpoch}.pdf',
  );
}
```

---

## 🎨 6. Panduan Warna & Tipografi Mobile

Untuk menjaga konsistensi identitas visual antara aplikasi Web dan Mobile:
* **Warna Primer (Brand):** `#0d6efd` (Biru Utama)
* **Warna Aksen / Sukses:** `#198754` (Hijau Pendapatan & Status Selesai)
* **Warna Peringatan (Parkir Berjalan):** `#ffc107` (Kuning / Oranye)
* **Font Monospace Angka & Uang:** Gunakan font monospace (seperti `Roboto Mono` atau `Courier`) untuk kolom Jam, Durasi, dan Nominal Tarif agar sejajar rapi saat dicetak.

