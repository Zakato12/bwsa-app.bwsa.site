<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessReceiptOcr;
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
            $bill = DB::table('payments')
                ->where('id', request('bill_id'))
                ->where('user_id', session('usr_id'))
                ->where('status', 0)
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
        ]);

        $receipt = $request->file('receipt');
        if (!$receipt instanceof UploadedFile || !$this->isSafeReceiptImage($receipt)) {
            throw ValidationException::withMessages([
                'receipt' => 'Invalid receipt file. Upload a valid JPEG or PNG image only.',
            ]);
        }

        $userId = session('usr_id');

        if ($request->bill_id) {
            // Paying an existing bill
            $bill = DB::table('payments')
                ->where('id', $request->bill_id)
                ->where('user_id', $userId)
                ->where('status', 0)
                ->first();

            if (!$bill) {
                return redirect()->back()->with('error', 'Invalid bill selected.');
            }

            DB::table('payments')->where('id', $request->bill_id)->update([
                'payment_method' => $request->payment_method,
                'status' => 1,
                'updated_at' => now(),
            ]);
            $paymentId = $request->bill_id;
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
                ProcessReceiptOcr::dispatch($paymentId, $receiptPath);
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

        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');

        $allowedSort = ['created_at', 'amount', 'status'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        if (in_array(session('usr_role'), ['admin', 'official', 'treasurer'])) {
            // Show all payments
            $query = DB::table('payments')
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select('payments.*', 'gcash_payments.ocr_text', 'gcash_payments.extracted_amount', 'gcash_payments.extracted_reference', 'gcash_payments.confidence_score', 'gcash_payments.receipt_image_path', 'gcash_payments.verified_at', 'users.username as user_name');

            if (in_array(session('usr_role'), ['official', 'treasurer'], true)) {
                $barangayId = $this->currentUserBarangayId();
                if (!$barangayId) {
                    return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
                }
                $query->join('residents', 'residents.user_id', '=', 'payments.user_id')
                    ->where('residents.barangay_id', $barangayId);
            }

            $payments = $query->orderBy($sortBy, $sortOrder)->get();
            return view('payments.index', compact('payments', 'sortBy', 'sortOrder'));
        } else {
            // For residents: separate bills and payment history
            $bills = DB::table('payments')
                ->where('user_id', session('usr_id'))
                ->where('status', 0)
                ->orderBy($sortBy, $sortOrder)
                ->get();

            $payments = DB::table('payments')
                ->where('user_id', session('usr_id'))
                ->where('status', '>', 0)
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->select('payments.*', 'gcash_payments.ocr_text', 'gcash_payments.extracted_amount', 'gcash_payments.extracted_reference', 'gcash_payments.confidence_score', 'gcash_payments.receipt_image_path', 'gcash_payments.verified_at')
                ->orderBy($sortBy, $sortOrder)
                ->get();
            return view('payments.index', compact('bills', 'payments', 'sortBy', 'sortOrder'));
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
            'amount' => 'required|numeric|min:0.01',
        ]);

        $residentUserIds = DB::table('residents')
            ->where('barangay_id', $barangayId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($residentUserIds)) {
            return redirect()->back()->with('error', 'No residents found in your barangay.');
        }

        $existingUnpaid = DB::table('payments')
            ->whereIn('user_id', $residentUserIds)
            ->where('status', 0)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $billableUserIds = array_values(array_diff($residentUserIds, $existingUnpaid));
        $now = now();
        $rows = [];
        foreach ($billableUserIds as $userId) {
            $rows[] = [
                'user_id' => $userId,
                'amount' => $validated['amount'],
                'payment_method' => 0, // 0 for bill
                'status' => 0, // 0 for bill generated
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('payments')->insert($rows);
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
            'Bills generated: ' . count($rows) . '. Skipped (already unpaid): ' . count($existingUnpaid) . '.'
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
            return Storage::disk($disk)->download($payment->receipt_image_path);
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
