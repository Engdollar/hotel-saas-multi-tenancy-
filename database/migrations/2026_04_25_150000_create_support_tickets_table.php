<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->string('category')->default('general');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->text('description');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index(['created_by_user_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
