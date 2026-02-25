<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentOcrWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if ($driver !== 'mysql') {
            $this->markTestSkipped('This test class is configured for MySQL test database only.');
        }

        $databaseName = (string) $connection->getDatabaseName();
        if ($databaseName === '' || stripos($databaseName, 'test') === false) {
            $this->markTestSkipped('Refusing to run without a dedicated MySQL test database (name should include "test").');
        }

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        Schema::dropIfExists('gcash_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('residents');
        Schema::dropIfExists('users');
        Schema::dropIfExists('barangays');

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

        Schema::create('gcash_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->unique();
            $table->text('ocr_text')->nullable();
            $table->decimal('extracted_amount', 12, 2)->nullable();
            $table->string('extracted_reference')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('receipt_image_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamps();
        });
    }

    private function seedActors(bool $sameBarangay = true): array
    {
        $barangayA = DB::table('barangays')->insertGetId([
            'name' => 'Barangay Alpha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $barangayB = DB::table('barangays')->insertGetId([
            'name' => 'Barangay Beta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $treasurerId = DB::table('users')->insertGetId([
            'username' => 'treasurer_a',
            'full_name' => 'Treasurer A',
            'role_id' => 3,
            'status' => 'active',
            'barangay_id' => $barangayA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $residentUserId = DB::table('users')->insertGetId([
            'username' => $sameBarangay ? 'resident_a' : 'resident_b',
            'full_name' => $sameBarangay ? 'Resident A' : 'Resident B',
            'role_id' => 4,
            'status' => 'active',
            'barangay_id' => $sameBarangay ? $barangayA : $barangayB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('residents')->insert([
            'user_id' => $residentUserId,
            'barangay_id' => $sameBarangay ? $barangayA : $barangayB,
            'address' => 'Purok 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$treasurerId, $residentUserId];
    }

    public function test_approve_requires_verified_status(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.approve', $paymentId));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Payment must be verified before approval.');
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 1,
        ]);
    }

    public function test_verify_requires_extracted_amount(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR completed',
            'extracted_amount' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.verify', $paymentId));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'OCR did not extract amount from receipt. Reprocess OCR or review manually.');
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 1,
        ]);
    }

    public function test_verify_blocks_amount_mismatch(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR completed',
            'extracted_amount' => 90.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.verify', $paymentId));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 1,
        ]);
    }

    public function test_resident_cannot_access_ocr_reprocess_route(): void
    {
        [, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR pending. Manual verification may be required.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $residentUserId,
                'usr_role' => 'resident',
            ])
            ->post(route('payments.ocr.reprocess', $paymentId));

        $response->assertStatus(403);
    }

    public function test_treasurer_cannot_verify_other_barangay_payment(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(false);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR completed',
            'extracted_amount' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.verify', $paymentId));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Unauthorized');
    }

    public function test_reject_requires_reason(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR completed',
            'extracted_amount' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.reject', $paymentId), [
                'rejection_reason' => '',
            ]);

        $response->assertSessionHasErrors('rejection_reason');
    }

    public function test_reject_updates_payment_and_gcash_tables(): void
    {
        [$treasurerId, $residentUserId] = $this->seedActors(true);

        $paymentId = DB::table('payments')->insertGetId([
            'user_id' => $residentUserId,
            'amount' => 100.00,
            'payment_method' => 2,
            'status' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('gcash_payments')->insert([
            'payment_id' => $paymentId,
            'receipt_image_path' => 'receipts/sample.jpg',
            'ocr_text' => 'OCR completed',
            'extracted_amount' => 100.00,
            'verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware([\App\Http\Middleware\CheckInactivity::class])
            ->withSession([
                'usr_id' => $treasurerId,
                'usr_role' => 'treasurer',
            ])
            ->post(route('payments.reject', $paymentId), [
                'rejection_reason' => 'Receipt details do not match expected payer.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Payment rejected.');

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 4,
        ]);
        $this->assertDatabaseHas('gcash_payments', [
            'payment_id' => $paymentId,
            'rejection_reason' => 'Receipt details do not match expected payer.',
            'rejected_by' => $treasurerId,
        ]);
    }
}
