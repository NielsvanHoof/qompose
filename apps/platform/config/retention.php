<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Archived client and dossier retention
    |--------------------------------------------------------------------------
    |
    | Soft-deleted clients and dossiers are permanently purged after this many
    | days, including stored files and related activity log entries.
    |
    */
    'archived_days' => (int) env('RETENTION_ARCHIVED_DAYS', 1095),

    /*
    |--------------------------------------------------------------------------
    | Activity log retention
    |--------------------------------------------------------------------------
    |
    | Entries older than this are removed by the scheduled activitylog:clean
    | command. Kept in sync with archived record retention by default.
    |
    */
    'activity_log_days' => (int) env('RETENTION_ACTIVITY_LOG_DAYS', env('RETENTION_ARCHIVED_DAYS', 1095)),

    /*
    |--------------------------------------------------------------------------
    | Failed queue jobs
    |--------------------------------------------------------------------------
    */
    'failed_jobs_hours' => (int) env('RETENTION_FAILED_JOBS_HOURS', 168),

    /*
    |--------------------------------------------------------------------------
    | Queue job batches
    |--------------------------------------------------------------------------
    */
    'job_batches_hours' => (int) env('RETENTION_JOB_BATCHES_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Database notifications
    |--------------------------------------------------------------------------
    */
    'notifications_days' => (int) env('RETENTION_NOTIFICATIONS_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Client portal access grants
    |--------------------------------------------------------------------------
    |
    | Expired or revoked grants older than this are removed by model:prune.
    |
    */
    'portal_grants_days' => (int) env('RETENTION_PORTAL_GRANTS_DAYS', 90),

];
