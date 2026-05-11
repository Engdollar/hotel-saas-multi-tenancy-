<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotel_housekeeping_tasks', 'assigned_at')) {
            Schema::table('hotel_housekeeping_tasks', function (Blueprint $table) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_to_user_id');
                $table->string('linen_status', 30)->default('not_required')->after('priority');
                $table->unsignedSmallInteger('linen_items_collected')->default(0)->after('linen_status');
                $table->unsignedSmallInteger('linen_items_delivered')->default(0)->after('linen_items_collected');
                $table->string('minibar_status', 30)->default('not_checked')->after('linen_items_delivered');
                $table->timestamp('minibar_restocked_at')->nullable()->after('minibar_status');
                $table->decimal('minibar_charge_amount', 14, 2)->default(0)->after('minibar_restocked_at');
                $table->string('inspection_status', 30)->nullable()->after('minibar_charge_amount');
                $table->foreignId('inspected_by_user_id')->nullable()->after('inspection_status');
                $table->foreign('inspected_by_user_id', 'hotel_hk_inspected_by_fk')->references('id')->on('users')->nullOnDelete();
                $table->text('inspection_notes')->nullable()->after('inspected_by_user_id');
            });
        }

        if (! Schema::hasTable('hotel_room_inspections')) {
            Schema::create('hotel_room_inspections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('hotel_rooms')->cascadeOnDelete();
                $table->foreignId('housekeeping_task_id')->nullable();
                $table->foreign('housekeeping_task_id', 'hotel_room_insp_task_fk')->references('id')->on('hotel_housekeeping_tasks')->nullOnDelete();
                $table->foreignId('inspected_by_user_id')->nullable();
                $table->foreign('inspected_by_user_id', 'hotel_room_insp_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->string('inspection_type', 40)->default('room_ready');
                $table->string('status', 30);
                $table->json('checklist')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('inspected_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'hotel_room_insp_company_status_idx');
                $table->index(['company_id', 'room_id'], 'hotel_room_insp_company_room_idx');
            });
        }

        if (Schema::hasTable('hotel_preventive_maintenance_schedules')) {
            Schema::drop('hotel_preventive_maintenance_schedules');
        }

        if (! Schema::hasTable('hotel_preventive_maintenance_schedules')) {
            Schema::create('hotel_preventive_maintenance_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id');
                $table->foreign('property_id', 'hotel_pm_sched_prop_fk')->references('id')->on('hotel_properties')->cascadeOnDelete();
                $table->foreignId('room_id')->nullable();
                $table->foreign('room_id', 'hotel_pm_sched_room_fk')->references('id')->on('hotel_rooms')->nullOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable();
                $table->foreign('assigned_to_user_id', 'hotel_pm_sched_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('maintenance_category', 40)->nullable();
                $table->string('priority', 20)->default('medium');
                $table->unsignedSmallInteger('frequency_days')->default(30);
                $table->timestamp('last_generated_at')->nullable();
                $table->timestamp('next_due_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'next_due_at'], 'hotel_pm_sched_company_due_idx');
                $table->index(['company_id', 'is_active'], 'hotel_pm_sched_company_active_idx');
            });
        }

        if (! Schema::hasColumn('hotel_maintenance_requests', 'assigned_at')) {
            Schema::table('hotel_maintenance_requests', function (Blueprint $table) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_to_user_id');
                $table->timestamp('work_started_at')->nullable()->after('assigned_at');
                $table->timestamp('work_completed_at')->nullable()->after('work_started_at');
                $table->string('maintenance_category', 40)->nullable()->after('description');
                $table->boolean('is_preventive')->default(false)->after('priority');
                $table->unsignedBigInteger('preventive_maintenance_schedule_id')->nullable()->after('is_preventive');
                $table->foreign('preventive_maintenance_schedule_id', 'hotel_maint_pm_sched_fk')->references('id')->on('hotel_preventive_maintenance_schedules')->nullOnDelete();
                $table->text('technician_notes')->nullable()->after('resolved_at');
                $table->index(['company_id', 'is_preventive'], 'hotel_maint_company_prev_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotel_maintenance_requests', 'assigned_at')) {
            Schema::table('hotel_maintenance_requests', function (Blueprint $table) {
                $table->dropIndex('hotel_maint_company_prev_idx');
                $table->dropForeign('hotel_maint_pm_sched_fk');
                $table->dropColumn([
                    'assigned_at',
                    'work_started_at',
                    'work_completed_at',
                    'maintenance_category',
                    'is_preventive',
                    'preventive_maintenance_schedule_id',
                    'technician_notes',
                ]);
            });
        }

        Schema::dropIfExists('hotel_preventive_maintenance_schedules');
        Schema::dropIfExists('hotel_room_inspections');

        if (Schema::hasColumn('hotel_housekeeping_tasks', 'assigned_at')) {
            Schema::table('hotel_housekeeping_tasks', function (Blueprint $table) {
                $table->dropForeign('hotel_hk_inspected_by_fk');
                $table->dropColumn([
                    'assigned_at',
                    'linen_status',
                    'linen_items_collected',
                    'linen_items_delivered',
                    'minibar_status',
                    'minibar_restocked_at',
                    'minibar_charge_amount',
                    'inspection_status',
                    'inspected_by_user_id',
                    'inspection_notes',
                ]);
            });
        }
    }
};