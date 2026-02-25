<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gcash_payments')) {
            return;
        }

        Schema::table('gcash_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('gcash_payments', 'rejection_reason')) {
                $table->string('rejection_reason', 255)->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('gcash_payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('gcash_payments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('gcash_payments')) {
            return;
        }

        Schema::table('gcash_payments', function (Blueprint $table) {
            if (Schema::hasColumn('gcash_payments', 'rejected_by')) {
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('gcash_payments', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('gcash_payments', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};

