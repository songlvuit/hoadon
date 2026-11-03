<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();

            // 1. Thông tin cần xuất hoá đơn
            $table->string('company_name')->nullable();
            $table->string('tax_code', 20)->nullable()->index();
            $table->string('address', 500)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('payment_method', 30)->default('Chuyển khoản');
            $table->string('quote_ref', 100)->nullable()->index();
            $table->date('requested_date')->nullable();
            $table->text('note')->nullable();

            // 2. Hạng mục + tổng tiền (tính lại tự động từ invoice_items)
            $table->smallInteger('vat_rate')->default(8);
            $table->decimal('subtotal', 15, 0)->default(0);
            $table->decimal('vat_amount', 15, 0)->default(0);
            $table->decimal('total', 15, 0)->default(0);

            // 3. Hoá đơn nháp + xác nhận
            $table->json('draft_files')->nullable();
            $table->json('draft_file_names')->nullable();
            $table->string('draft_status', 20)->default('none')->index();
            $table->string('draft_confirmed_by')->nullable();
            $table->timestamp('draft_confirmed_at')->nullable();
            $table->text('draft_feedback')->nullable();
            $table->string('public_token', 64)->unique();

            // 4. Hoá đơn final
            $table->string('final_file')->nullable();
            $table->string('final_file_name')->nullable();
            $table->string('final_symbol', 20)->nullable();
            $table->string('final_number', 20)->nullable()->index();
            $table->date('final_date')->nullable();
            $table->string('final_lookup_code', 100)->nullable();

            // 5. Thanh toán (tính lại tự động từ payments)
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->decimal('paid_amount', 15, 0)->default(0);
            $table->date('payment_due_date')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->string('description', 1000);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 15, 0)->default(0);
            $table->decimal('amount', 15, 0)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->date('paid_at');
            $table->decimal('amount', 15, 0);
            $table->string('method', 30)->default('Chuyển khoản');
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('actor')->nullable();
            $table->boolean('by_customer')->default(false);
            $table->text('note')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('invoice_activities');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
