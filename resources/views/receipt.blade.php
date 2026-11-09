<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $receiptData['is_entry_ticket'] ? 'Karcis Masuk' : 'Bukti Pembayaran' }} #{{ $receiptData['ticket_code'] ?? $receiptData['trx_code'] }} - E-Parkir</title>
    <!-- Bootstrap 5.3 & Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Standar Roll Kertas Thermal 58mm */
        @page {
            size: 58mm auto;
            margin: 0;
        }

        @media print {
            body {
                background: #FFFFFF !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 58mm !important;
            }
            .d-print-none {
                display: none !important;
            }
            .receipt-ticket {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 2mm 1.5mm !important;
                width: 54mm !important;
                max-width: 54mm !important;
            }
            #qrcode-container img, #qrcode-container canvas {
                margin: 0 auto !important;
            }
        }

        body {
            font-family: 'Courier New', Courier, monospace, 'Lucida Console', Monaco;
            background-color: #0F172A;
            color: #000000;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Container Struk Kertas Fisik 58mm */
        .receipt-ticket {
            background-color: #FFFFFF;
            width: 100%;
            max-width: 310px; /* ~58mm proporsi layar */
            padding: 20px 16px;
            border-radius: 4px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.45);
            font-size: 13px;
            line-height: 1.35;
            color: #111111;
            letter-spacing: -0.2px;
        }

        .receipt-dashed {
            border-top: 1.5px dashed #222222;
            margin: 8px 0;
            width: 100%;
        }

        .receipt-double {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            height: 3px;
            margin: 8px 0;
            width: 100%;
        }

        /* Kode Tiket GEDE */
        .big-ticket-code {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-align: center;
            padding: 5px 2px;
            margin: 3px 0;
            border: 1.5px solid #000000;
            background-color: #FAFAFA;
            color: #000000;
            word-break: break-all;
        }

        /* Baris Data Karcis / Struk */
        .ticket-details {
            width: 100%;
            font-size: 12.5px;
            line-height: 1.45;
            margin: 4px 0;
        }

        .ticket-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .ticket-label {
            color: #222222;
            font-weight: 600;
            flex-shrink: 0;
        }

        .ticket-value {
            font-weight: 700;
            text-align: right;
            word-break: break-word;
            color: #000000;
        }

        /* Container QR Code */
        #qrcode-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 6px;
            padding: 4px;
        }

        #qrcode-container canvas, #qrcode-container img {
            display: block;
            margin: 0 auto;
            image-rendering: pixelated;
        }
    </style>
</head>
<body>

    <!-- ACTION BUTTONS TOOLBAR (D-PRINT-NONE) -->
    <div class="d-print-none w-100 mb-3 d-flex flex-column gap-2" style="max-width: 310px; font-family: system-ui, -apple-system, sans-serif;">
        <!-- Header status bar -->
        <div class="d-flex align-items-center justify-content-between bg-dark bg-opacity-75 backdrop-blur px-3 py-2 rounded-3 border border-secondary text-white shadow">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded text-white" style="width: 1.5rem; height: 1.5rem; background-color: #00719C; font-size: 0.75rem;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="small fw-bold lh-1 text-white">Printer Thermal 58mm</div>
                    <div class="text-white-50" style="font-size: 9px;">Eppos EP589 / POS-58</div>
                </div>
            </div>

            <!-- Mode Struk Aktif -->
            <span class="badge {{ $receiptData['is_entry_ticket'] ? 'bg-warning text-dark' : 'bg-success text-white' }} fw-bold" style="font-size: 10px;">
                {{ $receiptData['is_entry_ticket'] ? 'Karcis Masuk' : 'Bukti Bayar' }}
            </span>
        </div>

        <!-- Tombol Switch Mode Struk -->
        <div class="btn-group btn-group-sm w-100 bg-dark p-1 rounded-3 border border-secondary" role="group">
            <a href="{{ request()->fullUrlWithQuery(['type' => 'ticket']) }}" class="btn {{ $receiptData['is_entry_ticket'] ? 'btn-primary' : 'btn-dark text-white-50' }} fw-bold rounded-2">
                <i class="bi bi-ticket-perforated-fill me-1"></i> Karcis Masuk
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'receipt']) }}" class="btn {{ !$receiptData['is_entry_ticket'] ? 'btn-primary' : 'btn-dark text-white-50' }} fw-bold rounded-2">
                <i class="bi bi-cash-stack me-1"></i> Bukti Bayar
            </a>
        </div>

        <!-- Tombol Cetak -->
        <div class="row g-2">
            <div class="col-6">
                <button onclick="printViaBluetooth()" id="btn-bt" class="btn btn-sm btn-primary w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-1 shadow rounded-3 py-2">
                    <i class="bi bi-bluetooth"></i>
                    <span id="bt-btn-text">Bluetooth</span>
                </button>
            </div>
            <div class="col-6">
                <button onclick="window.print()" class="btn btn-sm btn-warning text-dark w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-1 shadow rounded-3 py-2">
                    <i class="bi bi-printer-fill"></i>
                    <span>Cetak USB</span>
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between text-white-50 px-1 pt-1" style="font-size: 11px;">
            <a href="javascript:window.history.back()" class="text-white-50 text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <span class="font-monospace text-secondary" style="font-size: 10px;">#{{ $receiptData['ticket_code'] ?? $receiptData['trx_code'] }}</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- KERTAS STRUK THERMAL 58MM (AREA TERCETAK)                                -->
    <!-- ========================================================================= -->
    <div class="receipt-ticket" id="thermal-receipt">

        <!-- HEADER APLIKASI (E-Parkir) -->
        <div class="text-center">
            <div class="fw-bold" style="font-size: 17px; letter-spacing: 0.05em; line-height: 1.2;">{{ $receiptData['area_name'] ?? 'E-Parkir' }}</div>
            <div class="fw-bold" style="font-size: 11px;">{{ $receiptData['area_tagline'] ?? 'Sistem Parkir Otomatis IoT' }}</div>
            @if(!$receiptData['is_entry_ticket'])
                <div style="font-size: 11px;">{{ $receiptData['area_address'] ?? 'Jl. Jenggolo Raya' }}</div>
            @endif
        </div>

        @if ($receiptData['is_entry_ticket'])
            <!-- ================================================================= -->
            <!-- TIPE 1: KARCIS MASUK PARKIR (ENTRY TICKET)                        -->
            <!-- ================================================================= -->
            <div class="receipt-dashed"></div>

            <div class="text-center fw-bold text-uppercase my-1" style="font-size: 14px; letter-spacing: 0.05em;">
                KARCIS MASUK PARKIR
            </div>

            <div class="receipt-dashed"></div>

            <!-- KODE TIKET GEDE & JELAS -->
            <div class="text-center fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.05em;">
                Kode Tiket Masuk:
            </div>
            <div class="big-ticket-code font-monospace">
                {{ $receiptData['ticket_code'] }}
            </div>

            <div class="receipt-dashed"></div>

            <!-- Rincian Masuk -->
            <div class="ticket-details">
                <div class="ticket-row">
                    <span class="ticket-label">Pos Gerbang</span>
                    <span class="ticket-value">{{ $receiptData['gate_in'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Masuk</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['tap_in_time'] }} WIB</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Lantai Parkir</span>
                    <span class="ticket-value fw-bold">LANTAI {{ $receiptData['parking_floor'] ?? 1 }} (LT {{ $receiptData['parking_floor'] ?? 1 }})</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Tarif Parkir</span>
                    <span class="ticket-value">{{ $receiptData['rate_display'] }}</span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- QR CODE TIKET -->
            <div class="text-center fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.05em;">
                Scan QR Code Pembayaran:
            </div>
            <div id="qrcode-container" class="my-2" style="min-height: 140px;"></div>
            <div class="receipt-dashed"></div>

        @else
            <!-- ================================================================= -->
            <!-- TIPE 2: STRUK BUKTI PEMBAYARAN KELUAR CLIENT (LUNAS)              -->
            <!-- ================================================================= -->
            <div class="receipt-double"></div>

            <div class="text-center my-1">
                <div class="fw-bold text-uppercase" style="font-size: 13px; letter-spacing: 0.05em;">
                    BUKTI PEMBAYARAN PARKIR
                </div>
                <div class="mt-1">
                    <span class="border border-dark px-2 py-0.5 fw-bold" style="font-size: 11px; letter-spacing: 0.1em; display: inline-block;">
                        *** STATUS: LUNAS ***
                    </span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- Metadata Transaksi -->
            <div class="ticket-details">
                <div class="ticket-row">
                    <span class="ticket-label">No. Transaksi</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['trx_code'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Pengendara</span>
                    <span class="ticket-value">{{ $receiptData['user_name'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Plat / UID</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['license_plate'] !== '-' ? $receiptData['license_plate'] : $receiptData['uid'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Lantai Parkir</span>
                    <span class="ticket-value">LANTAI {{ $receiptData['parking_floor'] ?? 1 }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Pos Keluar</span>
                    <span class="ticket-value">{{ $receiptData['gate_out'] }}</span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- Durasi & Waktu Parkir -->
            <div class="ticket-details">
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Masuk</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['tap_in_time'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Keluar</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['tap_out_time'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Durasi Parkir</span>
                    <span class="ticket-value">{{ $receiptData['duration_minutes'] }} Menit ({{ $receiptData['billed_hours'] }} Jam)</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Tarif / Jam</span>
                    <span class="ticket-value">Rp 2.000</span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- Total Biaya & Saldo Member -->
            <div class="ticket-details">
                <div class="ticket-row fw-bold" style="font-size: 13px;">
                    <span class="ticket-label fw-bold">TOTAL BIAYA</span>
                    <span class="ticket-value fw-bold" style="font-size: 14px;">{{ $receiptData['total_fee_formatted'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Metode Bayar</span>
                    <span class="ticket-value">{{ $receiptData['payment_method'] }}</span>
                </div>

                @if(!empty($receiptData['is_member']))
                <div class="ticket-row" style="font-size: 11.5px;">
                    <span class="ticket-label">Saldo Awal</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['balance_before_formatted'] }}</span>
                </div>
                <div class="ticket-row fw-bold" style="font-size: 11.5px;">
                    <span class="ticket-label fw-bold">Sisa Saldo</span>
                    <span class="ticket-value font-monospace">{{ $receiptData['balance_after_formatted'] }}</span>
                </div>
                @endif
            </div>

            <div class="receipt-dashed"></div>

            <!-- Footer Bukti Pembayaran -->
            <div class="text-center" style="font-size: 11px;">
                <div>Hati-Hati Di Jalan Raya</div>
                <div class="text-secondary mt-1" style="font-size: 9px;">{{ $receiptData['print_time'] }}</div>
            </div>
        @endif
    </div>

    <!-- Hidden Raw Text untuk ESC/POS Bluetooth -->
    <textarea id="raw-escpos-text" class="d-none">{{ $thermalText }}</textarea>

    <!-- Offline Inline QRCode Library -->
    @php
        $qrJsFile = public_path('assets/js/qrcode.min.js');
        $inlineQrJs = file_exists($qrJsFile) ? file_get_contents($qrJsFile) : '';
    @endphp
    @if(!empty($inlineQrJs))
        <script>{!! $inlineQrJs !!}</script>
    @else
        <script src="{{ asset('assets/js/qrcode.min.js') }}"></script>
    @endif

    <script>
        // 1. Generate QR Code Secara Resilient & Instan
        function renderQrCode() {
            const qrContainer = document.getElementById("qrcode-container");
            if (!qrContainer) return;
            qrContainer.innerHTML = "";

            const qrContent = "{{ $receiptData['ticket_code'] ?? $receiptData['trx_code'] }}";
            let rendered = false;

            try {
                if (typeof QRCode !== 'undefined') {
                    new QRCode(qrContainer, {
                        text: qrContent,
                        width: 135,
                        height: 135,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: (typeof QRCode.CorrectLevel !== 'undefined' && typeof QRCode.CorrectLevel.L !== 'undefined') ? QRCode.CorrectLevel.L : 1
                    });
                    rendered = true;
                }
            } catch (e) {
                console.warn("QRCode Render Warning:", e);
            }

            // Fallback Tag Gambar jika Browser Memblokir Canvas
            setTimeout(() => {
                const hasCanvasOrImg = qrContainer.querySelector('canvas, img, svg');
                if (!hasCanvasOrImg) {
                    const img = document.createElement('img');
                    img.src = "https://api.qrserver.com/v1/create-qr-code/?size=135x135&data=" + encodeURIComponent(qrContent);
                    img.alt = "QR Code " + qrContent;
                    img.style.width = "135px";
                    img.style.height = "135px";
                    img.style.margin = "0 auto";
                    img.style.display = "block";
                    qrContainer.appendChild(img);
                }
            }, 50);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderQrCode);
        } else {
            renderQrCode();
        }

        // 2. Auto print jika dipanggil dengan parameter ?autoprint=1
        @if($autoprint)
            window.addEventListener('DOMContentLoaded', () => {
                if (window.self === window.top) {
                    setTimeout(() => window.print(), 400);
                }
            });
        @endif

        // 3. Web Bluetooth ESC/POS Driver untuk Eppos EP589 V2 / POS-58
        const rawReceiptText = document.getElementById('raw-escpos-text').value;

        async function printViaBluetooth() {
            const btnText = document.getElementById('bt-btn-text');
            const btnBt = document.getElementById('btn-bt');

            if (!navigator.bluetooth) {
                alert('Browser Anda belum mendukung Web Bluetooth. Gunakan Google Chrome atau Microsoft Edge untuk cetak Bluetooth nirkabel.');
                return;
            }

            try {
                btnBt.disabled = true;
                btnText.innerText = 'Menghubungkan...';

                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: [
                        '000018f0-0000-1000-8000-00805f9b34fb',
                        '0000ffe0-0000-1000-8000-00805f9b34fb',
                        '0000ff00-0000-1000-8000-00805f9b34fb',
                        'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
                        '49535343-fe7d-4ae5-8fa9-9fafd205e455'
                    ]
                });

                const server = await device.gatt.connect();

                let targetCharacteristic = null;
                const services = await server.getPrimaryServices();

                for (const service of services) {
                    try {
                        const characteristics = await service.getCharacteristics();
                        for (const char of characteristics) {
                            if (char.properties.write || char.properties.writeWithoutResponse) {
                                targetCharacteristic = char;
                                break;
                            }
                        }
                    } catch (e) {}
                    if (targetCharacteristic) break;
                }

                if (!targetCharacteristic) {
                    throw new Error('Karakteristik tulis Bluetooth tidak ditemukan pada printer ini.');
                }

                btnText.innerText = 'Mencetak...';

                const encoder = new TextEncoder();
                const initCmd = new Uint8Array([0x1B, 0x40]); // ESC @
                const textBytes = encoder.encode(rawReceiptText);

                await targetCharacteristic.writeValue(initCmd);

                const chunkSize = 80;
                for (let i = 0; i < textBytes.length; i += chunkSize) {
                    const chunk = textBytes.slice(i, i + chunkSize);
                    await targetCharacteristic.writeValue(chunk);
                    await new Promise(r => setTimeout(r, 40));
                }

                btnText.innerText = 'Selesai!';
                setTimeout(() => {
                    btnText.innerText = 'Bluetooth';
                }, 2500);

            } catch (err) {
                console.error(err);
                alert('Pencetakan Bluetooth gagal: ' + err.message + '\n\nTips: Pastikan printer thermal Eppos EP589 dalam posisi ON dan Bluetooth aktif.');
                btnText.innerText = 'Bluetooth';
            } finally {
                btnBt.disabled = false;
            }
        }
    </script>
</body>
</html>
