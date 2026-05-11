<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotel_reservations', 'check_in_signature_name')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->string('check_in_signature_name')->nullable()->after('checked_out_by_user_id');
                $table->string('check_in_signature_path')->nullable()->after('check_in_signature_name');
                $table->timestamp('signed_registration_at')->nullable()->after('check_in_signature_path');
                $table->timestamp('id_verified_at')->nullable()->after('signed_registration_at');
                $table->foreignId('id_verified_by_user_id')->nullable()->after('id_verified_at')->constrained('users')->nullOnDelete();
                $table->index(['company_id', 'signed_registration_at'], 'hotel_res_sign_reg_idx');
            });
        }

        if (! Schema::hasTable('hotel_guest_identity_documents')) {
            Schema::create('hotel_guest_identity_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guest_profile_id')->constrained('hotel_guest_profiles')->cascadeOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('hotel_reservations')->nullOnDelete();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('document_type', 40);
                $table->string('document_number', 120)->nullable();
                $table->string('issuing_country', 100)->nullable();
                $table->date('issued_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->string('file_path')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'guest_profile_id'], 'hotel_gidoc_company_guest_idx');
                $table->index(['company_id', 'document_type'], 'hotel_gidoc_company_type_idx');
                $table->index(['company_id', 'reservation_id'], 'hotel_gidoc_company_res_idx');
            });
        }

        if (! Schema::hasTable('hotel_reservation_visitors')) {
            Schema::create('hotel_reservation_visitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reservation_id')->constrained('hotel_reservations')->cascadeOnDelete();
                $table->string('full_name');
                $table->string('relationship_to_guest', 80)->nullable();
                $table->string('identification_number', 120)->nullable();
                $table->string('phone', 50)->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('checked_out_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'reservation_id'], 'hotel_vis_company_res_idx');
                $table->index(['company_id', 'checked_in_at'], 'hotel_vis_company_in_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reservation_visitors');
        Schema::dropIfExists('hotel_guest_identity_documents');

        if (Schema::hasColumn('hotel_reservations', 'check_in_signature_name')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->dropIndex('hotel_res_sign_reg_idx');
                $table->dropConstrainedForeignId('id_verified_by_user_id');
                $table->dropColumn([
                    'check_in_signature_name',
                    'check_in_signature_path',
                    'signed_registration_at',
                    'id_verified_at',
                ]);
            });
        }
    }
};