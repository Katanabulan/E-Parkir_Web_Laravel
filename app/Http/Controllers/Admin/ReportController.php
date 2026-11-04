<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
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

        // Date range
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

        // Search
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

        // Agregat
        $totalRevenue = 0;
        $completedCount = 0;
        $parkedCount = 0;
        $totalMinutes = 0;
        $durationBuckets = ['< 1 Jam' => 0, '1-2 Jam' => 0, '3-4 Jam' => 0, '> 4 Jam' => 0];

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

                if ($dur < 60) {
                    $durationBuckets['< 1 Jam']++;
                } elseif ($dur <= 120) {
                    $durationBuckets['1-2 Jam']++;
                } elseif ($dur <= 240) {
                    $durationBuckets['3-4 Jam']++;
                } else {
                    $durationBuckets['> 4 Jam']++;
                }

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

        if (empty($trendData)) {
            $trendData[Carbon::now()->format($chartMode === 'monthly' ? 'M Y' : 'd M')] = 0;
        }

        if ($chartMode === 'hourly') {
            ksort($trendData);
        }

        $avgMinutes = $completedCount > 0 ? round($totalMinutes / $completedCount) : 0;
        $avgFee = $completedCount > 0 ? round($totalRevenue / $completedCount) : 0;
        $maxTrendValue = !empty($trendData) ? max($trendData) : 0;
        $trendType = $chartMode;

        return view('admin.reports', compact(
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
            'trendData',
            'trendType',
            'maxTrendValue'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $preset = $request->query('preset', 'all');
        $search = trim($request->query('search', ''));
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');
        $startTime = $request->query('start_time', '');
        $endTime = $request->query('end_time', '');
        $statusFilter = $request->query('status', 'all');
        $accessType = $request->query('access_type', 'all');

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

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Laporan_Parkir_' . date('Ymd_His') . '.csv',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'No',
                'ID Transaksi',
                'Nama Pengendara',
                'Plat Nomor',
                'UID Kartu',
                'Gerbang Masuk',
                'Waktu Masuk',
                'Gerbang Keluar',
                'Waktu Keluar',
                'Durasi (Menit)',
                'Durasi Dibulatkan (Jam)',
                'Biaya Masuk Kas (Rp)',
                'Saldo Sebelum (Rp)',
                'Sisa Saldo (Rp)',
                'Status'
            ]);

            $no = 1;
            $totalFeeSum = 0;
            foreach ($transactions as $row) {
                $durMin = (int) ($row->duration_minutes ?? 0);
                $billedHours = ($row->status === 'COMPLETED') ? (int) ceil(max(1, $durMin) / 60) : '-';
                $fee = (int) ($row->total_fee ?? 0);
                if ($row->status === 'COMPLETED') {
                    $totalFeeSum += $fee;
                }

                $isTicket = !empty($row->ticket_code);
                $userName = $isTicket ? 'Pengendara Umum' : ($row->user ? $row->user->name : 'Umum / Tamu');
                $plat = $isTicket ? '-' : ($row->card && $row->card->license_plate ? $row->card->license_plate : '-');
                $codeOrUid = $isTicket ? $row->ticket_code : ($row->card ? $row->card->uid : '-');

                fputcsv($handle, [
                    $no++,
                    '#' . $row->id,
                    $userName,
                    $plat,
                    $codeOrUid,
                    $row->deviceIn ? $row->deviceIn->device_code : '-',
                    $row->tap_in_time ? $row->tap_in_time->toDateTimeString() : '-',
                    $row->deviceOut ? $row->deviceOut->device_code : '-',
                    $row->tap_out_time ? $row->tap_out_time->toDateTimeString() : '-',
                    $durMin,
                    $billedHours,
                    $fee,
                    $row->balance_before !== null ? $row->balance_before : '-',
                    $row->balance_after !== null ? $row->balance_after : '-',
                    $row->status === 'COMPLETED' ? 'SELESAI' : 'SEDANG PARKIR'
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', '', '', 'TOTAL PENDAPATAN', '', $totalFeeSum, '', '', '']);

            fclose($handle);
        }, 200, $headers);
    }
}
