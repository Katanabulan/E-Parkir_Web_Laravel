<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RfidCard;
use App\Models\ParkingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public const TOTAL_CAPACITY = 20;
    public const CAPACITY_PER_FLOOR = 10;
    public const RATE_PER_HOUR = 2000;

    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            return redirect()->route('login');
        }

        $defaultUserId = (int) $currentUser->id;

        if ($currentUser->role === 'admin') {
            $clients = User::with('rfidCard')
                ->where('role', 'client')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'uid' => $u->rfidCard ? $u->rfidCard->uid : null,
                        'license_plate' => $u->rfidCard ? $u->rfidCard->license_plate : null,
                        'card_status' => $u->rfidCard ? $u->rfidCard->status : 'none',
                    ];
                });
            if ($clients->isNotEmpty()) {
                $defaultUserId = (int) $clients->first()['id'];
            }
        } else {
            $clients = collect([[
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'uid' => $currentUser->rfidCard ? $currentUser->rfidCard->uid : null,
                'license_plate' => $currentUser->rfidCard ? $currentUser->rfidCard->license_plate : null,
                'card_status' => $currentUser->rfidCard ? $currentUser->rfidCard->status : 'none',
            ]]);
        }

        $totalCapacity = self::TOTAL_CAPACITY;
        $floor1Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 1)->count();
        $floor2Parked = ParkingTransaction::where('status', 'PARKED')->where('parking_floor', 2)->count();
        $parkedInitCount = $floor1Parked + $floor2Parked;
        $availableInitSlots = max(0, $totalCapacity - $parkedInitCount);
        $floor1Available = max(0, self::CAPACITY_PER_FLOOR - $floor1Parked);
        $floor2Available = max(0, self::CAPACITY_PER_FLOOR - $floor2Parked);

        return view('client.dashboard', compact(
            'currentUser',
            'defaultUserId',
            'clients',
            'totalCapacity',
            'parkedInitCount',
            'availableInitSlots',
            'floor1Parked',
            'floor2Parked',
            'floor1Available',
            'floor2Available'
        ));
    }
}
