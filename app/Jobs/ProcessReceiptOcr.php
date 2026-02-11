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

        if (!function_exists('shell_exec')) {
            Log::warning('ocr.shell_exec_disabled', [
                'payment_id' => $this->paymentId,
            ]);
            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $this->paymentId],
                [
                    'ocr_text' => 'OCR unavailable. Manual verification required.',
                    'extracted_amount' => null,
                    'extracted_reference' => null,
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );
            return;
        }

        [$result, $error] = $this->runPythonOcr($pythonScript, $fullPath);
        if ($result === null || isset($result['error'])) {
            DB::table('gcash_payments')->updateOrInsert(
                ['payment_id' => $this->paymentId],
                [
                    'ocr_text' => 'OCR failed. Manual verification required. ' . ($result['error'] ?? $error ?? 'Error'),
                    'extracted_amount' => null,
                    'extracted_reference' => null,
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );
            Log::warning('ocr.execution_failed', [
                'payment_id' => $this->paymentId,
                'error' => $result['error'] ?? $error ?? 'unknown',
            ]);
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

    private function runPythonOcr(string $pythonScript, string $fullPath): array
    {
        $errors = [];
        foreach ($this->pythonCandidates() as $pythonBin) {
            $command = $pythonBin . ' ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($fullPath) . ' 2>&1';
            $output = \shell_exec($command);
            if ($output === null) {
                $errors[] = "{$pythonBin}: no output";
                continue;
            }

            $result = $this->extractJsonPayload((string) $output);
            if (is_array($result)) {
                return [$result, null];
            }

            $snippet = trim((string) $output);
            if ($snippet !== '') {
                $errors[] = "{$pythonBin}: " . mb_substr($snippet, 0, 220);
            }
        }

        if (empty($errors)) {
            return [null, 'No python runtime could execute OCR script'];
        }

        return [null, implode(' | ', $errors)];
    }

    private function extractJsonPayload(string $output): ?array
    {
        $lines = preg_split('/\r\n|\r|\n/', $output) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] !== '{') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode(trim($output), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function pythonCandidates(): array
    {
        $candidates = [];

        $preferred = trim((string) env('OCR_PYTHON_BINARY', ''));
        if ($preferred !== '') {
            $candidates[] = $preferred;
        }

        $fromEnv = trim((string) env('OCR_PYTHON_CANDIDATES', 'python,python3,py -3'));
        foreach (explode(',', $fromEnv) as $entry) {
            $entry = trim($entry);
            if ($entry !== '') {
                $candidates[] = $entry;
            }
        }

        return array_values(array_unique($candidates));
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
