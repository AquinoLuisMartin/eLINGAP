<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX benefit_payments_one_active_per_entitlement ON benefit_payments (benefit_entitlement_id) WHERE reversed_at IS NULL');
        DB::statement('CREATE INDEX benefit_entitlements_schedule_id_order ON benefit_entitlements (benefit_schedule_id, id)');
        DB::statement('CREATE INDEX senior_documents_owner_type_id_order ON senior_citizen_documents (senior_citizen_id, document_type, id DESC)');
        DB::statement('CREATE INDEX beneficiaries_senior_status_lookup ON beneficiaries (senior_citizen_id, status)');
        DB::statement('CREATE INDEX audit_logs_recent_activity_order ON audit_logs (created_at DESC, id DESC)');
    }

    public function down(): void
    {
        foreach (['audit_logs_recent_activity_order', 'beneficiaries_senior_status_lookup', 'senior_documents_owner_type_id_order', 'benefit_entitlements_schedule_id_order', 'benefit_payments_one_active_per_entitlement'] as $index) {
            DB::statement('DROP INDEX '.$index);
        }
    }
};
