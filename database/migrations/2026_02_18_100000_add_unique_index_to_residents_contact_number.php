<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateExists = \Illuminate\Support\Facades\DB::table('residents')
            ->whereNotNull('contact_number')
            ->where('contact_number', '<>', '')
            ->groupBy('contact_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateExists) {
            throw new \RuntimeException('Cannot add unique index: duplicate contact_number values exist in residents table.');
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->unique('contact_number', 'residents_contact_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropUnique('residents_contact_number_unique');
        });
    }
};
