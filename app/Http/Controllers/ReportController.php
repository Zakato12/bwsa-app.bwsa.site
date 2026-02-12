<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function residents()
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        $barangay = DB::table('barangays')
            ->select('id', 'name')
            ->where('id', $barangayId)
            ->first();

        $residents = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->where('residents.barangay_id', $barangayId)
            ->select(
                'residents.id',
                'users.full_name',
                'users.username',
                'residents.address',
                'residents.created_at'
            )
            ->orderBy('users.full_name')
            ->get();

        return view('reports.residents', compact('barangay', 'residents'));
    }

    public function payments()
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        $barangay = DB::table('barangays')
            ->select('id', 'name')
            ->where('id', $barangayId)
            ->first();

        $payments = DB::table('payments')
            ->join('users', 'payments.user_id', '=', 'users.id')
            ->join('residents', 'residents.user_id', '=', 'users.id')
            ->where('residents.barangay_id', $barangayId)
            ->select(
                'payments.id',
                'users.full_name',
                'users.username',
                'payments.amount',
                'payments.payment_method',
                'payments.status',
                'payments.created_at'
            )
            ->orderBy('payments.created_at', 'desc')
            ->get();

        $summary = [
            'total_transactions' => $payments->count(),
            'total_amount' => (float) $payments->sum('amount'),
            'approved_count' => $payments->where('status', 3)->count(),
            'approved_amount' => (float) $payments->where('status', 3)->sum('amount'),
        ];

        return view('reports.payments', compact('barangay', 'payments', 'summary'));
    }
}
