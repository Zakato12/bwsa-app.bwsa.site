<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResidentAndReportFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not enabled in this environment.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password')->nullable();
            $table->string('full_name');
            $table->unsignedBigInteger('role_id');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('barangay_id')->nullable();
            $table->timestamps();
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('brgy_code')->nullable();
            $table->decimal('payment_amount_per_bill', 10, 2)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });

        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('barangay_id');
            $table->string('address')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('payment_method')->default(2);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function test_resident_update_allows_address_and_contact_change_when_full_name_is_unchanged(): void
    {
        $barangayId = DB::table('barangays')->insertGetId([
            'name' => 'Barangay Maligaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $officialId = DB::table('users')->insertGetId([
            'username' => 'official_1',
            'full_name' => 'Official One',
            'role_id' => 2,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residentUserId = DB::table('users')->insertGetId([
            'username' => 'resident_1',
            'full_name' => 'Juan Dela Cruz',
            'role_id' => 4,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residentId = DB::table('residents')->insertGetId([
            'user_id' => $residentUserId,
            'barangay_id' => $barangayId,
            'address' => 'Purok 1',
            'contact_number' => '09170000001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Existing duplicate full name in legacy data.
        $dupResidentUserId = DB::table('users')->insertGetId([
            'username' => 'resident_2',
            'full_name' => 'Juan Dela Cruz',
            'role_id' => 4,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('residents')->insert([
            'user_id' => $dupResidentUserId,
            'barangay_id' => $barangayId,
            'address' => 'Purok 3',
            'contact_number' => '09170000003',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware()
            ->withSession([
                'usr_id' => $officialId,
                'usr_role' => 'official',
            ])
            ->put(route('residents.update', $residentId), [
                'username' => 'resident_1',
                'full_name' => 'Juan Dela Cruz',
                'address' => 'Purok 2',
                'contact_number' => '09179999999',
                'barangay_id' => $barangayId,
            ]);

        $response->assertRedirect(route('residents.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('residents', [
            'id' => $residentId,
            'address' => 'Purok 2',
            'contact_number' => '09179999999',
        ]);
    }

    public function test_payment_report_filters_by_month_and_year(): void
    {
        $barangayId = DB::table('barangays')->insertGetId([
            'name' => 'Barangay Maligaya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $treasurerId = DB::table('users')->insertGetId([
            'username' => 'treasurer_1',
            'full_name' => 'Treasurer One',
            'role_id' => 3,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residentA = DB::table('users')->insertGetId([
            'username' => 'resident_a',
            'full_name' => 'Resident A',
            'role_id' => 4,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('residents')->insert([
            'user_id' => $residentA,
            'barangay_id' => $barangayId,
            'address' => 'Purok 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residentB = DB::table('users')->insertGetId([
            'username' => 'resident_b',
            'full_name' => 'Resident B',
            'role_id' => 4,
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('residents')->insert([
            'user_id' => $residentB,
            'barangay_id' => $barangayId,
            'address' => 'Purok 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $janPaymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentA,
            'amount' => 50,
            'payment_method' => 2,
            'status' => 3,
            'created_at' => Carbon::create(2026, 1, 15, 10, 0, 0),
            'updated_at' => Carbon::create(2026, 1, 15, 10, 0, 0),
        ]);

        DB::table('payments')->insert([
            'user_id' => $residentB,
            'amount' => 60,
            'payment_method' => 2,
            'status' => 3,
            'created_at' => Carbon::create(2026, 2, 10, 10, 0, 0),
            'updated_at' => Carbon::create(2026, 2, 10, 10, 0, 0),
        ]);

        $response = $this
            ->withoutMiddleware()
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->get(route('reports.payments', ['month' => 1, 'year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('payments', function ($payments) use ($janPaymentId) {
            return $payments->count() === 1 && (int) $payments->first()->id === (int) $janPaymentId;
        });
    }
}
