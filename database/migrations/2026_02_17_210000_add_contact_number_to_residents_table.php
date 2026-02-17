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
        Schema::table('residents', function (Blueprint $table) {
            if (!Schema::hasColumn('residents', 'contact_number')) {
                $table->string('contact_number', 20)->nullable()->after('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            if (Schema::hasColumn('residents', 'contact_number')) {
                $table->dropColumn('contact_number');
            }
        });
    }
};
