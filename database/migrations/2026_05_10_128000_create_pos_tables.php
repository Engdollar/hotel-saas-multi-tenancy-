<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotel_pos_cashier_shifts')) {
            Schema::create('hotel_pos_cashier_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('shift_number')->unique();
                $table->string('status', 30)->default('open');
                $table->decimal('opening_cash_amount', 14, 2)->default(0);
                $table->decimal('closing_cash_amount', 14, 2)->default(0);
                $table->decimal('expected_cash_amount', 14, 2)->default(0);
                $table->decimal('variance_amount', 14, 2)->default(0);
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'property_id'], 'hotel_pos_shift_company_prop_idx');
                $table->index(['company_id', 'status'], 'hotel_pos_shift_company_status_idx');
            });
        }

        if (! Schema::hasTable('hotel_pos_orders')) {
            Schema::create('hotel_pos_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
                $table->foreignId('cashier_shift_id')->nullable()->constrained('hotel_pos_cashier_shifts')->nullOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('hotel_reservations')->nullOnDelete();
                $table->foreignId('folio_id')->nullable()->constrained('hotel_folios')->nullOnDelete();
                $table->string('order_number')->unique();
                $table->string('status', 30)->default('open');
                $table->string('payment_method', 30)->default('cash');
                $table->string('service_location', 40)->default('restaurant');
                $table->boolean('charge_to_room')->default(false);
                $table->timestamp('posted_to_folio_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'property_id'], 'hotel_pos_order_company_prop_idx');
                $table->index(['company_id', 'status'], 'hotel_pos_order_company_status_idx');
                $table->index(['company_id', 'cashier_shift_id'], 'hotel_pos_order_company_shift_idx');
            });
        }

        if (! Schema::hasTable('hotel_pos_order_lines')) {
            Schema::create('hotel_pos_order_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('hotel_pos_orders')->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_ledger_accounts')->nullOnDelete();
                $table->string('item_name');
                $table->string('category', 60)->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'pos_order_id'], 'hotel_pos_line_company_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_pos_order_lines');
        Schema::dropIfExists('hotel_pos_orders');
        Schema::dropIfExists('hotel_pos_cashier_shifts');
    }
};