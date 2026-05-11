<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_name_guard_name_unique');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
            $table->unique(['company_id', 'name', 'guard_name'], 'roles_company_name_guard_unique');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_name_guard_name_unique');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
            $table->unique(['company_id', 'name', 'guard_name'], 'permissions_company_name_guard_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->index('company_id');
            $table->unique(['company_id', 'key'], 'settings_company_key_unique');
        });

        Schema::table('theme_presets', function (Blueprint $table) {
            $table->dropUnique('theme_presets_slug_unique');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->index('company_id');
            $table->unique(['company_id', 'slug'], 'theme_presets_company_slug_unique');
        });

        Schema::table('template_presets', function (Blueprint $table) {
            $table->dropUnique('template_presets_type_slug_unique');
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->index('company_id');
            $table->unique(['company_id', 'type', 'slug'], 'template_presets_company_type_slug_unique');
        });

        Schema::table('user_dashboard_preferences', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            $table->index('company_id');
        });

        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('user_dashboard_preferences', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('template_presets', function (Blueprint $table) {
            $table->dropUnique('template_presets_company_type_slug_unique');
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique(['type', 'slug']);
        });

        Schema::table('theme_presets', function (Blueprint $table) {
            $table->dropUnique('theme_presets_company_slug_unique');
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique('slug');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_key_unique');
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique('key');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique('permissions_company_name_guard_unique');
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_company_name_guard_unique');
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
