<?php

return [
    // How many backup archives to keep (oldest are deleted).
    'keep' => (int) env('BACKUP_KEEP', 14),

    // Path to the dump tool for MySQL/MariaDB (mysqldump or mariadb-dump).
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    // Daily backup time (server timezone) used by the scheduler. Set BACKUP_ENABLED=false to turn off.
    'enabled' => (bool) env('BACKUP_ENABLED', true),
    'time' => env('BACKUP_TIME', '02:30'),
];
