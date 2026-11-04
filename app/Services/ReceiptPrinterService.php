<?php

namespace App\Services;

use App\Models\ParkingTransaction;
use Illuminate\Support\Facades\Log;

class ReceiptPrinterService
{
    /**
     * Deteksi nama printer thermal yang terpasang di Windows
     */
    public static function findWindowsThermalPrinter(): ?string
    {
        $configured = env('THERMAL_PRINTER_NAME');
        if (!empty($configured)) {
            return $configured;
        }

        return null;
    }

    /**
     * Format teks 32 kolom untuk Printer 58mm (Eppos EP589 V2)
     */
    public static function format32Columns(ParkingTransaction $tx): string
    {
        $now = now();
        $inTime = $tx->tap_in_time ?: $now;
        $outTime = $tx->tap_out_time ?: $now;
        $durationMinutes = $tx->duration_minutes ?? max(1, (int) ceil($inTime->diffInSeconds($outTime) / 60));
        $billedHours = max(1, (int) ceil($durationMinutes / 60));
        $totalFee = $tx->total_fee ?? ($billedHours * 2000);

        $isTicket = !empty($tx->ticket_code);
        $trxCode = $isTicket ? $tx->ticket_code : ('TRX-' . str_pad($tx->id, 5, '0', STR_PAD_LEFT));
        $userName = $tx->user ? $tx->user->name : ($isTicket ? 'Pengendara Umum' : 'Pengendara');
        $uid = $isTicket ? $tx->ticket_code : ($tx->card ? $tx->card->uid : '-');
        $plate = $tx->card && $tx->card->license_plate ? $tx->card->license_plate : 'Non-Plat';
        $gateOut = $tx->deviceOut ? $tx->deviceOut->device_code : 'Gate-01';

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
        $lines[] = $padBetween("No. Trx     :", $trxCode);
        $lines[] = $padBetween("Pos Keluar  :", $gateOut);
        $lines[] = $padBetween("Waktu Cetak :", date('d/m/Y H:i'));
        $lines[] = "--------------------------------";
        if ($isTicket) {
            $lines[] = $padBetween("Kode Tiket  :", $uid);
            $lines[] = $padBetween("Pengendara  :", mb_strimwidth($userName, 0, 16, '..'));
        } else {
            $lines[] = $padBetween("UID Kartu   :", $uid);
            $lines[] = $padBetween("Pengendara  :", mb_strimwidth($userName, 0, 16, '..'));
            $lines[] = $padBetween("No. Plat    :", $plate);
        }
        $floor = $tx->parking_floor ?? 1;
        $lines[] = $padBetween("Lantai      :", "LANTAI {$floor}");
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("Waktu Masuk :", $inTime->format('d/m/Y H:i'));
        $lines[] = $padBetween("Waktu Keluar:", $outTime->format('d/m/Y H:i'));
        $lines[] = $padBetween("Durasi      :", "{$durationMinutes} Mnt ({$billedHours} Jam)");
        $lines[] = $padBetween("Tarif / Jam :", "Rp 2.000");
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("TOTAL BIAYA :", 'Rp ' . number_format($totalFee, 0, ',', '.'));
        $lines[] = $padBetween("Metode Bayar:", $isTicket ? "Tunai / Kasir" : "Potong Saldo Kartu RFID");
        if (!$isTicket) {
            $lines[] = $padBetween("Saldo Awal  :", 'Rp ' . number_format($tx->balance_before ?? 0, 0, ',', '.'));
            $lines[] = $padBetween("Sisa Saldo  :", 'Rp ' . number_format($tx->balance_after ?? 0, 0, ',', '.'));
        } else {
            $lines[] = $padBetween("Status Bayar:", "Lunas di Pos Keluar");
        }
        $lines[] = "--------------------------------";
        $lines[] = $center("Hati-Hati Di Perjalanan");
        $lines[] = $center(date('d/m/Y H:i') . " WIB");
        $lines[] = "\n\n\n";

        return implode("\n", $lines);
    }

    /**
     * Format Struk Tiket Masuk Karcis (32 Kolom POS-58)
     */
    public static function format32TicketEntry(ParkingTransaction $tx): string
    {
        $inTime = $tx->tap_in_time ? \Carbon\Carbon::parse($tx->tap_in_time) : now();
        $ticketCode = $tx->ticket_code ?: ('TKT-' . $inTime->format('ymd') . '-' . str_pad($tx->id, 4, '0', STR_PAD_LEFT));
        $gateIn = $tx->deviceIn ? $tx->deviceIn->device_code : 'Gate-01 (In)';

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
        $lines[] = $center("KODE TIKET PARKIR:");
        $lines[] = $center(">>> " . $ticketCode . " <<<");
        $lines[] = "--------------------------------";
        $lines[] = $padBetween("Pos Masuk   :", $gateIn);
        $lines[] = $padBetween("Waktu Masuk :", $inTime->format('d/m/Y H:i'));
        $floor = $tx->parking_floor ?? 1;
        $lines[] = $padBetween("Lantai      :", "LANTAI {$floor} (LT {$floor})");
        $lines[] = $padBetween("Tarif / Jam :", "Rp 2.000");
        $lines[] = "--------------------------------";
        $lines[] = $center("[ SCAN QR CODE TIKET ]");
        $lines[] = "\n\n\n";

        return implode("\n", $lines);
    }

    /**
     * Kirim teks struk secara langsung ke antrean printer Windows (jika printer terpasang)
     */
    public static function autoPrintDirect(ParkingTransaction $tx): bool
    {
        $printer = self::findWindowsThermalPrinter();
        if (!$printer) {
            return false;
        }

        $rawText = self::format32Columns($tx);

        try {
            $dir = storage_path('app/receipts');
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $tempFile = $dir . '/print_' . $tx->id . '_' . time() . '.txt';
            file_put_contents($tempFile, $rawText);

            $escapedFile = addslashes($tempFile);
            $escapedPrinter = addslashes($printer);

            $psCmd = "powershell -NoProfile -Command \"Get-Content -Path '{$escapedFile}' | Out-Printer -Name '{$escapedPrinter}'\"";
            exec($psCmd, $out, $returnCode);

            @unlink($tempFile);
            Log::info("Auto-print transaksi #{$tx->id} ke printer '{$printer}' status: " . ($returnCode === 0 ? 'SUKSES' : 'GAGAL'));
            return $returnCode === 0;
        } catch (\Throwable $e) {
            Log::error("Gagal auto-print transaksi #{$tx->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim tiket masuk secara langsung ke printer Windows
     */
    public static function autoPrintTicketDirect(ParkingTransaction $tx): bool
    {
        $printer = self::findWindowsThermalPrinter();
        if (!$printer) {
            return false;
        }

        $rawText = self::format32TicketEntry($tx);

        try {
            $dir = storage_path('app/receipts');
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $tempFile = $dir . '/ticket_' . $tx->id . '_' . time() . '.txt';
            file_put_contents($tempFile, $rawText);

            $escapedFile = addslashes($tempFile);
            $escapedPrinter = addslashes($printer);

            $psCmd = "powershell -NoProfile -Command \"Get-Content -Path '{$escapedFile}' | Out-Printer -Name '{$escapedPrinter}'\"";
            exec($psCmd, $out, $returnCode);

            @unlink($tempFile);
            Log::info("Auto-print tiket karcis #{$tx->id} ke printer '{$printer}' status: " . ($returnCode === 0 ? 'SUKSES' : 'GAGAL'));
            return $returnCode === 0;
        } catch (\Throwable $e) {
            Log::error("Gagal auto-print tiket #{$tx->id}: " . $e->getMessage());
            return false;
        }
    }
}
