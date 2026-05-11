<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotel_reservations', 'actual_check_in_at')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->timestamp('actual_check_in_at')->nullable()->after('check_out_date');
                $table->timestamp('actual_check_out_at')->nullable()->after('actual_check_in_at');
                $table->foreignId('checked_in_by_user_id')->nullable()->after('actual_check_out_at')->constrained('users')->nullOnDelete();
                $table->foreignId('checked_out_by_user_id')->nullable()->after('checked_in_by_user_id')->constrained('users')->nullOnDelete();
                $table->index(['company_id', 'actual_check_in_at'], 'hotel_res_company_checkin_idx');
                $table->index(['company_id', 'actual_check_out_at'], 'hotel_res_company_checkout_idx');
            });
        }

        if (! Schema::hasTable('hotel_room_moves')) {
            Schema::create('hotel_room_moves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reservation_id')->constrained('hotel_reservations')->cascadeOnDelete();
                $table->foreignId('from_room_id')->constrained('hotel_rooms')->cascadeOnDelete();
                $table->foreignId('to_room_id')->constrained('hotel_rooms')->cascadeOnDelete();
                $table->foreignId('moved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->timestamp('moved_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'reservation_id'], 'hotel_room_moves_company_res_idx');
            });
        }

        if (! Schema::hasTable('hotel_housekeeping_tasks')) {
            Schema::create('hotel_housekeeping_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('hotel_rooms')->cascadeOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('hotel_reservations')->nullOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('task_type', 40);
                $table->string('status', 30)->default('pending');
                $table->string('priority', 20)->default('medium');
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'hotel_hk_company_status_idx');
                $table->index(['company_id', 'room_id'], 'hotel_hk_company_room_idx');
            });
        }

        if (! Schema::hasTable('hotel_maintenance_requests')) {
            Schema::create('hotel_maintenance_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('hotel_rooms')->nullOnDelete();
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority', 20)->default('medium');
                $table->string('status', 30)->default('open');
                $table->timestamp('reported_at')->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'hotel_maint_company_status_idx');
                $table->index(['company_id', 'priority'], 'hotel_maint_company_priority_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_maintenance_requests');
        Schema::dropIfExists('hotel_housekeeping_tasks');
        Schema::dropIfExists('hotel_room_moves');

        if (Schema::hasColumn('hotel_reservations', 'actual_check_in_at')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->dropIndex('hotel_res_company_checkin_idx');
                $table->dropIndex('hotel_res_company_checkout_idx');
                $table->dropConstrainedForeignId('checked_out_by_user_id');
                $table->dropConstrainedForeignId('checked_in_by_user_id');
                $table->dropColumn(['actual_check_in_at', 'actual_check_out_at']);
            });
        }
    }
};