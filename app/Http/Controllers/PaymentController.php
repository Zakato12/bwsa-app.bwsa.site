<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function create()
    {
        $bill = null;
        if (request('bill_id')) {
            $bill = DB::table('payments')->where('id', request('bill_id'))->where('user_id', Auth::id())->where('status', 0)->first();
        }
        return view('payments.create', compact('bill'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:1,2',
            'receipt' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->bill_id) {
            // Paying an existing bill
            DB::table('payments')->where('id', $request->bill_id)->update([
                'payment_method' => $request->payment_method,
                'status' => $request->payment_method == 1 ? 2 : 1,
                'updated_at' => now(),
            ]);
            $paymentId = $request->bill_id;
        } else {
            // New payment submission
            $paymentId = DB::table('payments')->insertGetId([
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => $request->payment_method == 1 ? 2 : 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->payment_method == 2) {
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            $ocrData = $this->runOCR($receiptPath);

            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $paymentId],
                [
                    'ocr_text' => $ocrData['text'] ?? null,
                    'extracted_amount' => $ocrData['amount'] ?? null,
                    'extracted_reference' => $ocrData['reference'] ?? null,
                    'confidence_score' => $ocrData['confidence'] ?? null,
                    'receipt_image_path' => $receiptPath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return redirect()->route('payments.index')->with('success', 'Payment submitted.');
    }

    public function index()
    {
        $sortBy = request('sort_by', 'created_at');
        $sortOrder = request('sort_order', 'desc');

        if (in_array(session('usr_role'), ['admin', 'official'])) {
            // Show all payments
            $payments = DB::table('payments')
                ->leftJoin('gcash_payments', 'payments.id', '=', 'gcash_payments.payment_id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select('payments.*', 'gcash_payments.ocr_text', 'gcash_payments.extracted_amount', 'gcash_payments.extracted_reference', 'gcash_payments.confidence_score', 'gcash_payments.receipt_image_path', 'gcash_payments.verified_at', 'users.username as user_name')
                ->orderBy($sortBy, $sortOrder)
                ->get();
            return view('payments.index', compact('payments', 'sortBy', 'sortOrder'));
        } else {
            // For residents: separate bills and payment history
            $bills = DB::table('payments')
                ->where('user_id', Auth::id())
                ->where('status', 0)
                ->orderBy($sortBy, $sortOrder)
                ->get();

            $payments = DB::table('payments')
                ->where('user_id', Auth::id())
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
        DB::table('payments')->where('id', $id)->update(['status' => 2, 'updated_at' => now()]);
        DB::table('gcash_payments')->where('payment_id', $id)->update(['verified_at' => now(), 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Payment verified.');
    }

    public function approve($id)
    {
        DB::table('payments')->where('id', $id)->update(['status' => 3, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Payment approved.');
    }

    public function createBill()
    {
        if (session('usr_role') !== 'treasurer') {
            abort(403);
        }
        $residents = DB::table('users')->where('role', 'resident')->get(); // Assuming role column exists
        return view('payments.create_bill', compact('residents'));
    }

    public function storeBill(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::table('payments')->insert([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'payment_method' => 0, // 0 for bill
            'status' => 0, // 0 for bill generated
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('payments.index')->with('success', 'Bill generated.');
    }

    private function runOCR($imagePath)
    {
        if (!$imagePath) return null;

        $fullPath = storage_path('app/public/' . $imagePath);
        $pythonScript = resource_path('python/ocr.py');

        $command = "python \"$pythonScript\" \"$fullPath\" 2>&1";
        $output = shell_exec($command);

        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || isset($result['error'])) {
            return [
                'text' => 'OCR failed: ' . ($result['error'] ?? 'Error'),
                'amount' => null,
                'reference' => null,
                'confidence' => 0.0,
            ];
        }

        return $result;
    }
}
