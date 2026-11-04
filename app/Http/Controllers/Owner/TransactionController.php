<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    /**
     * Tampilkan halaman khusus Rincian & Audit Transaksi untuk Owner.
     */
    public function index(Request $request)
    {
        $preset = $request->query('preset', 'all');
        $search = trim($request->query('search', ''));
        $date = $request->query('date', $request->query('start_date', ''));
        $statusFilter = $request->query('status', 'all');
        $accessType = $request->query('access_type', 'all');
        $floorFilter = $request->query('floor', 'all');

        // Preset handling
        if ($preset === 'today') {
            $date = Carbon::today()->toDateString();
        } elseif ($preset === 'yesterday') {
            $date = Carbon::yesterday()->toDateString();
        } elseif ($preset === 'all') {
            $date = '';
        }

        $query = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut']);

        // Filter Satu Tanggal
        if (!empty($date)) {
            $query->whereDate('tap_in_time', $date);
        }

        // Status filter
        if ($statusFilter === 'COMPLETED') {
            $query->where('status', 'COMPLETED');
        } elseif ($statusFilter === 'PARKED') {
            $query->where('status', 'PARKED');
        }

        // Tipe akses (Karcis vs Kartu RFID)
        if ($accessType === 'ticket') {
            $query->whereNotNull('ticket_code');
        } elseif ($accessType === 'card') {
            $query->whereNull('ticket_code');
        }

        // Filter Lantai (LT 1 / LT 2)
        if ($floorFilter === '1') {
            $query->where('parking_floor', 1);
        } elseif ($floorFilter === '2') {
            $query->where('parking_floor', 2);
        }

        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('card', function ($qc) use ($search) {
                      $qc->where('uid', 'like', "%{$search}%")
                         ->orWhere('license_plate', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();

        // Agregat singkat untuk ringkasan di atas tabel
        $totalTransactionsCount = $transactions->total();

        // Hitung total finansial dari query filter saat ini untuk kop cetak print
        $totalRevenueFiltered = (clone $query)->where('status', 'COMPLETED')->sum('total_fee');
        $completedCountFiltered = (clone $query)->where('status', 'COMPLETED')->count();

        return view('owner.transactions', compact(
            'transactions',
            'preset',
            'search',
            'date',
            'statusFilter',
            'accessType',
            'floorFilter',
            'totalTransactionsCount',
            'totalRevenueFiltered',
            'completedCountFiltered'
        ));
    }
}

