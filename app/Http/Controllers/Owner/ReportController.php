<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Tampilkan halaman laporan & ringkasan eksekutif untuk Owner.
     */
    public function index(Request $request)
    {
        $preset = $request->query('preset', 'all');
        $search = trim($request->query('search', ''));
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        $startTime = $request->query('start_time', '');
        $endTime = $request->query('end_time', '');
        $statusFilter = $request->query('status', 'all');
        $accessType = $request->query('access_type', 'all');
        $chartMode = $request->query('chart_mode', 'daily');

        // Preset handling
        if ($preset === 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'yesterday') {
            $startDate = Carbon::yesterday()->toDateString();
            $endDate = Carbon::yesterday()->toDateString();
        } elseif ($preset === 'week') {
            $startDate = Carbon::now()->subDays(6)->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'month') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($preset === 'all') {
            $startDate = '';
            $endDate = '';
        }

        $query = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut']);

        // Date range filter
        if (!empty($startDate) && !empty($endDate)) {
            $from = $startDate . ' ' . (!empty($startTime) ? $startTime . ':00' : '00:00:00');
            $to = $endDate . ' ' . (!empty($endTime) ? $endTime . ':59' : '23:59:59');
            $query->whereBetween('tap_in_time', [$from, $to]);
        } elseif (!empty($startDate)) {
            $from = $startDate . ' ' . (!empty($startTime) ? $startTime . ':00' : '00:00:00');
            $query->where('tap_in_time', '>=', $from);
        } elseif (!empty($endDate)) {
            $to = $endDate . ' ' . (!empty($endTime) ? $endTime . ':59' : '23:59:59');
            $query->where('tap_in_time', '<=', $to);
        }

        // Status filter
        if ($statusFilter === 'COMPLETED' || $statusFilter === 'PARKED') {
            $query->where('status', $statusFilter);
        }

        // Access type filter (Karcis Tiket vs Kartu RFID)
        if ($accessType === 'ticket') {
            $query->whereNotNull('ticket_code');
        } elseif ($accessType === 'card') {
            $query->whereNull('ticket_code')->whereNotNull('card_id');
        }

        // Search filter (ID, Kode Tiket, Nama Pengendara, Nomor HP, Plat Nomor, UID RFID)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('card', function ($qc) use ($search) {
                      $qc->where('uid', 'like', "%{$search}%")
                         ->orWhere('license_plate', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->orderBy('id', 'desc')->get();

        // Agregat Finansial & Operasional
        $totalRevenue = 0;
        $completedCount = 0;
        $parkedCount = 0;
        $totalMinutes = 0;
        $durationBuckets = ['< 1 Jam' => 0, '1-2 Jam' => 0, '3-4 Jam' => 0, '> 4 Jam' => 0];
        $paymentMethods = ['card' => 0, 'cash' => 0, 'qris' => 0];

        // Format data tren grafik berdasarkan filter chartMode (hourly, daily, monthly)
        $trendData = [];

        if ($chartMode === 'hourly') {
            for ($h = 6; $h <= 22; $h++) {
                $trendData[sprintf('%02d:00', $h)] = 0;
            }
        }

        foreach ($transactions as $t) {
            if ($t->status === 'COMPLETED') {
                $fee = (int) $t->total_fee;
                $totalRevenue += $fee;
                $completedCount++;
                $dur = (int) ($t->duration_minutes ?? 0);
                $totalMinutes += $dur;

                // Durasi
                if ($dur < 60) {
                    $durationBuckets['< 1 Jam']++;
                } elseif ($dur <= 120) {
                    $durationBuckets['1-2 Jam']++;
                } elseif ($dur <= 240) {
                    $durationBuckets['3-4 Jam']++;
                } else {
                    $durationBuckets['> 4 Jam']++;
                }

                // Metode Pembayaran
                $pm = $t->payment_method ?? (!empty($t->ticket_code) ? 'cash' : 'card');
                if (isset($paymentMethods[$pm])) {
                    $paymentMethods[$pm] += $fee;
                } else {
                    $paymentMethods['card'] += $fee;
                }

                // Kalkulasi data tren sesuai chartMode
                $refTime = $t->tap_out_time ? Carbon::parse($t->tap_out_time) : Carbon::parse($t->tap_in_time);
                if ($chartMode === 'hourly') {
                    $hKey = $refTime->format('H:00');
                    if (!isset($trendData[$hKey])) {
                        $trendData[$hKey] = 0;
                    }
                    $trendData[$hKey] += $fee;
                } elseif ($chartMode === 'monthly') {
                    $mKey = $refTime->format('M Y');
                    if (!isset($trendData[$mKey])) {
                        $trendData[$mKey] = 0;
                    }
                    $trendData[$mKey] += $fee;
                } else {
                    // Daily (Harian)
                    $dKey = $refTime->format('d M');
                    if (!isset($trendData[$dKey])) {
                        $trendData[$dKey] = 0;
                    }
                    $trendData[$dKey] += $fee;
                }
            } else {
                $parkedCount++;
            }
        }

        // Default jika tren harian/bulanan belum ada data
        if (empty($trendData)) {
            $trendData[Carbon::now()->format($chartMode === 'monthly' ? 'M Y' : 'd M')] = 0;
        }

        // Urutkan key jika harian/bulanan agar urut kronologis
        if ($chartMode === 'hourly') {
            ksort($trendData);
        }

        $avgMinutes = $completedCount > 0 ? round($totalMinutes / $completedCount) : 0;
        $avgFee = $completedCount > 0 ? round($totalRevenue / $completedCount) : 0;
        $maxTrendValue = !empty($trendData) ? max($trendData) : 0;
        $trendType = $chartMode;

        return view('owner.reports', compact(
            'transactions',
            'preset',
            'search',
            'startDate',
            'endDate',
            'startTime',
            'endTime',
            'statusFilter',
            'accessType',
            'chartMode',
            'totalRevenue',
            'completedCount',
            'parkedCount',
            'totalMinutes',
            'avgMinutes',
            'avgFee',
            'durationBuckets',
            'paymentMethods',
            'trendData',
            'trendType',
            'maxTrendValue'
        ));
    }

    /**
     * Ekspor laporan ke format file CSV untuk Owner.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $preset = $request->query('preset', 'all');
        $search = trim($request->query('search', ''));
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        $singleDate = $request->query('date', '');
        $startTime = $request->query('start_time', '');
        $endTime = $request->query('end_time', '');
        $statusFilter = $request->query('status', 'all');
        $accessType = $request->query('access_type', 'all');
        $floorFilter = $request->query('floor', 'all');

        if (!empty($singleDate)) {
            $startDate = $singleDate;
            $endDate = $singleDate;
        } elseif ($preset === 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'yesterday') {
            $startDate = Carbon::yesterday()->toDateString();
            $endDate = Carbon::yesterday()->toDateString();
        } elseif ($preset === 'week') {
            $startDate = Carbon::now()->subDays(6)->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($preset === 'month') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $query = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut']);

        if (!empty($startDate) && !empty($endDate)) {
            $from = $startDate . ' ' . (!empty($startTime) ? $startTime . ':00' : '00:00:00');
            $to = $endDate . ' ' . (!empty($endTime) ? $endTime . ':59' : '23:59:59');
            $query->whereBetween('tap_in_time', [$from, $to]);
        } elseif (!empty($startDate)) {
            $from = $startDate . ' ' . (!empty($startTime) ? $startTime . ':00' : '00:00:00');
            $query->where('tap_in_time', '>=', $from);
        } elseif (!empty($endDate)) {
            $to = $endDate . ' ' . (!empty($endTime) ? $endTime . ':59' : '23:59:59');
            $query->where('tap_in_time', '<=', $to);
        }

        if ($statusFilter === 'COMPLETED' || $statusFilter === 'PARKED') {
            $query->where('status', $statusFilter);
        }

        if ($accessType === 'ticket') {
            $query->whereNotNull('ticket_code');
        } elseif ($accessType === 'card') {
            $query->whereNull('ticket_code')->whereNotNull('card_id');
        }

        if ($floorFilter === '1') {
            $query->where('parking_floor', 1);
        } elseif ($floorFilter === '2') {
            $query->where('parking_floor', 2);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('card', function ($qc) use ($search) {
                      $qc->where('uid', 'like', "%{$search}%")
                         ->orWhere('license_plate', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->orderBy('id', 'desc')->get();
        $filename = 'Laporan_Owner_Parkir_' . date('Ymd_His') . '.xls';

        // Hitung total pendapatan terhitung
        $grandTotalRevenue = 0;
        foreach ($transactions as $t) {
            if ($t->status === 'COMPLETED') {
                $grandTotalRevenue += (int) ($t->total_fee ?? 0);
            }
        }

        $ownerName = auth()->user()->name ?? 'Owner Eksekutif';
        $ownerEmail = auth()->user()->email ?? 'owner@eparkir.local';

        return response()->stream(function () use ($transactions, $grandTotalRevenue, $singleDate, $preset, $ownerName, $ownerEmail) {
            $output = fopen('php://output', 'w');
            
            $filterText = !empty($singleDate) 
                ? Carbon::parse($singleDate)->format('d/m/Y') 
                : (($preset === 'all' || empty($preset)) ? 'SEMUA TANGGAL' : strtoupper($preset));

            $totalTxCount = count($transactions);
            $totalRevenueStr = 'Rp ' . number_format($grandTotalRevenue, 0, ',', '.');
            $waktuEkspor = date('d/m/Y H:i') . ' WIB';
            $tglCetak = date('d F Y');

            fwrite($output, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n");
            fwrite($output, '<head>' . "\n");
            fwrite($output, '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n");
            fwrite($output, '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Rincian Transaksi</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' . "\n");
            fwrite($output, '<style>' . "\n");
            fwrite($output, 'body, table, td, th { font-family: Calibri, Arial, sans-serif; font-size: 11pt; color: #000000; }' . "\n");
            fwrite($output, '.title { font-size: 16pt; font-weight: bold; text-align: center; }' . "\n");
            fwrite($output, '.subtitle { font-size: 11pt; color: #555555; text-align: center; }' . "\n");
            fwrite($output, 'table.report-table { border-collapse: collapse; width: 100%; margin-top: 10px; }' . "\n");
            fwrite($output, 'table.report-table th { background-color: #00719C; color: #FFFFFF; font-weight: bold; border: 1px solid #000000; padding: 8px 5px; text-align: center; font-size: 11pt; }' . "\n");
            fwrite($output, 'table.report-table td { border: 1px solid #CBD5E1; padding: 6px 5px; vertical-align: middle; }' . "\n");
            fwrite($output, '.center { text-align: center; }' . "\n");
            fwrite($output, '.right { text-align: right; }' . "\n");
            fwrite($output, '.bold { font-weight: bold; }' . "\n");
            fwrite($output, '.total-row td { background-color: #F1F5F9; border-top: 2px solid #000000; border-bottom: 2px solid #000000; font-weight: bold; }' . "\n");
            fwrite($output, '</style>' . "\n");
            fwrite($output, '</head>' . "\n");
            fwrite($output, '<body>' . "\n");

            // 1. Kop Surat & Metadata Dokumen
            fwrite($output, '<table style="width:100%; border:none;">' . "\n");
            fwrite($output, '<tr><td colspan="10" class="title">LAPORAN RINCIAN TRANSAKSI KENDARAAN PARKIR</td></tr>' . "\n");
            fwrite($output, '<tr><td colspan="10" class="subtitle">E-Parkir IoT Platform &bull; Portal Eksekutif Pemilik Usaha (Owner)</td></tr>' . "\n");
            fwrite($output, '<tr><td colspan="10">&nbsp;</td></tr>' . "\n");
            
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="2"><strong>Waktu Ekspor:</strong></td><td colspan="8">' . $waktuEkspor . '</td>' . "\n");
            fwrite($output, '</tr>' . "\n");

            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="2"><strong>Filter Tanggal:</strong></td><td colspan="8">' . $filterText . '</td>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '<tr><td colspan="10">&nbsp;</td></tr>' . "\n");
            fwrite($output, '</table>' . "\n");

            // 2. Tabel Data Transaksi (10 Kolom)
            fwrite($output, '<table class="report-table" border="1">' . "\n");
            fwrite($output, '<thead>' . "\n");
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<th style="width:40px; background-color:#00719C; color:#FFFFFF;">No</th>' . "\n");
            fwrite($output, '<th style="width:70px; background-color:#00719C; color:#FFFFFF;">ID</th>' . "\n");
            fwrite($output, '<th style="width:100px; background-color:#00719C; color:#FFFFFF;">Tipe Akses</th>' . "\n");
            fwrite($output, '<th style="width:80px; background-color:#00719C; color:#FFFFFF;">Lantai</th>' . "\n");
            fwrite($output, '<th style="width:180px; background-color:#00719C; color:#FFFFFF;">Identitas / Kode</th>' . "\n");
            fwrite($output, '<th style="width:160px; background-color:#00719C; color:#FFFFFF;">Pengendara</th>' . "\n");
            fwrite($output, '<th style="width:160px; background-color:#00719C; color:#FFFFFF;">Waktu</th>' . "\n");
            fwrite($output, '<th style="width:130px; background-color:#00719C; color:#FFFFFF;">Durasi</th>' . "\n");
            fwrite($output, '<th style="width:120px; background-color:#00719C; color:#FFFFFF;">Tarif (Rp)</th>' . "\n");
            fwrite($output, '<th style="width:90px; background-color:#00719C; color:#FFFFFF;">Status</th>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '</thead>' . "\n");
            fwrite($output, '<tbody>' . "\n");

            $no = 1;
            foreach ($transactions as $t) {
                $isTicket = !empty($t->ticket_code);
                $accessType = $isTicket ? 'KARCIS' : 'RFID';
                $floorStr = ($t->parking_floor == 2) ? 'LT 2' : 'LT 1';

                if ($isTicket) {
                    $codeOrUid = htmlspecialchars($t->ticket_code);
                } else {
                    $plate = htmlspecialchars($t->card->license_plate ?? 'NON-PLAT');
                    $uid = htmlspecialchars($t->card->uid ?? '-');
                    $codeOrUid = "{$plate} (UID: {$uid})";
                }

                $name = htmlspecialchars($isTicket ? 'Pengendara Umum' : ($t->user->name ?? 'Member Parkir'));

                $timeInStr = $t->tap_in_time ? Carbon::parse($t->tap_in_time)->format('d/m/Y H:i') : '-';
                if ($t->tap_out_time) {
                    $timeStr = $timeInStr . ' s/d ' . Carbon::parse($t->tap_out_time)->format('H:i');
                } else {
                    $timeStr = $timeInStr . ' (Belum Keluar)';
                }

                $durMin = (int) ($t->duration_minutes ?? 0);
                $billedHours = ceil(max(1, $durMin) / 60);
                $durationStr = ($t->status === 'COMPLETED') ? "{$durMin} mnt (± {$billedHours} Jam)" : '-';
                $fee = (int) ($t->total_fee ?? 0);
                $feeFormatted = 'Rp ' . number_format($fee, 0, ',', '.');
                $statusStr = ($t->status === 'COMPLETED') ? 'SELESAI' : 'PARKIR';

                fwrite($output, '<tr>' . "\n");
                fwrite($output, '<td class="center">' . $no++ . '</td>' . "\n");
                fwrite($output, '<td class="center bold">#' . $t->id . '</td>' . "\n");
                fwrite($output, '<td class="center">' . $accessType . '</td>' . "\n");
                fwrite($output, '<td class="center bold">' . $floorStr . '</td>' . "\n");
                fwrite($output, '<td>' . $codeOrUid . '</td>' . "\n");
                fwrite($output, '<td>' . $name . '</td>' . "\n");
                fwrite($output, '<td class="center">' . $timeStr . '</td>' . "\n");
                fwrite($output, '<td class="center">' . $durationStr . '</td>' . "\n");
                fwrite($output, '<td class="right bold">' . $feeFormatted . '</td>' . "\n");
                fwrite($output, '<td class="center bold">' . $statusStr . '</td>' . "\n");
                fwrite($output, '</tr>' . "\n");
            }

            // 3. Baris Total Pendapatan Sesuai Filter
            fwrite($output, '<tr class="total-row">' . "\n");
            fwrite($output, '<td colspan="7" class="right" style="font-size:12pt; font-weight:bold; padding:10px;">TOTAL PENDAPATAN :</td>' . "\n");
            fwrite($output, '<td colspan="3" class="right" style="font-size:14pt; font-weight:bold; color:#00719C; padding:10px;">' . $totalRevenueStr . '</td>' . "\n");
            fwrite($output, '</tr>' . "\n");

            fwrite($output, '</tbody>' . "\n");
            fwrite($output, '</table>' . "\n");

            // 4. Lembar Tanda Tangan Resmi
            fwrite($output, '<br><br>' . "\n");
            fwrite($output, '<table style="width:100%; border:none;">' . "\n");
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="7">&nbsp;</td>' . "\n");
            fwrite($output, '<td colspan="3" class="right">Jakarta, ' . $tglCetak . '</td>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="7">&nbsp;</td>' . "\n");
            fwrite($output, '<td colspan="3" class="right"><strong>Pemilik Usaha (Owner),</strong></td>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '<tr><td colspan="10" style="height:45px;">&nbsp;</td></tr>' . "\n");
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="7">&nbsp;</td>' . "\n");
            fwrite($output, '<td colspan="3" class="right"><u><strong>( ' . htmlspecialchars($ownerName) . ' )</strong></u></td>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '<tr>' . "\n");
            fwrite($output, '<td colspan="7">&nbsp;</td>' . "\n");
            fwrite($output, '<td colspan="3" class="right"><span style="color:#666666; font-size:9pt;">ID Akun: ' . htmlspecialchars($ownerEmail) . '</span></td>' . "\n");
            fwrite($output, '</tr>' . "\n");
            fwrite($output, '</table>' . "\n");

            fwrite($output, '</body>' . "\n");
            fwrite($output, '</html>' . "\n");

            fclose($output);
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
