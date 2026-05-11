<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotel_folios')) {
            Schema::create('hotel_folios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('hotel_reservations')->nullOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hotel_guest_profiles')->nullOnDelete();
                $table->string('folio_number')->unique();
                $table->string('status', 30)->default('open');
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('balance_amount', 14, 2)->default(0);
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'hotel_folios_company_status_idx');
                $table->index(['company_id', 'reservation_id'], 'hotel_folios_company_res_idx');
            });
        }

        if (! Schema::hasTable('hotel_folio_lines')) {
            Schema::create('hotel_folio_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('folio_id')->constrained('hotel_folios')->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_ledger_accounts')->nullOnDelete();
                $table->string('line_type', 40)->default('charge');
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->date('service_date')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'folio_id'], 'hotel_folio_lines_company_folio_idx');
            });
        }

        if (! Schema::hasTable('accounting_invoices')) {
            Schema::create('accounting_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guest_profile_id')->nullable()->constrained('hotel_guest_profiles')->nullOnDelete();
                $table->foreignId('folio_id')->nullable()->constrained('hotel_folios')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->string('status', 30)->default('draft');
                $table->string('currency_code', 3)->default('USD');
                $table->date('issue_date');
                $table->date('due_date')->nullable();
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('balance_amount', 14, 2)->default(0);
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'acct_inv_company_status_idx');
                $table->index(['company_id', 'issue_date'], 'acct_inv_company_issue_idx');
                $table->index(['source_type', 'source_id'], 'acct_inv_source_idx');
            });
        }

        if (! Schema::hasTable('accounting_invoice_lines')) {
            Schema::create('accounting_invoice_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_ledger_accounts')->nullOnDelete();
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'invoice_id'], 'acct_inv_lines_company_inv_idx');
            });
        }

        if (! Schema::hasTable('accounting_payments')) {
            Schema::create('accounting_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
                $table->string('payment_number')->unique();
                $table->string('payment_method', 40)->default('cash');
                $table->string('currency_code', 3)->default('USD');
                $table->timestamp('paid_at')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('reference')->nullable();
                $table->string('status', 30)->default('posted');
                $table->timestamps();

                $table->index(['company_id', 'invoice_id'], 'acct_pay_company_invoice_idx');
            });
        }

        if (! Schema::hasTable('accounting_refunds')) {
            Schema::create('accounting_refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('accounting_invoices')->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('accounting_payments')->nullOnDelete();
                $table->string('refund_number')->unique();
                $table->string('currency_code', 3)->default('USD');
                $table->timestamp('refunded_at')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('posted');
                $table->timestamps();

                $table->index(['company_id', 'invoice_id'], 'acct_ref_company_invoice_idx');
            });
        }

        if (! Schema::hasTable('accounting_suppliers')) {
            Schema::create('accounting_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone', 60)->nullable();
                $table->string('tax_identifier', 120)->nullable();
                $table->string('status', 30)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'acct_sup_company_status_idx');
            });
        }

        if (! Schema::hasTable('accounting_supplier_bills')) {
            Schema::create('accounting_supplier_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('accounting_suppliers')->cascadeOnDelete();
                $table->string('bill_number')->unique();
                $table->string('status', 30)->default('draft');
                $table->string('currency_code', 3)->default('USD');
                $table->date('bill_date');
                $table->date('due_date')->nullable();
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('balance_amount', 14, 2)->default(0);
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'acct_sb_company_status_idx');
                $table->index(['company_id', 'supplier_id'], 'acct_sb_company_supplier_idx');
            });
        }

        if (! Schema::hasTable('accounting_supplier_bill_lines')) {
            Schema::create('accounting_supplier_bill_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_bill_id')->constrained('accounting_supplier_bills')->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_ledger_accounts')->nullOnDelete();
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'supplier_bill_id'], 'acct_sbl_company_bill_idx');
            });
        }

        if (! Schema::hasTable('accounting_supplier_payments')) {
            Schema::create('accounting_supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_bill_id')->constrained('accounting_supplier_bills')->cascadeOnDelete();
                $table->string('payment_number')->unique();
                $table->string('payment_method', 40)->default('bank_transfer');
                $table->string('currency_code', 3)->default('USD');
                $table->timestamp('paid_at')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('reference')->nullable();
                $table->string('status', 30)->default('posted');
                $table->timestamps();

                $table->index(['company_id', 'supplier_bill_id'], 'acct_sp_company_bill_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_supplier_payments');
        Schema::dropIfExists('accounting_supplier_bill_lines');
        Schema::dropIfExists('accounting_supplier_bills');
        Schema::dropIfExists('accounting_suppliers');
        Schema::dropIfExists('accounting_refunds');
        Schema::dropIfExists('accounting_payments');
        Schema::dropIfExists('accounting_invoice_lines');
        Schema::dropIfExists('accounting_invoices');
        Schema::dropIfExists('hotel_folio_lines');
        Schema::dropIfExists('hotel_folios');
    }
};