<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    |
    | With no account and no server copy, a backup file is the only way to
    | recover the taxpayer's records or move them to another device. Backups
    | are written to a folder on this computer, which the taxpayer can change
    | under "Your data".
    |
    */

    'folder' => env('BACKUP_FOLDER', rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir()), '/').'/Documents/BuwisBuddy'),

    'extension' => 'bbk',

    // Home suggests a backup once the last one is this old.
    'reminder_days' => 30,

    // The largest backup file a restore accepts, in kilobytes.
    'max_upload_kb' => 512 * 1024,

];
