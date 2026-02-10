<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PageController extends Controller
{
    public function showLogin(){
        return view('pages.auth.login');
    }
    public function userincactive(){
        return redirect()->route('login')->with('error','Your account is inactive. Please login again.');
    }
    public function main(){
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        if(session()->has('usr_role')){
            switch (session('usr_role')) {
                case 'admin':
                    $totalResidents = DB::table('residents')->count();
                    $totalBarangays = DB::table('barangays')->count();
                    $totalPayments = DB::table('payments')->count();
                    $pendingPayments = DB::table('payments')->where('status', 1)->count();

                    $recentLogs = [];
                    $logPath = storage_path('logs/audit_ledger.log');
                    if (File::exists($logPath)) {
                        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $recentLogs = array_slice($lines, -8);
                        $recentLogs = array_reverse($recentLogs);
                    }

                    return view('pages.dashboards.admin', compact(
                        'totalResidents',
                        'totalBarangays',
                        'totalPayments',
                        'pendingPayments',
                        'recentLogs'
                    ));
                case 'official':
                    $barangayId = $this->currentUserBarangayId();
                    $residentCount = $barangayId
                        ? DB::table('residents')->where('barangay_id', $barangayId)->count()
                        : 0;
                    $paymentCount = $barangayId
                        ? DB::table('payments')
                            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
                            ->where('residents.barangay_id', $barangayId)
                            ->count()
                        : 0;
                    $recentResidents = $barangayId
                        ? DB::table('residents')
                            ->join('users', 'residents.user_id', '=', 'users.id')
                            ->where('residents.barangay_id', $barangayId)
                            ->select('users.full_name', 'users.username', 'residents.created_at')
                            ->orderBy('residents.created_at', 'desc')
                            ->limit(5)
                            ->get()
                        : collect();
                    $recentPayments = $barangayId
                        ? DB::table('payments')
                            ->join('users', 'payments.user_id', '=', 'users.id')
                            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
                            ->where('residents.barangay_id', $barangayId)
                            ->select('payments.id', 'users.username', 'payments.amount', 'payments.status', 'payments.created_at')
                            ->orderBy('payments.created_at', 'desc')
                            ->limit(5)
                            ->get()
                        : collect();

                    return view('pages.dashboards.official', compact(
                        'residentCount',
                        'paymentCount',
                        'recentResidents',
                        'recentPayments'
                    ));
                case 'treasurer':
                    $barangayId = $this->currentUserBarangayId();
                    $pendingVerifications = $barangayId
                        ? DB::table('payments')
                            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
                            ->where('residents.barangay_id', $barangayId)
                            ->where('payments.payment_method', 2)
                            ->where('payments.status', 1)
                            ->count()
                        : 0;
                    $pendingBills = $barangayId
                        ? DB::table('payments')
                            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
                            ->where('residents.barangay_id', $barangayId)
                            ->where('payments.status', 0)
                            ->count()
                        : 0;
                    $recentPayments = $barangayId
                        ? DB::table('payments')
                            ->join('users', 'payments.user_id', '=', 'users.id')
                            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
                            ->where('residents.barangay_id', $barangayId)
                            ->select('payments.id', 'users.username', 'payments.amount', 'payments.payment_method', 'payments.status', 'payments.created_at')
                            ->orderBy('payments.created_at', 'desc')
                            ->limit(5)
                            ->get()
                        : collect();

                    return view('pages.dashboards.tresurer', compact(
                        'pendingVerifications',
                        'pendingBills',
                        'recentPayments'
                    ));
                case 'resident':
                    $userId = session('usr_id');
                    $unpaidBills = DB::table('payments')
                        ->where('user_id', $userId)
                        ->where('status', 0)
                        ->count();
                    $recentPayments = DB::table('payments')
                        ->where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

                    return view('pages.dashboards.member', compact('unpaidBills', 'recentPayments'));
                default:
                    return redirect()->action([PageController::class, 'showLogin'])->with('error','Invalid Login Credentials.');
            }
        }
    }

    public function showAddUser(){
        if ($redirect = $this->requireRole(['admin', 'official'])) {
            return $redirect;
        }
        return view('pages.users.create');
    }
}
