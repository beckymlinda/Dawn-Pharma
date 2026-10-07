<?php

return [

    // Where gzipped SQL backups are written (inside storage/app, not publicly reachable)
    'path' => storage_path('app/backups'),

    // How many days of backups to keep on the server
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    // Comma-separated addresses that receive each backup by email, so a copy
    // exists off the server. Leave empty to only keep backups on the server.
    'email' => env('BACKUP_EMAIL', ''),

    // Backups larger than this are not emailed (most mail servers reject big attachments)
    'max_email_mb' => (int) env('BACKUP_MAX_EMAIL_MB', 15),

];
