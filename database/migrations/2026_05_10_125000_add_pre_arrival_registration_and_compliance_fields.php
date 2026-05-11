<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hotel_guest_profiles', 'date_of_birth')) {
            Schema::table('hotel_guest_profiles', function (Blueprint $table) {
                $table->date('date_of_birth')->nullable()->after('phone');
                $table->string('gender', 30)->nullable()->after('date_of_birth');
                $table->string('address_line1')->nullable()->after('gender');
                $table->string('address_line2')->nullable()->after('address_line1');
                $table->string('city', 120)->nullable()->after('address_line2');
                $table->string('state_region', 120)->nullable()->after('city');
                $table->string('postal_code', 40)->nullable()->after('state_region');
                $table->string('country_code', 2)->nullable()->after('postal_code');
                $table->string('tax_identifier', 100)->nullable()->after('country_code');
                $table->string('visa_number', 120)->nullable()->after('tax_identifier');
                $table->date('visa_expiry_date')->nullable()->after('visa_number');
                $table->timestamp('gdpr_consent_at')->nullable()->after('visa_expiry_date');
                $table->timestamp('marketing_consent_at')->nullable()->after('gdpr_consent_at');
                $table->index(['company_id', 'country_code'], 'hotel_guest_company_country_idx');
                $table->index(['company_id', 'date_of_birth'], 'hotel_guest_company_dob_idx');
            });
        }

        if (! Schema::hasColumn('hotel_reservations', 'pre_arrival_status')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->string('pre_arrival_status', 30)->nullable()->after('id_verified_by_user_id');
                $table->timestamp('pre_arrival_submitted_at')->nullable()->after('pre_arrival_status');
                $table->timestamp('pre_arrival_completed_at')->nullable()->after('pre_arrival_submitted_at');
                $table->time('expected_arrival_time')->nullable()->after('pre_arrival_completed_at');
                $table->string('registration_channel', 40)->nullable()->after('expected_arrival_time');
                $table->string('emergency_contact_name')->nullable()->after('registration_channel');
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
                $table->text('compliance_notes')->nullable()->after('emergency_contact_phone');
                $table->index(['company_id', 'pre_arrival_status'], 'hotel_res_company_pre_arrival_idx');
            });
        }

        if (Schema::hasTable('hotel_guest_document_extraction_requests')) {
            Schema::drop('hotel_guest_document_extraction_requests');
        }

        if (! Schema::hasTable('hotel_guest_document_extraction_requests')) {
            Schema::create('hotel_guest_document_extraction_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('guest_identity_document_id');
                $table->foreign('guest_identity_document_id', 'hotel_doc_extract_doc_fk')->references('id')->on('hotel_guest_identity_documents')->cascadeOnDelete();
                $table->foreignId('reservation_id')->nullable();
                $table->foreign('reservation_id', 'hotel_doc_extract_res_fk')->references('id')->on('hotel_reservations')->nullOnDelete();
                $table->string('provider', 60)->default('manual-review');
                $table->string('status', 30)->default('pending');
                $table->json('extracted_payload')->nullable();
                $table->text('failure_message')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'hotel_doc_extract_company_status_idx');
                $table->index(['company_id', 'reservation_id'], 'hotel_doc_extract_company_res_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_guest_document_extraction_requests');

        if (Schema::hasColumn('hotel_reservations', 'pre_arrival_status')) {
            Schema::table('hotel_reservations', function (Blueprint $table) {
                $table->dropIndex('hotel_res_company_pre_arrival_idx');
                $table->dropColumn([
                    'pre_arrival_status',
                    'pre_arrival_submitted_at',
                    'pre_arrival_completed_at',
                    'expected_arrival_time',
                    'registration_channel',
                    'emergency_contact_name',
                    'emergency_contact_phone',
                    'compliance_notes',
                ]);
            });
        }

        if (Schema::hasColumn('hotel_guest_profiles', 'date_of_birth')) {
            Schema::table('hotel_guest_profiles', function (Blueprint $table) {
                $table->dropIndex('hotel_guest_company_country_idx');
                $table->dropIndex('hotel_guest_company_dob_idx');
                $table->dropColumn([
                    'date_of_birth',
                    'gender',
                    'address_line1',
                    'address_line2',
                    'city',
                    'state_region',
                    'postal_code',
                    'country_code',
                    'tax_identifier',
                    'visa_number',
                    'visa_expiry_date',
                    'gdpr_consent_at',
                    'marketing_consent_at',
                ]);
            });
        }
    }
};