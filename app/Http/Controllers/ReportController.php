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

        $month = (int) request('month', 0);
        $year = (int) request('year', 0);
        if ($month < 1 || $month > 12) {
            $month = 0;
        }
        if ($year < 2000 || $year > 2100) {
            $year = 0;
        }

        $baseQuery = DB::table('payments')
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
            );

        if ($month > 0) {
            $baseQuery->whereMonth('payments.created_at', $month);
        }
        if ($year > 0) {
            $baseQuery->whereYear('payments.created_at', $year);
        }

        $payments = (clone $baseQuery)
            ->orderBy('payments.created_at', 'desc')
            ->get();

        $yearOptions = DB::table('payments')
            ->join('residents', 'residents.user_id', '=', 'payments.user_id')
            ->where('residents.barangay_id', $barangayId)
            ->selectRaw('YEAR(payments.created_at) as year_value')
            ->whereNotNull('payments.created_at')
            ->distinct()
            ->orderBy('year_value', 'desc')
            ->pluck('year_value')
            ->filter()
            ->values();

        $summary = [
            'total_transactions' => $payments->count(),
            'total_amount' => (float) $payments->sum('amount'),
            'approved_count' => $payments->where('status', 3)->count(),
            'approved_amount' => (float) $payments->where('status', 3)->sum('amount'),
        ];

        return view('reports.payments', compact('barangay', 'payments', 'summary', 'month', 'year', 'yearOptions'));
    }

    public function billingHistory()
    {
        if ($redirect = $this->requireRole(['official', 'treasurer'])) {
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

        $search = trim((string) request('q', ''));
        $status = trim((string) request('status', 'all'));

        $query = DB::table('bills')
            ->join('users', 'bills.user_id', '=', 'users.id')
            ->join('residents', 'residents.user_id', '=', 'users.id')
            ->where('residents.barangay_id', $barangayId)
            ->select(
                'bills.id',
                'users.full_name',
                'users.username',
                'bills.bill_name',
                'bills.amount',
                'bills.due_date',
                'bills.status',
                'bills.paid_at',
                'bills.created_at',
                DB::raw("CASE WHEN bills.status IN ('pending','overdue') AND CURDATE() > bills.due_date THEN bills.amount * 2 ELSE bills.amount END AS amount_due_now")
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', '%' . $search . '%')
                    ->orWhere('users.username', 'like', '%' . $search . '%')
                    ->orWhere('bills.bill_name', 'like', '%' . $search . '%')
                    ->orWhereRaw('CAST(bills.id AS CHAR) LIKE ?', ['%' . $search . '%']);
            });
        }

        if (in_array($status, ['pending', 'overdue', 'paid'], true)) {
            $query->where('bills.status', $status);
        }

        $billingLogs = $query
            ->orderBy('bills.created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $summaryBase = DB::table('bills')
            ->join('residents', 'residents.user_id', '=', 'bills.user_id')
            ->where('residents.barangay_id', $barangayId);

        $summary = [
            'total_bills' => (clone $summaryBase)->count(),
            'pending_bills' => (clone $summaryBase)->where('bills.status', 'pending')->count(),
            'overdue_bills' => (clone $summaryBase)->where('bills.status', 'overdue')->count(),
            'paid_bills' => (clone $summaryBase)->where('bills.status', 'paid')->count(),
        ];

        return view('reports.billing_history', compact('barangay', 'billingLogs', 'summary', 'search', 'status'));
    }
}
