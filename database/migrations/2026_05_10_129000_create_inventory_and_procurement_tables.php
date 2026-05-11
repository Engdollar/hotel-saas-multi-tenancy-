<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained('hotel_properties')->nullOnDelete();
                $table->foreignId('preferred_supplier_id')->nullable()->constrained('accounting_suppliers')->nullOnDelete();
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('category', 80)->nullable();
                $table->string('unit_of_measure', 30)->default('unit');
                $table->decimal('current_quantity', 14, 2)->default(0);
                $table->decimal('reorder_level', 14, 2)->default(0);
                $table->decimal('par_level', 14, 2)->default(0);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'category'], 'inv_items_company_cat_idx');
                $table->index(['company_id', 'property_id'], 'inv_items_company_prop_idx');
            });
        }

        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('movement_type', 30);
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->decimal('quantity_change', 14, 2);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->timestamp('moved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'inventory_item_id'], 'inv_mov_company_item_idx');
                $table->index(['source_type', 'source_id'], 'inv_mov_source_idx');
            });
        }

        if (! Schema::hasTable('procurement_purchase_orders')) {
            Schema::create('procurement_purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->nullable()->constrained('hotel_properties')->nullOnDelete();
                $table->foreignId('supplier_id')->constrained('accounting_suppliers')->cascadeOnDelete();
                $table->string('purchase_order_number')->unique();
                $table->string('status', 30)->default('draft');
                $table->string('currency_code', 3)->default('USD');
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'proc_po_company_status_idx');
                $table->index(['company_id', 'supplier_id'], 'proc_po_company_supplier_idx');
            });
        }

        if (! Schema::hasTable('procurement_purchase_order_lines')) {
            Schema::create('procurement_purchase_order_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id');
                $table->foreign('purchase_order_id', 'proc_pol_po_fk')->references('id')->on('procurement_purchase_orders')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('description');
                $table->decimal('ordered_quantity', 14, 2);
                $table->decimal('received_quantity', 14, 2)->default(0);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'purchase_order_id'], 'proc_pol_company_po_idx');
            });
        }

        if (! Schema::hasTable('procurement_goods_receipts')) {
            Schema::create('procurement_goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('purchase_order_id');
                $table->foreign('purchase_order_id', 'proc_gr_po_fk')->references('id')->on('procurement_purchase_orders')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->timestamp('received_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'purchase_order_id'], 'proc_gr_company_po_idx');
            });
        }

        if (! Schema::hasTable('procurement_goods_receipt_lines')) {
            Schema::create('procurement_goods_receipt_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goods_receipt_id');
                $table->foreign('goods_receipt_id', 'proc_grl_gr_fk')->references('id')->on('procurement_goods_receipts')->cascadeOnDelete();
                $table->foreignId('purchase_order_line_id');
                $table->foreign('purchase_order_line_id', 'proc_grl_pol_fk')->references('id')->on('procurement_purchase_order_lines')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->decimal('received_quantity', 14, 2);
                $table->decimal('unit_cost', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['company_id', 'inventory_item_id'], 'proc_grl_company_item_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_goods_receipt_lines');
        Schema::dropIfExists('procurement_goods_receipts');
        Schema::dropIfExists('procurement_purchase_order_lines');
        Schema::dropIfExists('procurement_purchase_orders');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
    }
};