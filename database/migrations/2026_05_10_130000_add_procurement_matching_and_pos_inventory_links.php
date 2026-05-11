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
                if (! Schema::hasColumn('procurement_purchase_orders', 'approved_by_user_id')) {
                    $table->foreignId('approved_by_user_id')->nullable()->after('supplier_id');
                    $table->foreign('approved_by_user_id', 'proc_po_approved_by_fk')->references('id')->on('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('procurement_purchase_orders', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('expected_delivery_date');
                }
            });
        }

        if (Schema::hasTable('procurement_purchase_order_lines')) {
            Schema::table('procurement_purchase_order_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('procurement_purchase_order_lines', 'billed_quantity')) {
                    $table->decimal('billed_quantity', 14, 2)->default(0)->after('received_quantity');
                }
            });
        }

        if (Schema::hasTable('accounting_supplier_bills')) {
            Schema::table('accounting_supplier_bills', function (Blueprint $table) {
                if (! Schema::hasColumn('accounting_supplier_bills', 'purchase_order_id')) {
                    $table->foreignId('purchase_order_id')->nullable()->after('supplier_id');
                    $table->foreign('purchase_order_id', 'acct_sb_po_fk')->references('id')->on('procurement_purchase_orders')->nullOnDelete();
                }

                if (! Schema::hasColumn('accounting_supplier_bills', 'match_status')) {
                    $table->string('match_status', 30)->default('unmatched')->after('status');
                }
            });
        }

        if (Schema::hasTable('accounting_supplier_bill_lines')) {
            Schema::table('accounting_supplier_bill_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('accounting_supplier_bill_lines', 'purchase_order_line_id')) {
                    $table->foreignId('purchase_order_line_id')->nullable()->after('supplier_bill_id');
                    $table->foreign('purchase_order_line_id', 'acct_sbl_pol_fk')->references('id')->on('procurement_purchase_order_lines')->nullOnDelete();
                }

                if (! Schema::hasColumn('accounting_supplier_bill_lines', 'inventory_item_id')) {
                    $table->foreignId('inventory_item_id')->nullable()->after('purchase_order_line_id');
                    $table->foreign('inventory_item_id', 'acct_sbl_item_fk')->references('id')->on('inventory_items')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('hotel_pos_order_lines')) {
            Schema::table('hotel_pos_order_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('hotel_pos_order_lines', 'inventory_item_id')) {
                    $table->foreignId('inventory_item_id')->nullable()->after('ledger_account_id');
                    $table->foreign('inventory_item_id', 'hotel_pos_line_item_fk')->references('id')->on('inventory_items')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hotel_pos_order_lines')) {
            Schema::table('hotel_pos_order_lines', function (Blueprint $table) {
                if (Schema::hasColumn('hotel_pos_order_lines', 'inventory_item_id')) {
                    $table->dropForeign('hotel_pos_line_item_fk');
                    $table->dropColumn('inventory_item_id');
                }
            });
        }

        if (Schema::hasTable('accounting_supplier_bill_lines')) {
            Schema::table('accounting_supplier_bill_lines', function (Blueprint $table) {
                if (Schema::hasColumn('accounting_supplier_bill_lines', 'inventory_item_id')) {
                    $table->dropForeign('acct_sbl_item_fk');
                    $table->dropColumn('inventory_item_id');
                }

                if (Schema::hasColumn('accounting_supplier_bill_lines', 'purchase_order_line_id')) {
                    $table->dropForeign('acct_sbl_pol_fk');
                    $table->dropColumn('purchase_order_line_id');
                }
            });
        }

        if (Schema::hasTable('accounting_supplier_bills')) {
            Schema::table('accounting_supplier_bills', function (Blueprint $table) {
                if (Schema::hasColumn('accounting_supplier_bills', 'match_status')) {
                    $table->dropColumn('match_status');
                }

                if (Schema::hasColumn('accounting_supplier_bills', 'purchase_order_id')) {
                    $table->dropForeign('acct_sb_po_fk');
                    $table->dropColumn('purchase_order_id');
                }
            });
        }

        if (Schema::hasTable('procurement_purchase_order_lines')) {
            Schema::table('procurement_purchase_order_lines', function (Blueprint $table) {
                if (Schema::hasColumn('procurement_purchase_order_lines', 'billed_quantity')) {
                    $table->dropColumn('billed_quantity');
                }
            });
        }

        if (Schema::hasTable('procurement_purchase_orders')) {
            Schema::table('procurement_purchase_orders', function (Blueprint $table) {
                if (Schema::hasColumn('procurement_purchase_orders', 'approved_at')) {
                    $table->dropColumn('approved_at');
                }

                if (Schema::hasColumn('procurement_purchase_orders', 'approved_by_user_id')) {
                    $table->dropForeign('proc_po_approved_by_fk');
                    $table->dropColumn('approved_by_user_id');
                }
            });
        }
    }
};