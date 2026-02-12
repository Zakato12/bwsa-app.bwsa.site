<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barangays')) {
            return;
        }

        if (!Schema::hasColumn('barangays', 'brgy_code')) {
            Schema::table('barangays', function (Blueprint $table) {
                $table->string('brgy_code', 12)->nullable()->unique()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('barangays')) {
            return;
        }

        if (Schema::hasColumn('barangays', 'brgy_code')) {
            Schema::table('barangays', function (Blueprint $table) {
                $table->dropUnique(['brgy_code']);
                $table->dropColumn('brgy_code');
            });
        }
    }
};
