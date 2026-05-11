<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('procurement_purchase_orders')) {
            Schema::table('procurement_purchase_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('procurement_purchase_orders', 'match_status')) {
                    $table->string('match_status', 30)->default('unmatched')->after('status');
                }

                if (! Schema::hasColumn('procurement_purchase_orders', 'quantity_tolerance_percent')) {
                    $table->decimal('quantity_tolerance_percent', 8, 2)->default(0)->after('total_amount');
                }

                if (! Schema::hasColumn('procurement_purchase_orders', 'amount_tolerance_percent')) {
                    $table->decimal('amount_tolerance_percent', 8, 2)->default(0)->after('quantity_tolerance_percent');
                }
            });
        }

        if (! Schema::hasTable('procurement_purchase_order_approvals')) {
            Schema::create('procurement_purchase_order_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id');
                $table->foreign('purchase_order_id', 'proc_poa_po_fk')->references('id')->on('procurement_purchase_orders')->cascadeOnDelete();
                $table->unsignedInteger('sequence_number');
                $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 30)->default('pending');
                $table->timestamp('acted_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'purchase_order_id'], 'proc_poa_company_po_idx');
                $table->index(['purchase_order_id', 'sequence_number'], 'proc_poa_po_seq_idx');
            });
        }

        if (Schema::hasTable('hotel_pos_orders')) {
            Schema::table('hotel_pos_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('hotel_pos_orders', 'voided_at')) {
                    $table->timestamp('voided_at')->nullable()->after('paid_at');
                }

                if (! Schema::hasColumn('hotel_pos_orders', 'void_reason')) {
                    $table->string('void_reason', 255)->nullable()->after('voided_at');
                }
            });
        }

        if (Schema::hasTable('hotel_pos_order_lines')) {
            Schema::table('hotel_pos_order_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('hotel_pos_order_lines', 'modifiers')) {
                    $table->json('modifiers')->nullable()->after('category');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'modifier_total_amount')) {
                    $table->decimal('modifier_total_amount', 14, 2)->default(0)->after('modifiers');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'kitchen_station')) {
                    $table->string('kitchen_station', 60)->nullable()->after('modifier_total_amount');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'kitchen_status')) {
                    $table->string('kitchen_status', 30)->default('pending')->after('kitchen_station');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'sent_to_kitchen_at')) {
                    $table->timestamp('sent_to_kitchen_at')->nullable()->after('kitchen_status');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'kitchen_completed_at')) {
                    $table->timestamp('kitchen_completed_at')->nullable()->after('sent_to_kitchen_at');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'wasted_quantity')) {
                    $table->decimal('wasted_quantity', 10, 2)->default(0)->after('total_amount');
                }

                if (! Schema::hasColumn('hotel_pos_order_lines', 'wastage_reason')) {
                    $table->string('wastage_reason', 255)->nullable()->after('wasted_quantity');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hotel_pos_order_lines')) {
            Schema::table('hotel_pos_order_lines', function (Blueprint $table) {
                foreach (['wastage_reason', 'wasted_quantity', 'kitchen_completed_at', 'sent_to_kitchen_at', 'kitchen_status', 'kitchen_station', 'modifier_total_amount', 'modifiers'] as $column) {
                    if (Schema::hasColumn('hotel_pos_order_lines', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('hotel_pos_orders')) {
            Schema::table('hotel_pos_orders', function (Blueprint $table) {
                foreach (['void_reason', 'voided_at'] as $column) {
                    if (Schema::hasColumn('hotel_pos_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('procurement_purchase_order_approvals');

        if (Schema::hasTable('procurement_purchase_orders')) {
            Schema::table('procurement_purchase_orders', function (Blueprint $table) {
                foreach (['amount_tolerance_percent', 'quantity_tolerance_percent', 'match_status'] as $column) {
                    if (Schema::hasColumn('procurement_purchase_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};