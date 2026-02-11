<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessReceiptOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $paymentId;
    private string $receiptPath;

    public function __construct(int $paymentId, string $receiptPath)
    {
        $this->paymentId = $paymentId;
        $this->receiptPath = $receiptPath;
    }

    public function handle(): void
    {
        $disk = $this->receiptDisk();
        $fullPath = Storage::disk($disk)->path($this->receiptPath);
        $pythonScript = resource_path('python/ocr.py');

        if (!file_exists($fullPath) || !file_exists($pythonScript)) {
            Log::error('ocr.missing_file', [
                'payment_id' => $this->paymentId,
                'receipt_path' => $this->receiptPath,
            ]);
            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $this->paymentId],
                [
                    'ocr_text' => 'OCR failed: missing file',
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );
            return;
        }

        $command = 'python ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
        $output = shell_exec($command);
        $result = json_decode((string) $output, true);

        if (json_last_error() !== JSON_ERROR_NONE || isset($result['error'])) {
            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $this->paymentId],
                [
                    'ocr_text' => 'OCR failed: ' . ($result['error'] ?? 'Error'),
                    'extracted_amount' => null,
                    'extracted_reference' => null,
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );
            return;
        }

        DB::table('gcash_payments')->updateOrInsert(
            ['payment_id' => $this->paymentId],
            [
                'ocr_text' => $result['text'] ?? null,
                'extracted_amount' => $result['amount'] ?? null,
                'extracted_reference' => $result['reference'] ?? null,
                'confidence_score' => $result['confidence'] ?? null,
                'updated_at' => now(),
            ]
        );
    }

    private function receiptDisk(): string
    {
        $configured = env('RECEIPT_DISK', 'private');
        $disks = array_keys((array) config('filesystems.disks', []));
        if (in_array($configured, $disks, true)) {
            return $configured;
        }

        return in_array('public', $disks, true) ? 'public' : config('filesystems.default', 'local');
    }
}
