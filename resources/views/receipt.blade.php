<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $receiptData['is_entry_ticket'] ? 'Karcis Masuk' : 'Bukti Pembayaran' }} #{{ $receiptData['ticket_code'] ?? $receiptData['trx_code'] }} - E-Parkir</title>
    <!-- Offline CSS & FontAwesome -->
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
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
            .no-print {
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

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: 700;
        }

        .font-black {
            font-weight: 900;
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

        /* Kotak Info Member Bayar Saldo */
        .member-info-box {
            background-color: #F8FAFC;
            border: 1px dashed #000000;
            border-radius: 4px;
            padding: 7px 8px;
            text-align: center;
            margin: 8px 0;
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

    <!-- ACTION BUTTONS TOOLBAR (NO-PRINT) -->
    <div class="no-print w-full max-w-[310px] mb-3 space-y-2">
        <!-- Header status bar -->
        <div class="flex items-center justify-between bg-slate-800/95 backdrop-blur px-3 py-2 rounded-xl border border-white/10 text-white shadow">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded bg-[#00719C] text-white flex items-center justify-center text-xs font-bold">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <div class="text-xs font-bold leading-tight">Printer Thermal 58mm</div>
                    <div class="text-[9px] text-slate-400">Eppos EP589 / POS-58</div>
                </div>
            </div>

            <!-- Mode Struk Aktif -->
            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $receiptData['is_entry_ticket'] ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                {{ $receiptData['is_entry_ticket'] ? 'Karcis Masuk' : 'Bukti Bayar (Lunas)' }}
            </span>
        </div>

        <!-- Tombol Switch Mode Struk -->
        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-900 rounded-xl border border-white/5 text-[11px]">
            <a href="{{ request()->fullUrlWithQuery(['type' => 'ticket']) }}" class="py-1 text-center font-bold rounded-lg transition {{ $receiptData['is_entry_ticket'] ? 'bg-[#00719C] text-white shadow' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-ticket text-[10px]"></i> Karcis Masuk
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'receipt']) }}" class="py-1 text-center font-bold rounded-lg transition {{ !$receiptData['is_entry_ticket'] ? 'bg-[#00719C] text-white shadow' : 'text-slate-400 hover:text-white' }}">
                <i class="fa-solid fa-file-invoice-dollar text-[10px]"></i> Bukti Bayar
            </a>
        </div>

        <!-- Tombol Cetak -->
        <div class="grid grid-cols-2 gap-2">
            <button onclick="printViaBluetooth()" id="btn-bt" class="px-3 py-2 rounded-xl bg-[#00719C] hover:bg-sky-600 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow transition cursor-pointer border-none">
                <i class="fa-brands fa-bluetooth-b"></i>
                <span id="bt-btn-text">Cetak Bluetooth</span>
            </button>

            <button onclick="window.print()" class="px-3 py-2 rounded-xl bg-[#FFD51E] hover:bg-yellow-400 text-slate-950 text-xs font-bold flex items-center justify-center gap-1.5 shadow transition cursor-pointer border-none">
                <i class="fa-solid fa-print"></i>
                <span>Cetak USB / Driver</span>
            </button>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-400 px-1 pt-0.5">
            <a href="javascript:window.history.back()" class="hover:text-white flex items-center gap-1">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <span class="text-slate-500 font-mono text-[10px]">#{{ $receiptData['ticket_code'] ?? $receiptData['trx_code'] }}</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- KERTAS STRUK THERMAL 58MM (AREA TERCETAK)                                -->
    <!-- ========================================================================= -->
    <div class="receipt-ticket" id="thermal-receipt">

        <!-- HEADER APLIKASI (E-Parkir) -->
        <div class="text-center space-y-0.5">
            <div class="font-black text-[17px] tracking-wider leading-tight text-black">{{ $receiptData['area_name'] ?? 'E-Parkir' }}</div>
            <div class="text-[11px] font-bold text-black">{{ $receiptData['area_tagline'] ?? 'Sistem Parkir Otomatis IoT' }}</div>
            @if(!$receiptData['is_entry_ticket'])
                <div class="text-[11px] text-black">{{ $receiptData['area_address'] ?? 'Jl. Jenggolo Raya' }}</div>
            @endif
        </div>

        @if ($receiptData['is_entry_ticket'])
            <!-- ================================================================= -->
            <!-- TIPE 1: KARCIS MASUK PARKIR (ENTRY TICKET)                        -->
            <!-- ================================================================= -->
            <div class="receipt-dashed"></div>

            <div class="text-center font-black text-[14px] uppercase tracking-wider my-1">
                KARCIS MASUK PARKIR
            </div>

            <div class="receipt-dashed"></div>

            <!-- KODE TIKET GEDE & JELAS -->
            <div class="text-center text-[10px] font-bold uppercase tracking-wider text-black">
                Kode Tiket Masuk:
            </div>
            <div class="big-ticket-code font-mono">
                {{ $receiptData['ticket_code'] }}
            </div>

            <div class="receipt-dashed"></div>

            <!-- Rincian Masuk -->
            <div class="ticket-details space-y-1">
                <div class="ticket-row">
                    <span class="ticket-label">Pos Gerbang</span>
                    <span class="ticket-value">{{ $receiptData['gate_in'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Masuk</span>
                    <span class="ticket-value font-mono">{{ $receiptData['tap_in_time'] }} WIB</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Lantai Parkir</span>
                    <span class="ticket-value font-black">LANTAI {{ $receiptData['parking_floor'] ?? 1 }} (LT {{ $receiptData['parking_floor'] ?? 1 }})</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Tarif Parkir</span>
                    <span class="ticket-value">{{ $receiptData['rate_display'] }}</span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- QR CODE TIKET -->
            <div class="text-center text-[10px] font-bold uppercase tracking-wider text-black">
                Scan QR Code Pembayaran:
            </div>
            <div id="qrcode-container" class="min-h-[140px] flex items-center justify-center my-1.5"></div>
            <div class="receipt-dashed"></div>

        @else
            <!-- ================================================================= -->
            <!-- TIPE 2: STRUK BUKTI PEMBAYARAN KELUAR CLIENT (LUNAS)              -->
            <!-- ================================================================= -->
            <div class="receipt-double"></div>

            <div class="text-center space-y-1 my-1">
                <div class="font-black text-[13px] uppercase tracking-wider">
                    BUKTI PEMBAYARAN PARKIR
                </div>
                <div>
                    <span class="border border-black px-2.5 py-0.5 text-[11px] font-black tracking-widest inline-block">
                        *** STATUS: LUNAS ***
                    </span>
                </div>
            </div>

            <div class="receipt-dashed"></div>

            <!-- Metadata Transaksi -->
            <div class="ticket-details space-y-1">
                <div class="ticket-row">
                    <span class="ticket-label">No. Transaksi</span>
                    <span class="ticket-value font-mono">{{ $receiptData['trx_code'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Pengendara</span>
                    <span class="ticket-value">{{ $receiptData['user_name'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Plat / UID</span>
                    <span class="ticket-value font-mono">{{ $receiptData['license_plate'] !== '-' ? $receiptData['license_plate'] : $receiptData['uid'] }}</span>
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
            <div class="ticket-details space-y-1">
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Masuk</span>
                    <span class="ticket-value font-mono">{{ $receiptData['tap_in_time'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Waktu Keluar</span>
                    <span class="ticket-value font-mono">{{ $receiptData['tap_out_time'] }}</span>
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
            <div class="ticket-details space-y-1.5">
                <div class="ticket-row text-[13px] font-black">
                    <span class="ticket-label font-black">TOTAL BIAYA</span>
                    <span class="ticket-value text-[14px] font-black">{{ $receiptData['total_fee_formatted'] }}</span>
                </div>
                <div class="ticket-row">
                    <span class="ticket-label">Metode Bayar</span>
                    <span class="ticket-value">{{ $receiptData['payment_method'] }}</span>
                </div>

                @if(!empty($receiptData['is_member']))
                <div class="ticket-row text-[11.5px]">
                    <span class="ticket-label">Saldo Awal</span>
                    <span class="ticket-value font-mono">{{ $receiptData['balance_before_formatted'] }}</span>
                </div>
                <div class="ticket-row text-[11.5px] font-bold">
                    <span class="ticket-label font-bold">Sisa Saldo</span>
                    <span class="ticket-value font-mono">{{ $receiptData['balance_after_formatted'] }}</span>
                </div>
                @endif
            </div>

            <div class="receipt-dashed"></div>

            <!-- Footer Bukti Pembayaran -->
            <div class="text-center space-y-0.5 text-[11px]">
                <div>Hati-Hati Di Jalan Raya</div>
                <div class="text-[9px] text-slate-600 mt-0.5">{{ $receiptData['print_time'] }}</div>
            </div>
        @endif
    </div>

    <!-- Hidden Raw Text untuk ESC/POS Bluetooth -->
    <textarea id="raw-escpos-text" class="hidden">{{ $thermalText }}</textarea>

    <!-- Offline Inline QRCode Library (100% Bebas 404 & Bebas Error Path) -->
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

        // 2. Auto print jika dipanggil dengan parameter ?autoprint=1 (Hanya jika dibuka di tab/window utama, bukan iframe)
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
                    btnText.innerText = 'Cetak BT';
                }, 2500);

            } catch (err) {
                console.error(err);
                alert('Pencetakan Bluetooth gagal: ' + err.message + '\n\nTips: Pastikan printer thermal Eppos EP589 dalam posisi ON dan Bluetooth aktif.');
                btnText.innerText = 'Cetak BT';
            } finally {
                btnBt.disabled = false;
            }
        }
    </script>
</body>
</html>
