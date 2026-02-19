<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptOcr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    private function monthlyBillNameForDate($date): string
    {
        return Carbon::parse($date)->format('F') . ' Bill';
    }

    private function receiptDisk(): string
    {
        $configured = env('RECEIPT_DISK', 'private');
        $disks = array_keys((array) config('filesystems.disks', []));
        if (in_array($configured, $disks, true)) {
            return $configured;
        }

        // Fallback for shared hosting misconfiguration.
        return in_array('public', $disks, true) ? 'public' : config('filesystems.default', 'local');
    }

    public function create()
    {
        if ($redirect = $this->requireRole(['resident'])) {
            return $redirect;
        }

        $bill = null;
        if (request('bill_id')) {
            DB::table('bills')
                ->where('user_id', session('usr_id'))
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            $bill = DB::table('bills')
                ->select(
                    'bills.*',
                    DB::raw("CASE WHEN bills.status IN ('pending','overdue') AND CURDATE() > bills.due_date THEN bills.amount * 2 ELSE bills.amount END AS amount_due")
                )
                ->where('id', request('bill_id'))
                ->where('user_id', session('usr_id'))
                ->whereIn('status', ['pending', 'overdue'])
                ->first();
        }

        $submissionToken = Str::random(40);
        session(['payments_submission_token' => $submissionToken]);

        return view('payments.create', compact('bill', 'submissionToken'));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireRole(['resident'])) {
            return $redirect;
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:2',
            'receipt' => 'required|file|mimes:jpeg,jpg,png|mimetypes:image/jpeg,image/png|max:2048',
            'submission_token' => 'required|string',
        ], [
            'receipt.required' => 'Receipt is required.',
            'receipt.file' => 'Receipt must be a file upload.',
            'receipt.mimes' => 'Invalid file type. Upload JPG, JPEG, or PNG only.',
            'receipt.mimetypes' => 'Invalid receipt file content. Upload a valid image file.',
            'receipt.max' => 'Receipt must not exceed 2MB.',
            'submission_token.required' => 'Session expired. Please open the payment form again.',
        ]);

        $sessionToken = (string) session('payments_submission_token', '');
        $submittedToken = (string) $request->input('submission_token', '');
        if ($sessionToken === '' || $submittedToken === '' || !hash_equals($sessionToken, $submittedToken)) {
            return redirect()->route('payments.create', ['bill_id' => $request->bill_id])->with('error', 'Duplicate or expired submission detected. Please try again.');
        }
        session()->forget('payments_submission_token');

        $receipt = $request->file('receipt');
        if (!$receipt instanceof UploadedFile || !$this->isSafeReceiptImage($receipt)) {
            throw ValidationException::withMessages([
                'receipt' => 'Invalid receipt file. Upload a valid JPEG or PNG image only.',
            ]);
        }

        $userId = session('usr_id');
        $amountToCheck = (float) $request->amount;

        if ($request->bill_id) {
            DB::table('bills')
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            $result = DB::transaction(function () use ($request, $userId, &$amountToCheck) {
                // Lock bill row to prevent two payments for the same bill in concurrent requests.
                $bill = DB::table('bills')
                    ->where('id', $request->bill_id)
                    ->where('user_id', $userId)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->lockForUpdate()
                    ->first();

                if (!$bill) {
                    return ['state' => 'bill_unavailable'];
                }

                $isOverdue = now()->toDateString() > (string) $bill->due_date;
                $amountDue = $isOverdue ? ((float) $bill->amount * 2) : (float) $bill->amount;
                $amountToCheck = $amountDue;

                $recentDuplicate = DB::table('payments')
                    ->where('user_id', $userId)
                    ->where('payment_method', 2)
                    ->where('status', 1)
                    ->where('amount', $amountToCheck)
                    ->where('created_at', '>=', now()->subMinute())
                    ->exists();

                if ($recentDuplicate) {
                    return ['state' => 'duplicate'];
                }

                $paymentId = DB::table('payments')->insertGetId([
                    'user_id' => $userId,
                    'amount' => $amountDue,
                    'payment_method' => $request->payment_method,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('bills')->where('id', $request->bill_id)->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'updated_at' => now(),
                ]);

                if ((int) $bill->is_recurring === 1 && $bill->recurrence_type === 'monthly') {
                    $nextDueDate = Carbon::parse($bill->due_date)->addMonthNoOverflow()->toDateString();
                    $nextBillName = $this->monthlyBillNameForDate($nextDueDate);

                    $existingNextBill = DB::table('bills')
                        ->where('user_id', $userId)
                        ->where('bill_name', $nextBillName)
                        ->whereDate('due_date', $nextDueDate)
                        ->whereIn('status', ['pending', 'overdue'])
                        ->exists();

                    if (!$existingNextBill) {
                        DB::table('bills')->insert([
                            'user_id' => $userId,
                            'bill_name' => $nextBillName,
                            'amount' => $bill->amount,
                            'due_date' => $nextDueDate,
                            'status' => 'pending',
                            'paid_at' => null,
                            'is_recurring' => 1,
                            'recurrence_type' => $bill->recurrence_type,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return ['state' => 'created', 'payment_id' => $paymentId];
            });

            if (($result['state'] ?? null) === 'bill_unavailable') {
                return redirect()->route('payments.index')->with('error', 'This bill is already paid or unavailable.');
            }
            if (($result['state'] ?? null) === 'duplicate') {
                return redirect()->route('payments.index')->with('success', 'Payment already submitted. Please wait for verification.');
            }

            $paymentId = (int) ($result['payment_id'] ?? 0);
        } else {
            $recentDuplicate = DB::table('payments')
                ->where('user_id', $userId)
                ->where('payment_method', 2)
                ->where('status', 1)
                ->where('amount', $amountToCheck)
                ->where('created_at', '>=', now()->subMinute())
                ->exists();

            if ($recentDuplicate) {
                return redirect()->route('payments.index')->with('success', 'Payment already submitted. Please wait for verification.');
            }

            // New payment submission
            $paymentId = DB::table('payments')->insertGetId([
                'user_id' => $userId,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->payment_method == 2) {
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', $this->receiptDisk());
            }

            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $paymentId],
                [
                    'receipt_image_path' => $receiptPath,
                    'ocr_text' => 'OCR pending. Manual verification may be required.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if ($receiptPath) {
                $mode = strtolower((string) env('OCR_PROCESSING_MODE', 'sync'));
                if ($mode === 'queue') {
                    ProcessReceiptOcr::dispatch($paymentId, $receiptPath);
                } else {
                    ProcessReceiptOcr::dispatchSync($paymentId, $receiptPath);
                }
            }
        }

        Log::info('payments.submitted', [
            'user_id' => $userId,
            'payment_id' => $paymentId,
            'method' => (int) $request->payment_method,
        ]);
        $this->appendAuditLedger('payments.submitted', [
            'payment_id' => $paymentId,
            'method' => (int) $request->payment_method,
        ]);

        return redirect()->route('payments.index')->with('success', 'Payment submitted.');
    }

    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $search = trim((string) request('q', ''));
        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');
        $month = (int) request('month', 0);
        $year = (int) request('year', 0);

        if ($month < 1 || $month > 12) {
            $month = 0;
        }
        if ($year < 2000 || $year > 2100) {
            $year = 0;
        }

        $allowedSort = ['created_at', 'amount', 'status', 'due_date'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        if (in_array(session('usr_role'), ['admin', 'official', 'treasurer'])) {
            DB::table('bills')
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            $isScopedToBarangay = in_array(session('usr_role'), ['official', 'treasurer'], true);
            $barangayId = null;
            if ($isScopedToBarangay) {
                $barangayId = $this->currentUserBarangayId();
                if (!$barangayId) {
                    return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
                }
            }

            $unpaidBillsQuery = DB::table('bills')
                ->join('users', 'bills.user_id', '=', 'users.id')
                ->select(
                    'bills.id',
                    'bills.user_id',
                    'bills.amount',
                    DB::raw('0 as payment_method'),
                    DB::raw("CASE WHEN bills.status = 'overdue' THEN -1 ELSE 0 END as status"),
                    'bills.due_date',
                    'bills.created_at',
                    'bills.updated_at',
                    DB::raw('NULL as ocr_text'),
                    DB::raw('NULL as extracted_amount'),
                    DB::raw('NULL as extracted_reference'),
                    DB::raw('NULL as confidence_score'),
                    DB::raw('NULL as receipt_image_path'),
                    DB::raw('NULL as verified_at'),
                    'users.username as user_name',
                    'users.full_name as full_name',
                    DB::raw("'bill' as row_type")
                )
                ->whereIn('bills.status', ['pending', 'overdue']);

            if ($isScopedToBarangay) {
                $unpaidBillsQuery->join('residents', 'residents.user_id', '=', 'bills.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }

            if ($search !== '') {
                $unpaidBillsQuery->where(function ($q) use ($search) {
                    $q->where('users.username', 'like', '%' . $search . '%')
                        ->orWhere('users.full_name', 'like', '%' . $search . '%')
                        ->orWhereRaw('CAST(bills.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(bills.amount AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhere('bills.bill_name', 'like', '%' . $search . '%');
                });
            }
            if ($month > 0) {
                $unpaidBillsQuery->whereMonth('bills.created_at', $month);
            }
            if ($year > 0) {
                $unpaidBillsQuery->whereYear('bills.created_at', $year);
            }

            $unpaidPaymentsQuery = DB::table('payments')
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select(
                    'payments.id',
                    'payments.user_id',
                    'payments.amount',
                    'payments.payment_method',
                    'payments.status',
                    DB::raw('NULL as due_date'),
                    'payments.created_at',
                    'payments.updated_at',
                    'gcash_payments.ocr_text',
                    'gcash_payments.extracted_amount',
                    'gcash_payments.extracted_reference',
                    'gcash_payments.confidence_score',
                    'gcash_payments.receipt_image_path',
                    'gcash_payments.verified_at',
                    'users.username as user_name',
                    'users.full_name as full_name',
                    DB::raw("'payment' as row_type")
                )
                ->whereIn('payments.status', [1, 2]);

            if ($isScopedToBarangay) {
                $unpaidPaymentsQuery->join('residents', 'residents.user_id', '=', 'payments.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }

            if ($search !== '') {
                $unpaidPaymentsQuery->where(function ($q) use ($search) {
                    $q->where('users.username', 'like', '%' . $search . '%')
                        ->orWhere('users.full_name', 'like', '%' . $search . '%')
                        ->orWhereRaw('CAST(payments.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(payments.amount AS CHAR) LIKE ?', ['%' . $search . '%']);
                });
            }
            if ($month > 0) {
                $unpaidPaymentsQuery->whereMonth('payments.created_at', $month);
            }
            if ($year > 0) {
                $unpaidPaymentsQuery->whereYear('payments.created_at', $year);
            }

            $unpaidRecords = DB::query()
                ->fromSub($unpaidBillsQuery->unionAll($unpaidPaymentsQuery), 'unpaid_rows')
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'unpaid_page')
                ->withQueryString();

            $paidBaseQuery = DB::table('payments')
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select(
                    'payments.*',
                    DB::raw("(SELECT b.bill_name FROM bills b WHERE b.user_id = payments.user_id AND b.status = 'paid' AND b.paid_at IS NOT NULL AND ABS(TIMESTAMPDIFF(SECOND, b.paid_at, payments.created_at)) <= 60 ORDER BY ABS(TIMESTAMPDIFF(SECOND, b.paid_at, payments.created_at)) ASC, b.id DESC LIMIT 1) as bill_name"),
                    'gcash_payments.ocr_text',
                    'gcash_payments.extracted_amount',
                    'gcash_payments.extracted_reference',
                    'gcash_payments.confidence_score',
                    'gcash_payments.receipt_image_path',
                    'gcash_payments.verified_at',
                    'users.username as user_name',
                    'users.full_name as full_name'
                )
                ->where('payments.status', 3);

            if ($isScopedToBarangay) {
                $paidBaseQuery->join('residents', 'residents.user_id', '=', 'payments.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }

            if ($search !== '') {
                $paidBaseQuery->where(function ($q) use ($search) {
                    $q->where('users.username', 'like', '%' . $search . '%')
                        ->orWhere('users.full_name', 'like', '%' . $search . '%')
                        ->orWhereRaw('CAST(payments.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(payments.amount AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw("EXISTS (SELECT 1 FROM bills b WHERE b.user_id = payments.user_id AND b.status = 'paid' AND b.bill_name LIKE ? AND b.paid_at IS NOT NULL AND ABS(TIMESTAMPDIFF(SECOND, b.paid_at, payments.created_at)) <= 60)", ['%' . $search . '%']);
                });
            }
            if ($month > 0) {
                $paidBaseQuery->whereMonth('payments.created_at', $month);
            }
            if ($year > 0) {
                $paidBaseQuery->whereYear('payments.created_at', $year);
            }

            $paidRecords = $paidBaseQuery
                ->orderBy($sortBy === 'due_date' ? 'created_at' : $sortBy, $sortOrder)
                ->paginate(10, ['*'], 'paid_page')
                ->withQueryString();

            $yearsQuery = DB::table('payments');
            if ($isScopedToBarangay) {
                $yearsQuery->join('residents', 'residents.user_id', '=', 'payments.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }
            $yearOptions = $yearsQuery
                ->selectRaw('YEAR(payments.created_at) as year_value')
                ->whereNotNull('payments.created_at')
                ->distinct()
                ->orderBy('year_value', 'desc')
                ->pluck('year_value')
                ->filter()
                ->values();

            return view('payments.index', compact('unpaidRecords', 'paidRecords', 'sortBy', 'sortOrder', 'search', 'month', 'year', 'yearOptions'));
        } else {
            DB::table('bills')
                ->where('user_id', session('usr_id'))
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            $billsQuery = DB::table('bills')
                ->select(
                    'bills.*',
                    DB::raw("CASE WHEN bills.status IN ('pending','overdue') AND CURDATE() > bills.due_date THEN bills.amount * 2 ELSE bills.amount END AS amount_due"),
                    DB::raw("CASE WHEN bills.status IN ('pending','overdue') AND bills.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END AS is_upcoming_due")
                )
                ->where('user_id', session('usr_id'))
                ->whereIn('status', ['pending', 'overdue']);

            if ($search !== '') {
                $billsQuery->where(function ($q) use ($search) {
                    $q->whereRaw('CAST(bills.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(bills.amount AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhere('bills.bill_name', 'like', '%' . $search . '%');
                });
            }

            $bills = $billsQuery
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'my_bills_page')
                ->withQueryString();

            $paymentsQuery = DB::table('payments')
                ->where('user_id', session('usr_id'))
                ->where('status', '>', 0)
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->select('payments.*', 'gcash_payments.ocr_text', 'gcash_payments.extracted_amount', 'gcash_payments.extracted_reference', 'gcash_payments.confidence_score', 'gcash_payments.receipt_image_path', 'gcash_payments.verified_at')
                ;

            if ($search !== '') {
                $paymentsQuery->where(function ($q) use ($search) {
                    $q->whereRaw('CAST(payments.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(payments.amount AS CHAR) LIKE ?', ['%' . $search . '%']);
                });
            }
            if ($month > 0) {
                $paymentsQuery->whereMonth('payments.created_at', $month);
            }
            if ($year > 0) {
                $paymentsQuery->whereYear('payments.created_at', $year);
            }

            $payments = $paymentsQuery
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'my_payments_page')
                ->withQueryString();

            $yearOptions = DB::table('payments')
                ->where('user_id', session('usr_id'))
                ->selectRaw('YEAR(created_at) as year_value')
                ->whereNotNull('created_at')
                ->distinct()
                ->orderBy('year_value', 'desc')
                ->pluck('year_value')
                ->filter()
                ->values();

            return view('payments.index', compact('bills', 'payments', 'sortBy', 'sortOrder', 'search', 'month', 'year', 'yearOptions'));
        }
    }

    public function verify($id)
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with('error', 'Payment not found.');
        }

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], (int) $payment->user_id)) {
            return $redirect;
        }

        DB::table('payments')->where('id', $id)->update(['status' => 2, 'updated_at' => now()]);
        DB::table('gcash_payments')->where('payment_id', $id)->update(['verified_at' => now(), 'updated_at' => now()]);
        Log::info('payments.verified', [
            'actor_id' => session('usr_id'),
            'payment_id' => $id,
        ]);
        $this->appendAuditLedger('payments.verified', [
            'payment_id' => $id,
        ]);
        return redirect()->back()->with('success', 'Payment verified.');
    }

    public function approve($id)
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return redirect()->back()->with('error', 'Payment not found.');
        }

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], (int) $payment->user_id)) {
            return $redirect;
        }

        DB::table('payments')->where('id', $id)->update(['status' => 3, 'updated_at' => now()]);
        Log::info('payments.approved', [
            'actor_id' => session('usr_id'),
            'payment_id' => $id,
        ]);
        $this->appendAuditLedger('payments.approved', [
            'payment_id' => $id,
        ]);
        return redirect()->back()->with('success', 'Payment approved.');
    }

    public function createBill()
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], null, (int) $barangayId)) {
            return $redirect;
        }

        $barangay = DB::table('barangays')
            ->select('id', 'name', 'payment_amount_per_bill')
            ->where('id', $barangayId)
            ->first();

        $residentCount = DB::table('residents')
            ->where('barangay_id', $barangayId)
            ->count();

        return view('payments.create_bill', compact('barangay', 'residentCount'));
    }

    public function createWalkIn()
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], null, (int) $barangayId)) {
            return $redirect;
        }

        $residents = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->where('residents.barangay_id', $barangayId)
            ->select(
                'users.id',
                'users.full_name',
                'users.username',
                DB::raw("(SELECT b1.id FROM bills b1 WHERE b1.user_id = users.id AND b1.status IN ('pending', 'overdue') ORDER BY b1.due_date ASC, b1.id ASC LIMIT 1) as unpaid_bill_id"),
                DB::raw("(SELECT b2.amount FROM bills b2 WHERE b2.user_id = users.id AND b2.status IN ('pending', 'overdue') ORDER BY b2.due_date ASC, b2.id ASC LIMIT 1) as unpaid_bill_amount"),
                DB::raw("(SELECT b3.due_date FROM bills b3 WHERE b3.user_id = users.id AND b3.status IN ('pending', 'overdue') ORDER BY b3.due_date ASC, b3.id ASC LIMIT 1) as unpaid_bill_due_date")
            )
            ->get();

        $defaultAmount = (float) DB::table('barangays')
            ->where('id', $barangayId)
            ->value('payment_amount_per_bill');

        $today = now()->toDateString();
        $residents = $residents->map(function ($resident) use ($defaultAmount, $today) {
            $amount = $defaultAmount > 0 ? $defaultAmount : 0.0;

            if (!empty($resident->unpaid_bill_amount)) {
                $amount = (float) $resident->unpaid_bill_amount;
                if (!empty($resident->unpaid_bill_due_date) && (string) $resident->unpaid_bill_due_date < $today) {
                    $amount = $amount * 2;
                }
            }

            $resident->walkin_amount = $amount;
            return $resident;
        });

        return view('payments.walkin', compact('residents', 'defaultAmount'));
    }

    public function storeWalkIn(Request $request)
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], (int) $request->user_id, (int) $barangayId)) {
            return $redirect;
        }

        DB::table('bills')
            ->where('user_id', (int) $request->user_id)
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

        $unpaidBill = DB::table('bills')
            ->where('user_id', (int) $request->user_id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->first();

        $amountToPay = (float) DB::table('barangays')
            ->where('id', $barangayId)
            ->value('payment_amount_per_bill');
        if ($amountToPay <= 0) {
            $amountToPay = 50.0;
        }

        if ($unpaidBill) {
            $amountToPay = (float) $unpaidBill->amount;
            if ((string) $unpaidBill->due_date < now()->toDateString()) {
                $amountToPay = $amountToPay * 2;
            }
        }

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $request->user_id,
            'amount' => $amountToPay,
            'payment_method' => 1, // cash
            'status' => 3, // approved
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($unpaidBill) {
            DB::table('bills')->where('id', $unpaidBill->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

            if ((int) $unpaidBill->is_recurring === 1 && $unpaidBill->recurrence_type === 'monthly') {
                $nextDueDate = Carbon::parse($unpaidBill->due_date)->addMonthNoOverflow()->toDateString();
                $nextBillName = $this->monthlyBillNameForDate($nextDueDate);

                $existsNext = DB::table('bills')
                    ->where('user_id', (int) $request->user_id)
                    ->where('bill_name', $nextBillName)
                    ->whereDate('due_date', $nextDueDate)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->exists();

                if (!$existsNext) {
                    DB::table('bills')->insert([
                        'user_id' => (int) $request->user_id,
                        'bill_name' => $nextBillName,
                        'amount' => $unpaidBill->amount,
                        'due_date' => $nextDueDate,
                        'status' => 'pending',
                        'paid_at' => null,
                        'is_recurring' => 1,
                        'recurrence_type' => 'monthly',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Log::info('payments.walkin_created', [
            'actor_id' => session('usr_id'),
            'payment_id' => $paymentId,
            'user_id' => (int) $request->user_id,
        ]);
        $this->appendAuditLedger('payments.walkin_created', [
            'payment_id' => $paymentId,
            'user_id' => (int) $request->user_id,
        ]);

        return redirect()->route('payments.index')->with('success', 'Walk-in payment recorded.');
    }

    public function storeBill(Request $request)
    {
        if ($redirect = $this->requireRole(['treasurer'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
        ]);
        $billName = $this->monthlyBillNameForDate($validated['due_date']);

        $residentUserIds = DB::table('residents')
            ->where('barangay_id', $barangayId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($residentUserIds)) {
            return redirect()->back()->with('error', 'No residents found in your barangay.');
        }

        $existingUnpaid = DB::table('bills')
            ->whereIn('user_id', $residentUserIds)
            ->whereIn('status', ['pending', 'overdue'])
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $billableUserIds = array_values(array_diff($residentUserIds, $existingUnpaid));
        $now = now();
        $rows = [];
        foreach ($billableUserIds as $userId) {
            $rows[] = [
                'user_id' => $userId,
                'bill_name' => $billName,
                'amount' => $validated['amount'],
                'due_date' => $validated['due_date'],
                'status' => 'pending',
                'paid_at' => null,
                'is_recurring' => 1,
                'recurrence_type' => 'monthly',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('bills')->insert($rows);
        }

        Log::info('payments.bill_batch_created', [
            'actor_id' => session('usr_id'),
            'barangay_id' => $barangayId,
            'generated_count' => count($rows),
            'skipped_count' => count($existingUnpaid),
        ]);
        $this->appendAuditLedger('payments.bill_batch_created', [
            'barangay_id' => $barangayId,
            'generated_count' => count($rows),
            'skipped_count' => count($existingUnpaid),
            'amount' => (float) $validated['amount'],
        ]);

        return redirect()->route('payments.index')->with(
            'success',
            'Bills generated: ' . count($rows) . '. Skipped (already has pending/overdue bill): ' . count($existingUnpaid) . '.'
        );
    }

    public function receipt($id)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $payment = DB::table('payments')
            ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
            ->select('payments.user_id', 'gcash_payments.receipt_image_path')
            ->where('payments.id', $id)
            ->first();

        if (!$payment || !$payment->receipt_image_path) {
            return redirect()->back()->with('error', 'Receipt not found.');
        }

        $role = session('usr_role');
        if (in_array($role, ['official', 'treasurer'], true)) {
            if ($redirect = $this->requireRoleInBarangay([$role], (int) $payment->user_id)) {
                return $redirect;
            }
        } elseif (!in_array($role, ['admin'], true) && (int) $payment->user_id !== (int) session('usr_id')) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $disk = $this->receiptDisk();
        if (!Storage::disk($disk)->exists($payment->receipt_image_path)) {
            return redirect()->back()->with('error', 'Receipt file missing.');
        }

        try {
            if (request()->boolean('download')) {
                return Storage::disk($disk)->download($payment->receipt_image_path);
            }

            $absolutePath = Storage::disk($disk)->path($payment->receipt_image_path);
            return response()->file($absolutePath);
        } catch (Throwable $e) {
            Log::error('payments.receipt_download_failed', [
                'payment_id' => (int) $id,
                'disk' => $disk,
                'path' => $payment->receipt_image_path,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Receipt cannot be downloaded on current storage setup.');
        }
    }

    private function isSafeReceiptImage(UploadedFile $file): bool
    {
        if (!$file->isValid()) {
            return false;
        }

        $path = $file->getRealPath();
        if ($path === false || !is_readable($path)) {
            return false;
        }

        $allowedMime = ['image/jpeg', 'image/png'];
        $detectedMime = mime_content_type($path);
        if (!in_array($detectedMime, $allowedMime, true)) {
            return false;
        }

        if (function_exists('exif_imagetype')) {
            $imageType = @exif_imagetype($path);
            if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                return false;
            }
        }

        if (function_exists('getimagesize')) {
            [$width, $height] = @getimagesize($path) ?: [0, 0];
            if ($width < 100 || $height < 100) {
                return false;
            }
        }

        return true;
    }
}
