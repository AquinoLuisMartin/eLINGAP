<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('benefit_code', 60)->nullable()->unique();
            $table->string('government_level', 20)->default('LGU');
            $table->json('benefit_rules')->nullable();
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('provisional')->default(false);
        });
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->timestampTz('id_generated_at')->nullable();
            $table->string('applicant_type', 20)->default('SELF');
            $table->string('representative_name', 180)->nullable();
            $table->string('representative_relationship', 100)->nullable();
            $table->string('representative_contact', 30)->nullable();
            $table->boolean('is_bedridden')->default(false);
            $table->boolean('is_indigent')->default(false);
            $table->boolean('has_national_pension')->default(false);
            $table->boolean('has_local_pension')->default(false);
            $table->timestampTz('classification_verified_at')->nullable();
            $table->foreignId('classification_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('tss_verified_at')->nullable();
            $table->foreignId('tss_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tss_request', 500)->nullable();
        });
        Schema::create('benefit_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('reference_number', 40)->unique();
            $table->string('period_key', 40);
            $table->date('eligibility_on');
            $table->dateTimeTz('scheduled_at');
            $table->string('location', 250);
            $table->string('batch_name', 100)->nullable();
            $table->json('barangay_ids');
            $table->text('requirements')->nullable();
            $table->boolean('allow_representative')->default(false);
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('available_funds', 14, 2)->default(0);
            $table->json('rule_snapshot');
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('benefit_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('senior_citizen_id')->constrained()->restrictOnDelete();
            $table->string('period_key', 40);
            $table->unsignedSmallInteger('milestone_age')->nullable();
            $table->date('eligibility_on');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 20)->default('PENDING');
            $table->foreignId('benefit_schedule_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('rule_snapshot');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('claimed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestampsTz();
            $table->unique(['program_id', 'senior_citizen_id', 'period_key'], 'benefit_entitlement_identity');
            $table->index(['status', 'program_id']);
        });
        Schema::create('benefit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_entitlement_id')->constrained()->restrictOnDelete();
            $table->foreignId('benefit_schedule_id')->constrained()->restrictOnDelete();
            $table->string('reference_number', 40)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('claimant_type', 20);
            $table->string('external_reference', 180)->nullable();
            $table->boolean('groceries_received')->default(false);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('paid_at');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestampsTz();
        });
        Schema::create('benefit_schedule_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('benefit_entitlement_id')->constrained()->restrictOnDelete();
            $table->string('outcome', 20)->default('SELECTED');
            $table->timestampsTz();
            $table->unique(['benefit_schedule_id', 'benefit_entitlement_id'], 'benefit_schedule_recipient_identity');
        });
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->foreignId('benefit_entitlement_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('sms_blasts', function (Blueprint $table) {
            $table->foreignId('benefit_schedule_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('approval_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('sms_blasts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('benefit_schedule_id');
            $table->dropColumn('approval_hash');
        });
        Schema::table('sms_messages', fn (Blueprint $table) => $table->dropConstrainedForeignId('benefit_entitlement_id'));
        Schema::dropIfExists('benefit_schedule_recipients');
        Schema::dropIfExists('benefit_payments');
        Schema::dropIfExists('benefit_entitlements');
        Schema::dropIfExists('benefit_schedules');
        Schema::table('senior_citizens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('classification_verified_by');
            $table->dropConstrainedForeignId('tss_verified_by');
            $table->dropColumn(['id_generated_at', 'applicant_type', 'representative_name', 'representative_relationship', 'representative_contact', 'is_bedridden', 'is_indigent', 'has_national_pension', 'has_local_pension', 'classification_verified_at', 'tss_verified_at', 'tss_request']);
        });
        Schema::table('programs', fn (Blueprint $table) => $table->dropColumn(['benefit_code', 'government_level', 'benefit_rules', 'default_amount', 'provisional']));
    }
};
