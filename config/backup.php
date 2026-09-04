<?php

return [
    'timezone' => env('BACKUP_TIMEZONE', 'Europe/Athens'),
    /* Προαιρετικές απόλυτες διαδρομές. Στο Laragon γίνεται και αυτόματος εντοπισμός. */
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH'),
    'mysql_path' => env('BACKUP_MYSQL_PATH'),
];
