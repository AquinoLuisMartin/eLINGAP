<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 20)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('senior_citizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->string('registration_number', 30)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('name_suffix', 20)->nullable();
            $table->date('birth_date');
            $table->string('sex', 20);
            $table->string('civil_status', 30)->nullable();
            $table->text('address');
            $table->string('contact_number', 30)->nullable();
            $table->string('osca_id_number', 50)->nullable()->unique();
            $table->string('status', 30)->default('PENDING');
            $table->timestampTz('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['barangay_id', 'status']);
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('agency', 180);
            $table->decimal('budget', 14, 2)->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 30)->default('UPCOMING');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['status', 'starts_on']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('application_number', 30)->unique();
            $table->date('applied_on');
            $table->string('status', 30)->default('PENDING');
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['senior_citizen_id', 'program_id']);
            $table->index(['status', 'applied_on']);
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('remarks')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('senior_citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->date('enrolled_on');
            $table->string('status', 30)->default('ACTIVE');
            $table->timestampsTz();

            $table->unique(['program_id', 'senior_citizen_id']);
        });

        Schema::create('payout_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('reference_number', 40)->unique();
            $table->date('scheduled_on');
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('SCHEDULED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('senior_citizen_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('PENDING');
            $table->timestampTz('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['payout_schedule_id', 'senior_citizen_id']);
            $table->index(['status', 'released_at']);
        });

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sms_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_number', 30);
            $table->text('message');
            $table->string('status', 30)->default('QUEUED');
            $table->string('provider_message_id', 120)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampTz('queued_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'created_at']);
        });

        Schema::create('sms_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('provider_message_id', 120)->nullable();
            $table->text('response_payload')->nullable();
            $table->timestampTz('recorded_at')->useCurrent();
            $table->timestampsTz();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('auditable_type', 180)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampsTz();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('senior_citizen_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('senior_citizen_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('changes')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_citizen_histories');
        Schema::dropIfExists('senior_citizen_documents');
        Schema::dropIfExists('system_configurations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sms_delivery_logs');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('payout_schedules');
        Schema::dropIfExists('beneficiaries');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('senior_citizens');
        Schema::dropIfExists('barangays');
    }
};
