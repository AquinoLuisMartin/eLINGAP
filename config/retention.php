<?php

return [
    'login_logs_days' => (int) env('LOGIN_LOG_RETENTION_DAYS', 0),
    'sms_delivery_logs_days' => (int) env('SMS_DELIVERY_LOG_RETENTION_DAYS', 0),
    'failed_jobs_days' => (int) env('FAILED_JOB_RETENTION_DAYS', 30),
];
