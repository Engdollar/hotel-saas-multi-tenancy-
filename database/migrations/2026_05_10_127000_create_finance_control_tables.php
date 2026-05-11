<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_bank_accounts')) {
            Schema::create('accounting_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->nullable()->constrained('accounting_ledger_accounts')->nullOnDelete();
                $table->string('name');
                $table->string('bank_name')->nullable();
                $table->string('account_number_last4', 10)->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->decimal('current_balance', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('opened_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'is_active'], 'acct_bank_company_active_idx');
                $table->index(['company_id', 'currency_code'], 'acct_bank_company_curr_idx');
            });
        }

        if (! Schema::hasTable('accounting_bank_reconciliations')) {
            Schema::create('accounting_bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bank_account_id')->constrained('accounting_bank_accounts')->cascadeOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('statement_ending_balance', 14, 2)->default(0);
                $table->decimal('book_ending_balance', 14, 2)->default(0);
                $table->decimal('cleared_balance', 14, 2)->default(0);
                $table->string('status', 30)->default('open');
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'acct_bank_rec_company_status_idx');
                $table->index(['company_id', 'period_end'], 'acct_bank_rec_company_end_idx');
            });
        }

        if (! Schema::hasTable('accounting_bank_reconciliation_lines')) {
            Schema::create('accounting_bank_reconciliation_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('bank_reconciliation_id');
                $table->foreign('bank_reconciliation_id', 'acct_bank_rec_line_rec_fk')->references('id')->on('accounting_bank_reconciliations')->cascadeOnDelete();
                $table->string('entry_type', 40)->default('adjustment');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('description');
                $table->date('transaction_date');
                $table->decimal('amount', 14, 2);
                $table->boolean('is_cleared')->default(false);
                $table->timestamp('cleared_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'is_cleared'], 'acct_bank_rec_line_clear_idx');
                $table->index(['reference_type', 'reference_id'], 'acct_bank_rec_line_ref_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bank_reconciliation_lines');
        Schema::dropIfExists('accounting_bank_reconciliations');
        Schema::dropIfExists('accounting_bank_accounts');
    }
};