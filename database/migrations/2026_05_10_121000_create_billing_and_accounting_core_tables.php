<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_subscription_plans')) {
            Schema::create('saas_subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->decimal('monthly_price', 14, 2)->default(0);
                $table->decimal('yearly_price', 14, 2)->default(0);
                $table->string('currency_code', 3)->default('USD');
                $table->unsignedInteger('max_properties')->default(1);
                $table->unsignedInteger('max_users')->default(10);
                $table->unsignedInteger('max_storage_gb')->default(10);
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'code']);
            });
        }

        if (! Schema::hasTable('saas_tenant_subscriptions')) {
            Schema::create('saas_tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subscription_plan_id')->constrained('saas_subscription_plans')->restrictOnDelete();
                $table->string('status', 30)->default('trial');
                $table->string('billing_cycle', 30)->default('monthly');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('renews_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['subscription_plan_id', 'status']);
            });
        }

        if (! Schema::hasTable('accounting_ledger_accounts')) {
            Schema::create('accounting_ledger_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('code', 40);
                $table->string('name');
                $table->string('type', 30);
                $table->string('subtype', 60)->nullable();
                $table->string('currency_code', 3)->default('USD');
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'code']);
                $table->index(['company_id', 'type']);
                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('accounting_journal_entries')) {
            Schema::create('accounting_journal_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('entry_number')->unique();
                $table->date('entry_date');
                $table->string('currency_code', 3)->default('USD');
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('description');
                $table->string('status', 30)->default('draft');
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'entry_date']);
                $table->index(['company_id', 'status']);
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('accounting_journal_entry_lines')) {
            Schema::create('accounting_journal_entry_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('journal_entry_id')->constrained('accounting_journal_entries')->cascadeOnDelete();
                $table->foreignId('ledger_account_id')->constrained('accounting_ledger_accounts')->cascadeOnDelete();
                $table->string('description')->nullable();
                $table->decimal('debit_amount', 14, 2)->default(0);
                $table->decimal('credit_amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'ledger_account_id'], 'acct_jel_company_ledger_idx');
                $table->index(['journal_entry_id', 'ledger_account_id'], 'acct_jel_journal_ledger_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_entry_lines');
        Schema::dropIfExists('accounting_journal_entries');
        Schema::dropIfExists('accounting_ledger_accounts');
        Schema::dropIfExists('saas_tenant_subscriptions');
        Schema::dropIfExists('saas_subscription_plans');
    }
};