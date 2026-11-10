<?php

namespace App\Http\Controllers;

use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReceiptController extends Controller
{
    /**
     * Tampilkan struk parkir 58mm (Thermal Printer Eppos EP589 V2)
     */
    public function show($id, Request $request)
    {
        $tx = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut'])->findOrFail($id);

        $now = Carbon::now();
        $inTime = $tx->tap_in_time ? Carbon::parse($tx->tap_in_time) : $now;
        $outTime = $tx->tap_out_time ? Carbon::parse($tx->tap_out_time) : $now;

        $durationMinutes = $tx->duration_minutes ?? max(1, (int) ceil($inTime->diffInSeconds($outTime) / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $tx->total_fee ?? ($billedHours * 2000);

        $forceType = $request->query('type', '');
        $isEntryTicket = ($forceType === 'ticket') || ($tx->status === 'PARKED' && $forceType !== 'receipt');
        $isTicket = !empty($tx->ticket_code);

        // Kode Tiket yang GEDE & JELAS
        $ticketCode = $tx->ticket_code ?: ('TKT-' . $inTime->format('ymd') . '-' . str_pad($tx->id, 4, '0', STR_PAD_LEFT));
        $trxCode = $isTicket ? $ticketCode : ('TRX-' . str_pad($tx->id, 5, '0', STR_PAD_LEFT));

        $vNumber = '-';
        if ($tx->card && !empty($tx->card->license_plate) && $tx->card->license_plate !== '-') {
            $vNumber = $tx->card->license_plate;
        } elseif ($isTicket) {
            $vNumber = 'Tamu Umum';
        } else {
            $vNumber = 'Non-Plat';
        }

        $receiptData = [
            'id' => $tx->id,
            'is_entry_ticket' => $isEntryTicket,
            'is_ticket' => $isTicket,
            'ticket_code' => $ticketCode,
            'trx_code' => $trxCode,
            'area_name' => 'E-Parkir',
            'area_tagline' => 'Sistem Parkir Otomatis IoT',
            'area_address' => 'Jl. Jenggolo Raya',
            'v_number' => $vNumber,
            'user_name' => $tx->user ? $tx->user->name : ($isTicket ? 'Pengendara Umum' : 'Member Pengendara'),
            'is_member' => ($tx->user_id || $tx->card_id),
            'parking_floor' => $tx->parking_floor ?? 1,
            'uid' => $tx->card ? $tx->card->uid : ($isTicket ? $ticketCode : '-'),
            'license_plate' => $vNumber,
            'gate_in' => $tx->deviceIn ? $tx->deviceIn->device_code : 'Gate-01 (In)',
            'gate_out' => $tx->deviceOut ? $tx->deviceOut->device_code : 'Gate-01 (Out)',
            'tap_in_time' => $inTime->format('d/m/Y H:i'),
            'tap_out_time' => $outTime->format('d/m/Y H:i'),
            'duration_minutes' => $durationMinutes,
            'billed_hours' => $billedHours,
            'rate_per_hour' => 2000,
            'rate_display' => 'Rp 2.000 / Jam',
            'total_fee' => $totalFee,
            'total_fee_formatted' => 'Rp ' . number_format($totalFee, 0, ',', '.'),
            'payment_method' => in_array($tx->payment_method, ['card', 'balance']) ? 'Potong Saldo Kartu RFID' : ($isTicket ? 'Karcis / Tunai di Kasir' : 'Tunai di Kasir'),
            'balance_before' => $tx->balance_before ?? 0,
            'balance_before_formatted' => 'Rp ' . number_format($tx->balance_before ?? 0, 0, ',', '.'),
            'balance_after' => $tx->balance_after ?? 0,
            'balance_after_formatted' => 'Rp ' . number_format($tx->balance_after ?? 0, 0, ',', '.'),
            'status' => $tx->status,
            'print_time' => $now->format('d/m/Y H:i') . ' WIB',
            'qr_data' => $ticketCode,
            'pay_ticket_url' => url('/status') . '?pay_ticket=' . urlencode($ticketCode),
        ];

        // Format teks 32 kolom untuk Printer Thermal 58mm (Eppos EP589 V2)
        $thermalText = $isEntryTicket 
            ? $this->generate32EntryTicketText($receiptData) 
            : $this->generate32PaymentReceiptText($receiptData);

        $autoprint = $request->boolean('autoprint', false);

        return view('receipt', compact('receiptData', 'thermalText', 'autoprint'));
    }

    /**
     * Endpoint API JSON untuk kebutuhan Web Bluetooth ESC/POS
     */
    public function escposData($id, ?Request $request = null)
    {
        $request = $request ?: request();
        if ($id === 'latest') {
            $tx = ParkingTransaction::whereIn('status', ['COMPLETED', 'SELESAI'])
                ->orderBy('id', 'desc')
                ->first();
            if (!$tx) {
                $tx = ParkingTransaction::orderBy('id', 'desc')->firstOrFail();
            }
        } else {
            $tx = ParkingTransaction::with(['card', 'user', 'deviceIn', 'deviceOut'])->findOrFail($id);
        }

        $now = Carbon::now();
        $inTime = $tx->tap_in_time ? Carbon::parse($tx->tap_in_time) : $now;
        $outTime = $tx->tap_out_time ? Carbon::parse($tx->tap_out_time) : $now;

        $durationMinutes = $tx->duration_minutes ?? max(1, (int) ceil($inTime->diffInSeconds($outTime) / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $tx->total_fee ?? ($billedHours * 2000);

        $forceType = $request->query('type', '');
        $isEntryTicket = ($forceType === 'ticket') || ($tx->status === 'PARKED' && $forceType !== 'receipt');
        $isTicket = !empty($tx->ticket_code);
        $ticketCode = $tx->ticket_code ?: ('TKT-' . $inTime->format('ymd') . '-' . str_pad($tx->id, 4, '0', STR_PAD_LEFT));
        $trxCode = $isTicket ? $ticketCode : ('TRX-' . str_pad($tx->id, 5, '0', STR_PAD_LEFT));

        $vNumber = '-';
        if ($tx->card && !empty($tx->card->license_plate) && $tx->card->license_plate !== '-') {
            $vNumber = $tx->card->license_plate;
        } elseif ($isTicket) {
            $vNumber = 'Tamu Umum';
        } else {
            $vNumber = 'Non-Plat';
        }

        $receiptData = [
            'id' => $tx->id,
            'is_entry_ticket' => $isEntryTicket,
            'is_ticket' => $isTicket,
            'ticket_code' => $ticketCode,
            'trx_code' => $trxCode,
            'area_name' => 'E-Parkir',
            'area_tagline' => 'Sistem Parkir Otomatis IoT',
            'area_address' => 'Jl. Jenggolo Raya',
            'v_number' => $vNumber,
            'user_name' => $tx->user ? $tx->user->name : ($isTicket ? 'Pengendara Umum' : 'Member Pengendara'),
            'is_member' => ($tx->user_id || $tx->card_id),
            'parking_floor' => $tx->parking_floor ?? 1,
            'uid' => $tx->card ? $tx->card->uid : ($isTicket ? $ticketCode : '-'),
            'license_plate' => $vNumber,
            'gate_in' => $tx->deviceIn ? $tx->deviceIn->device_code : 'Gate-01 (In)',
            'gate_out' => $tx->deviceOut ? $tx->deviceOut->device_code : 'Gate-01 (Out)',
            'tap_in_time' => $inTime->format('d/m/Y H:i'),
            'tap_out_time' => $outTime->format('d/m/Y H:i'),
            'duration_minutes' => $durationMinutes,
            'billed_hours' => $billedHours,
            'rate_per_hour' => 2000,
            'rate_display' => 'Rp 2.000 / Jam',
            'total_fee' => $totalFee,
            'total_fee_formatted' => 'Rp ' . number_format($totalFee, 0, ',', '.'),
            'payment_method' => in_array($tx->payment_method, ['card', 'balance']) ? 'Potong Saldo Kartu RFID' : ($isTicket ? 'Karcis / Tunai di Kasir' : 'Tunai di Kasir'),
            'balance_before' => $tx->balance_before ?? 0,
            'balance_before_formatted' => 'Rp ' . number_format($tx->balance_before ?? 0, 0, ',', '.'),
            'balance_after' => $tx->balance_after ?? 0,
            'balance_after_formatted' => 'Rp ' . number_format($tx->balance_after ?? 0, 0, ',', '.'),
            'status' => $tx->status,
            'print_time' => $now->format('d/m/Y H:i') . ' WIB',
            'qr_data' => $ticketCode,
            'pay_ticket_url' => url('/status') . '?pay_ticket=' . urlencode($ticketCode),
        ];

        $thermalText = $isEntryTicket 
            ? $this->generate32EntryTicketText($receiptData) 
            : $this->generate32PaymentReceiptText($receiptData);

        return response()->json([
            'status' => 'success',
            'type' => $isEntryTicket ? 'ticket_entry' : 'payment_receipt',
            'receipt' => $receiptData,
            'raw_text' => $thermalText
        ]);
    }

    /**
     * Struk transaksi selesai terbaru (latest)
     */
    public function latest(Request $request)
    {
        $latest = ParkingTransaction::where('status', 'COMPLETED')
            ->orderBy('id', 'desc')
            ->first();

        if (!$latest) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada transaksi parkir selesai'
            ], 404);
        }

        return $this->show($latest->id, $request);
    }

    /**
     * Data ESC/POS untuk tiket masuk karcis terbaru (digunakan oleh auto-print monitoring)
     */
    public function latestTicketEscpos()
    {
        $tx = ParkingTransaction::whereNotNull('ticket_code')
            ->orderBy('id', 'desc')
            ->first();

        if (!$tx) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada data tiket karcis masuk'
            ], 404);
        }

        $rawText = \App\Services\ReceiptPrinterService::format32TicketEntry($tx);

        return response()->json([
            'status' => 'success',
            'ticket_code' => $tx->ticket_code,
            'transaction_id' => $tx->id,
            'raw_text' => $rawText,
            'receipt_url' => route('receipt.show', $tx->id) . '?type=ticket'
        ]);
    }

    /**
     * Helper 1: Pemformat 32 kolom untuk KARCIS MASUK PARKIR (Kode Gede + Info Member Bayar Saldo via QR)
     */
    private function generate32EntryTicketText(array $d): string
    {
        $padBetween = function(string $left, string $right, int $width = 32): string {
            $space = max(1, $width - mb_strlen($left) - mb_strlen($right));
            return $left . str_repeat(' ', $space) . $right;
        };

        $center = function(string $str, int $width = 32): string {
            $len = mb_strlen($str);
            if ($len >= $width) return substr($str, 0, $width);
            $leftPad = (int) floor(($width - $len) / 2);
            $rightPad = $width - $len - $leftPad;
            return str_repeat(' ', $leftPad) . $str . str_repeat(' ', $rightPad);
        };

        $lines = [];
        $lines[] = $center("E-PARKIR");
        $lines[] = $center("Sistem Parkir Otomatis IoT");
        $lines[] = "--------------------------------";
        $lines[] = $center("KARCIS MASUK PARKIR");
        $lines[] = "--------------------------------";
        $lines[] = $center("||| | |||| | || ||||| | ||| ||||");
        $lines[] = $center("* " . $d['ticket_code'] . " *");
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("Pos Masuk   :", $d['gate_in']);
        $lines[] = $padBetween("Waktu Masuk :", substr($d['tap_in_time'], 0, 16));
        $floor = $d['parking_floor'] ?? 1;
        $lines[] = $padBetween("Lantai      :", "LANTAI {$floor} (LT {$floor})");
        $lines[] = $padBetween("Tarif / Jam :", "Rp 2.000");
        $lines[] = "--------------------------------";
        $lines[] = "\n\n\n";

        return implode("\n", $lines);
    }

    /**
     * Helper 2: Pemformat 32 kolom untuk STRUK BUKTI PEMBAYARAN KELUAR CLIENT (Lunas)
     */
    private function generate32PaymentReceiptText(array $d): string
    {
        $padBetween = function(string $left, string $right, int $width = 32): string {
            $space = max(1, $width - mb_strlen($left) - mb_strlen($right));
            return $left . str_repeat(' ', $space) . $right;
        };

        $center = function(string $str, int $width = 32): string {
            $len = mb_strlen($str);
            if ($len >= $width) return substr($str, 0, $width);
            $leftPad = (int) floor(($width - $len) / 2);
            $rightPad = $width - $len - $leftPad;
            return str_repeat(' ', $leftPad) . $str . str_repeat(' ', $rightPad);
        };

        $lines = [];
        $lines[] = $center("E-PARKIR");
        $lines[] = $center("Sistem Parkir Otomatis IoT");
        $lines[] = $center("Jl. Jenggolo Raya");
        $lines[] = "================================";
        $lines[] = $center("BUKTI PEMBAYARAN PARKIR");
        $lines[] = $center("*** STATUS: LUNAS ***");
        $lines[] = "================================";
        $lines[] = $padBetween("No. Trx     :", $d['trx_code']);
        $lines[] = $padBetween("Pengendara  :", mb_strimwidth($d['user_name'], 0, 16, '..'));
        $lines[] = $padBetween("Plat / UID  :", mb_strimwidth($d['license_plate'] !== '-' ? $d['license_plate'] : $d['uid'], 0, 16, '..'));
        $floor = $d['parking_floor'] ?? 1;
        $lines[] = $padBetween("Lantai      :", "LANTAI {$floor}");
        $lines[] = $padBetween("Pos Keluar  :", $d['gate_out']);
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("Waktu Masuk :", substr($d['tap_in_time'], 0, 16));
        $lines[] = $padBetween("Waktu Keluar:", substr($d['tap_out_time'], 0, 16));
        $lines[] = $padBetween("Durasi      :", "{$d['duration_minutes']} Mnt ({$d['billed_hours']} Jam)");
        $lines[] = $padBetween("Tarif / Jam :", "Rp 2.000");
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("TOTAL BIAYA :", $d['total_fee_formatted']);
        $lines[] = $padBetween("Metode Bayar:", mb_strimwidth($d['payment_method'], 0, 18, '..'));
        if (!empty($d['is_member'])) {
            $lines[] = $padBetween("Saldo Awal  :", $d['balance_before_formatted']);
            $lines[] = $padBetween("Sisa Saldo  :", $d['balance_after_formatted']);
        }
        $lines[] = "--------------------------------";
        $lines[] = $center("Hati-Hati Di Perjalanan");
        $lines[] = $center(date('d/m/Y H:i') . " WIB");
        $lines[] = "\n\n\n";

        return implode("\n", $lines);
    }
}
