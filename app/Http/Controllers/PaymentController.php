<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptOcr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
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
        return view('payments.create', compact('bill'));
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
        ], [
            'receipt.required' => 'Receipt is required.',
            'receipt.file' => 'Receipt must be a file upload.',
            'receipt.mimes' => 'Invalid file type. Upload JPG, JPEG, or PNG only.',
            'receipt.mimetypes' => 'Invalid receipt file content. Upload a valid image file.',
            'receipt.max' => 'Receipt must not exceed 2MB.',
        ]);

        $receipt = $request->file('receipt');
        if (!$receipt instanceof UploadedFile || !$this->isSafeReceiptImage($receipt)) {
            throw ValidationException::withMessages([
                'receipt' => 'Invalid receipt file. Upload a valid JPEG or PNG image only.',
            ]);
        }

        $userId = session('usr_id');

        if ($request->bill_id) {
            DB::table('bills')
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now()->toDateString())
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            // Paying an existing bill
            $bill = DB::table('bills')
                ->where('id', $request->bill_id)
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'overdue'])
                ->first();

            if (!$bill) {
                return redirect()->back()->with('error', 'Invalid bill selected.');
            }

            $isOverdue = now()->toDateString() > (string) $bill->due_date;
            $amountDue = $isOverdue ? ((float) $bill->amount * 2) : (float) $bill->amount;

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

            if ((int) $bill->is_recurring === 1 && in_array($bill->recurrence_type, ['monthly', 'bimonthly'], true)) {
                $nextDueDate = $bill->recurrence_type === 'bimonthly'
                    ? Carbon::parse($bill->due_date)->addMonthsNoOverflow(2)->toDateString()
                    : Carbon::parse($bill->due_date)->addMonthNoOverflow()->toDateString();

                $existingNextBill = DB::table('bills')
                    ->where('user_id', $userId)
                    ->where('bill_name', $bill->bill_name)
                    ->whereDate('due_date', $nextDueDate)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->exists();

                if (!$existingNextBill) {
                    DB::table('bills')->insert([
                        'user_id' => $userId,
                        'bill_name' => $bill->bill_name,
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
        } else {
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

        $allowedSort = ['created_at', 'amount', 'status'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        if (in_array(session('usr_role'), ['admin', 'official', 'treasurer'])) {
            $baseQuery = DB::table('payments')
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select(
                    'payments.*',
                    'gcash_payments.ocr_text',
                    'gcash_payments.extracted_amount',
                    'gcash_payments.extracted_reference',
                    'gcash_payments.confidence_score',
                    'gcash_payments.receipt_image_path',
                    'gcash_payments.verified_at',
                    'users.username as user_name',
                    'users.full_name as full_name'
                );

            if (in_array(session('usr_role'), ['official', 'treasurer'], true)) {
                $barangayId = $this->currentUserBarangayId();
                if (!$barangayId) {
                    return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
                }
                $baseQuery->join('residents', 'residents.user_id', '=', 'payments.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }

            if ($search !== '') {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('users.username', 'like', '%' . $search . '%')
                        ->orWhere('users.full_name', 'like', '%' . $search . '%')
                        ->orWhereRaw('CAST(payments.id AS CHAR) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('CAST(payments.amount AS CHAR) LIKE ?', ['%' . $search . '%']);
                });
            }

            $unpaidRecords = (clone $baseQuery)
                ->whereIn('payments.status', [1, 2])
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'unpaid_page')
                ->withQueryString();

            $paidRecords = (clone $baseQuery)
                ->where('payments.status', 3)
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'paid_page')
                ->withQueryString();

            return view('payments.index', compact('unpaidRecords', 'paidRecords', 'sortBy', 'sortOrder', 'search'));
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

            $payments = $paymentsQuery
                ->orderBy($sortBy, $sortOrder)
                ->paginate(10, ['*'], 'my_payments_page')
                ->withQueryString();

            return view('payments.index', compact('bills', 'payments', 'sortBy', 'sortOrder', 'search'));
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
            ->select('users.id', 'users.full_name', 'users.username')
            ->get();

        return view('payments.walkin', compact('residents'));
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
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($redirect = $this->requireRoleInBarangay(['treasurer'], (int) $request->user_id, (int) $barangayId)) {
            return $redirect;
        }

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'payment_method' => 1, // cash
            'status' => 3, // approved
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            'bill_name' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'recurrence_type' => 'required|in:monthly,bimonthly',
        ]);

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
                'bill_name' => $validated['bill_name'],
                'amount' => $validated['amount'],
                'due_date' => $validated['due_date'],
                'status' => 'pending',
                'paid_at' => null,
                'is_recurring' => 1,
                'recurrence_type' => $validated['recurrence_type'],
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
