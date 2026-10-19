<?php

namespace App\Support;

use App\Enums\UserRole;
use Illuminate\Support\Str;

final class SystemEventLabel
{
    public static function operator(?string $role, ?string $name): string
    {
        if (trim($name ?? '') === '') {
            return 'System / unavailable operator';
        }

        return (UserRole::tryFrom($role ?? '')?->label() ?? 'Unknown role').' '.trim($name);
    }

    public static function for(string $event, string $category = 'Audit Trail'): string
    {
        if ($category === 'SMS Dispatch') {
            return 'SMS delivery: '.Str::lower($event);
        }

        return match ($event) {
            'senior_citizen.requirements_review_requested' => 'Flagged registration requirements for review',
            'senior_citizen.created' => 'Registered a new senior citizen',
            'senior_citizen.imported' => 'Imported a historical senior record for review',
            'senior_citizen.import_completed' => 'Imported a batch of historical senior records for review',
            'senior_citizen.updated' => 'Updated a senior citizen record',
            'senior_citizen.verified' => 'Verified a senior citizen record',
            'senior_citizen.id_generated' => 'Issued an OSCA ID',
            'senior_citizen.qr_enabled' => 'Enabled permanent ID verification',
            'senior_citizen.id_asset_updated' => 'Updated an ID photo or signature',
            'senior_citizen.wet_signature_selected' => 'Selected wet signature for an ID',
            'senior_citizen.id_print_generated' => 'Generated an ID print document',
            'senior_id.officials_updated' => 'Updated ID officials and card assets',
            'senior_citizen.archived' => 'Archived a senior citizen record',
            'senior_citizen.transferred' => 'Recorded a senior citizen transfer',
            'senior_citizen.cancellation_certificate_generated' => 'Generated a Certificate of Cancellation',
            'senior_citizen.deceased_declared' => 'Recorded a senior citizen death',
            'senior_citizen.deceased_corrected' => 'Corrected a senior citizen death record',
            'senior_citizen.classifications_verified' => 'Verified senior citizen classifications',
            'benefit.claimed', 'payout.released' => 'Released a benefit payment',
            'benefit.payment_reversed', 'payout.reversed' => 'Reversed a benefit payment',
            'benefit.schedule_created' => 'Created a benefit payout schedule',
            'benefit.schedule_approved' => 'Approved a benefit payout schedule',
            'benefit.recipients_prepared' => 'Prepared eligible benefit recipients',
            'benefit.recipient_reviewed' => 'Reviewed a benefit recipient',
            'program.created' => 'Created a program',
            'user.created' => 'Created a user account',
            'user.updated' => 'Updated a user account',
            'user.access_changed' => 'Changed account access',
            'user.password_reset' => 'Reset account password',
            'payout.schedule_created' => 'Created a payout schedule',
            'application.created' => 'Submitted an application',
            'application.status_changed' => 'Changed an application status',
            'beneficiary.enrolled' => 'Enrolled a program beneficiary',
            'report.exported' => 'Exported a report',
            'sms.broadcast' => 'Queued an SMS broadcast',
            'sms.retried' => 'Retried an SMS delivery',
            'LOGIN' => 'Signed in',
            'LOGOUT' => 'Signed out',
            'LOGIN_FAILED' => 'Failed sign-in attempt',
            'PASSWORD_CHANGED' => 'Changed account password',
            'PASSWORD_RESET' => 'Reset account password',
            'access.senior-citizens.create' => 'Viewed the senior citizen registration form',
            'access.registrations.index' => 'Viewed senior ID applications',
            default => self::fallback($event),
        };
    }

    private static function fallback(string $event): string
    {
        $access = Str::startsWith($event, 'access.');
        $name = $access ? Str::after($event, 'access.') : $event;

        return ($access ? 'Accessed: ' : '').Str::ucfirst(str_replace(['.', '_', '-'], ' ', $name));
    }
}
