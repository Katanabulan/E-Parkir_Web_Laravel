<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Device;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $filterStatus = $request->query('status', '');
        $filterDevice = $request->query('device_id', '');
        $filterMethod = $request->query('method', '');
        $search = trim($request->query('search', ''));

        $query = AccessLog::with('device');

        if (!empty($filterStatus) && in_array($filterStatus, ['granted', 'denied'])) {
            $query->where('status', $filterStatus);
        }

        if (!empty($filterDevice)) {
            $query->where('device_id', $filterDevice);
        }

        if (!empty($filterMethod)) {
            $query->where('access_method', $filterMethod);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('scan_uid', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('device', function ($qd) use ($search) {
                      $qd->where('device_code', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('id', 'desc')->limit(200)->get();

        $devicesList = Device::orderBy('id', 'asc')->get();

        $totalLogs = AccessLog::count();
        $grantedLogs = AccessLog::where('status', 'granted')->count();
        $deniedLogs = AccessLog::where('status', 'denied')->count();

        return view('admin.logs', compact(
            'logs',
            'devicesList',
            'filterStatus',
            'filterDevice',
            'filterMethod',
            'search',
            'totalLogs',
            'grantedLogs',
            'deniedLogs'
        ));
    }
}
