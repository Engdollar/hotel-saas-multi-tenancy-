<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('branch_code', 40);
            $table->string('name');
            $table->string('property_type', 50)->default('hotel');
            $table->string('timezone', 100)->default('UTC');
            $table->string('currency_code', 3)->default('USD');
            $table->time('check_in_time')->default('14:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'branch_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'property_type']);
        });

        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->decimal('base_rate', 14, 2)->default(0);
            $table->unsignedSmallInteger('capacity_adults')->default(2);
            $table->unsignedSmallInteger('capacity_children')->default(0);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'property_id', 'code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->cascadeOnDelete();
            $table->string('floor_label', 40)->nullable();
            $table->string('room_number', 40);
            $table->string('status', 30)->default('available');
            $table->string('cleaning_status', 30)->default('clean');
            $table->boolean('is_smoking_allowed')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'property_id', 'room_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'cleaning_status']);
        });

        Schema::create('hotel_guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('passport_number', 100)->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('loyalty_number', 100)->nullable();
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_blacklisted')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'last_name']);
            $table->index(['company_id', 'is_vip']);
            $table->index(['company_id', 'is_blacklisted']);
            $table->index(['company_id', 'passport_expiry_date']);
        });

        Schema::create('hotel_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('hotel_properties')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('hotel_rooms')->cascadeOnDelete();
            $table->foreignId('guest_profile_id')->constrained('hotel_guest_profiles')->cascadeOnDelete();
            $table->string('reservation_number')->unique();
            $table->string('booking_source', 50)->default('walk_in');
            $table->string('currency_code', 3)->default('USD');
            $table->string('status', 30)->default('pending');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedSmallInteger('adult_count')->default(1);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->unsignedSmallInteger('night_count')->default(1);
            $table->decimal('rate_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'property_id', 'check_in_date']);
            $table->index(['company_id', 'room_id', 'check_in_date', 'check_out_date'], 'hotel_reservations_room_date_idx');
            $table->index(['company_id', 'guest_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reservations');
        Schema::dropIfExists('hotel_guest_profiles');
        Schema::dropIfExists('hotel_rooms');
        Schema::dropIfExists('hotel_room_types');
        Schema::dropIfExists('hotel_properties');
    }
};