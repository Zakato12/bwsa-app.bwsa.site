<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillBarangayCodes extends Command
{
    protected $signature = 'barangays:backfill-codes';

    protected $description = 'Generate missing barangay codes (brgy_code) for existing barangays.';

    public function handle(): int
    {
        $barangays = DB::table('barangays')
            ->select('id', 'name', 'brgy_code')
            ->orderBy('id')
            ->get();

        if ($barangays->isEmpty()) {
            $this->info('No barangays found.');
            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($barangays as $barangay) {
            if (!empty($barangay->brgy_code)) {
                continue;
            }

            $code = $this->generateBarangayCode((string) $barangay->name, (int) $barangay->id);
            DB::table('barangays')
                ->where('id', $barangay->id)
                ->update([
                    'brgy_code' => $code,
                    'updated_at' => now(),
                ]);

            $updated++;
            $this->line("Updated barangay #{$barangay->id} -> {$code}");
        }

        $this->info("Backfill complete. Updated {$updated} barangay record(s).");
        return self::SUCCESS;
    }

    private function generateBarangayCode(string $name, ?int $excludeId = null): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $name));
        $consonants = preg_replace('/[AEIOU]/', '', $normalized);
        $base = Str::substr((string) $consonants, 0, 4);

        if (Str::length((string) $base) < 3) {
            $base = Str::substr($normalized, 0, 4);
        }

        if (Str::length((string) $base) < 3) {
            $base = str_pad((string) $base, 3, 'X');
        }

        $base = Str::upper((string) $base);
        $candidate = $base;
        $suffix = 2;

        while (true) {
            $exists = DB::table('barangays')
                ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
                ->where('brgy_code', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $candidate = Str::substr($base, 0, max(1, 8 - strlen((string) $suffix))) . $suffix;
            $suffix++;
        }
    }
}
