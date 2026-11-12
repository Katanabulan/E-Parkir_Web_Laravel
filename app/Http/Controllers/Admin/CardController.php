<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidCard;
use App\Models\User;
use App\Models\AccessLog;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->query('search', ''));
        $status = trim($request->query('status', ''));

        $query = RfidCard::with(['user', 'activeTransaction']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('uid', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($status) && in_array($status, ['active', 'blocked'])) {
            $query->where('status', $status);
        }

        $cards = $query->orderBy('id', 'desc')->get();

        $totalCards = RfidCard::count();
        $activeCards = RfidCard::where('status', 'active')->count();
        $blockedCards = RfidCard::where('status', 'blocked')->count();

        $pendingMembers = User::where('role', 'client')
            ->doesntHave('rfidCard')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.cards', compact(
            'cards',
            'totalCards',
            'activeCards',
            'blockedCards',
            'pendingMembers',
            'search',
            'status'
        ));
    }

    public function store(Request $request)
    {
        $userId = (int) $request->input('user_id', 0);
        $uid = strtoupper(trim($request->input('uid', '')));
        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $phone = trim($request->input('phone', ''));
        $licensePlate = strtoupper(trim($request->input('license_plate', '')));
        $avatar = trim($request->input('avatar', ''));
        $password = trim($request->input('password', ''));
        $status = in_array($request->input('status'), ['active', 'blocked']) ? $request->input('status') : 'active';
        $balance = max(0, (int) $request->input('balance', 0));

        if (empty($uid)) {
            return redirect()->route('admin.cards.index')->with('error', 'UID Kartu RFID wajib diisi!');
        }

        if (RfidCard::where('uid', $uid)->exists()) {
            return redirect()->route('admin.cards.index')->with('error', "UID Kartu '{$uid}' sudah terdaftar dalam sistem!");
        }

        if (empty($name)) {
            return redirect()->route('admin.cards.index')->with('error', 'Nama pemilik / label kartu wajib diisi!');
        }

        if (empty($email) && $userId <= 0) {
            $email = "kartu_" . strtolower($uid) . "@eparkir.local";
        }

        $targetUser = null;
        if ($userId > 0) {
            $targetUser = User::where('role', 'client')->find($userId);
            if (!$targetUser) {
                return redirect()->route('admin.cards.index')->with('error', 'Data pendaftar mobile tidak ditemukan!');
            }
            if ($targetUser->rfidCard()->exists()) {
                return redirect()->route('admin.cards.index')->with('error', "Akun '{$targetUser->name}' sudah memiliki kartu RFID!");
            }
            if (empty($email)) {
                $email = $targetUser->email;
            }
            if (User::whereRaw('LOWER(email) = ?', [strtolower($email)])->where('id', '!=', $targetUser->id)->exists()) {
                return redirect()->route('admin.cards.index')->with('error', "Email '{$email}' sudah digunakan akun lain!");
            }
        } else {
            $existingUser = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($existingUser) {
                if ($existingUser->role !== 'client') {
                    return redirect()->route('admin.cards.index')->with('error', "Email '{$email}' sudah digunakan oleh akun Admin/Owner!");
                }
                if ($existingUser->rfidCard()->exists()) {
                    return redirect()->route('admin.cards.index')->with('error', "Akun dengan email '{$email}' sudah memiliki kartu RFID aktif!");
                }
                $targetUser = $existingUser;
            } elseif (empty($password)) {
                return redirect()->route('admin.cards.index')->with('error', 'Password akun pengendara wajib diisi untuk pendaftar baru!');
            }
        }

        $card = DB::transaction(function () use ($targetUser, $uid, $name, $email, $phone, $licensePlate, $avatar, $password, $status, $balance) {
            if ($targetUser) {
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => !empty($phone) ? $phone : $targetUser->phone,
                    'license_plate' => !empty($licensePlate) ? $licensePlate : $targetUser->license_plate,
                ];
                if (!empty($password)) {
                    $userData['password'] = Hash::make($password);
                }
                if (!empty($avatar)) {
                    $userData['avatar'] = $avatar;
                }
                $targetUser->update($userData);
                $user = $targetUser;
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'phone' => !empty($phone) ? $phone : '0812000000',
                    'license_plate' => !empty($licensePlate) ? $licensePlate : null,
                    'avatar' => !empty($avatar) ? $avatar : null,
                    'role' => 'client',
                ]);
            }

            return RfidCard::create([
                'user_id' => $user->id,
                'uid' => $uid,
                'license_plate' => !empty($licensePlate) ? $licensePlate : ($user->license_plate ?? null),
                'balance' => $balance,
                'status' => $status,
                'created_at' => now(),
            ]);
        });

        $successMessage = $balance > 0
            ? "Kartu RFID '{$card->uid}' berhasil dibuatkan untuk {$name} dengan saldo awal Rp " . number_format($balance, 0, ',', '.') . "!"
            : "Kartu RFID '{$card->uid}' berhasil dibuatkan untuk {$name}!";

        return redirect()->route('admin.cards.index')->with('success', $successMessage);
    }

    public function update(Request $request, $id)
    {
        $card = RfidCard::with('user')->findOrFail($id);

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $phone = trim($request->input('phone', ''));
        $licensePlate = strtoupper(trim($request->input('license_plate', '')));
        $avatar = trim($request->input('avatar', ''));
        $status = in_array($request->input('status'), ['active', 'blocked']) ? $request->input('status') : 'active';
        $newBalance = $request->has('balance') ? (int) $request->input('balance') : null;
        $password = trim($request->input('password', ''));

        if (!empty($name) && $card->user) {
            $userData = [
                'name' => $name,
                'email' => !empty($email) ? $email : $card->user->email,
                'phone' => $phone,
            ];
            if ($request->has('license_plate')) {
                $userData['license_plate'] = $licensePlate !== '' ? $licensePlate : null;
            }
            if ($request->has('avatar')) {
                $userData['avatar'] = !empty($avatar) ? $avatar : null;
            }
            if (!empty($password)) {
                $userData['password'] = Hash::make($password);
            }
            $card->user->update($userData);
        }

        $cardData = ['status' => $status];
        if ($request->has('license_plate')) {
            $cardData['license_plate'] = $licensePlate !== '' ? $licensePlate : null;
        }
        if ($newBalance !== null) {
            $cardData['balance'] = $newBalance;
        }
        $card->update($cardData);

        return redirect()->route('admin.cards.index')->with('success', "Data pemilik & kartu RFID ('{$card->uid}') berhasil diperbarui!");
    }

    public function toggleStatus(Request $request)
    {
        $cardId = (int) $request->input('card_id');
        $newStatus = ($request->input('new_status') === 'blocked') ? 'blocked' : 'active';

        $card = RfidCard::findOrFail($cardId);
        $card->update(['status' => $newStatus]);

        return redirect()->route('admin.cards.index')->with('success', "Status kartu berhasil diubah menjadi " . strtoupper($newStatus));
    }

    public function topup(Request $request, $id)
    {
        $card = RfidCard::findOrFail($id);
        $amount = (int) $request->input('amount', 0);

        if ($amount < 1000) {
            return redirect()->route('admin.cards.index')->with('error', 'Nominal pengisian saldo minimal Rp 1.000!');
        }

        $newBal = (int) $card->balance + $amount;
        $card->update(['balance' => $newBal]);

        return redirect()->route('admin.cards.index')->with('success', "Saldo sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil ditambahkan ke kartu '{$card->uid}'. Saldo sekarang: Rp " . number_format($newBal, 0, ',', '.'));
    }

    public function destroy($id)
    {
        $card = RfidCard::findOrFail($id);
        $uid = $card->uid;
        $userId = $card->user_id;

        ParkingTransaction::where('card_id', $id)->delete();
        $card->delete();

        if ($userId) {
            $remainingCards = RfidCard::where('user_id', $userId)->count();
            if ($remainingCards === 0) {
                User::where('id', $userId)->where('role', 'client')->delete();
            }
        }

        return redirect()->route('admin.cards.index')->with('success', "Kartu RFID '{$uid}' dan data terkait berhasil dihapus!");
    }

    public function destroyPendingMember($id)
    {
        $user = User::where('role', 'client')
            ->doesntHave('rfidCard')
            ->findOrFail($id);

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.cards.index')->with('success', "Pendaftaran akun mobile '{$name}' berhasil dihapus!");
    }

    public function latestScan(Request $request): JsonResponse
    {
        // Cari tap dalam 30 detik terakhir
        $threshold = Carbon::now()->subSeconds(30);
        $latest = AccessLog::with('device')
            ->where('created_at', '>=', $threshold)
            ->orderBy('id', 'desc')
            ->first();

        if ($latest && !empty($latest->scan_uid)) {
            $uid = strtoupper(trim($latest->scan_uid));
            $existingCard = RfidCard::with('user')->where('uid', $uid)->first();
            $isRegistered = !empty($existingCard);
            $existingName = ($existingCard && $existingCard->user) ? $existingCard->user->name : '';

            return response()->json([
                'status' => 'success',
                'has_tap' => true,
                'uid' => $uid,
                'device_code' => $latest->device ? $latest->device->device_code : 'READER',
                'timestamp' => $latest->created_at ? $latest->created_at->toDateTimeString() : '',
                'is_registered' => $isRegistered,
                'existing_name' => $existingName
            ]);
        }

        return response()->json([
            'status' => 'success',
            'has_tap' => false
        ]);
    }
}
