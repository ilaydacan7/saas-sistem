<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Yedek klasörü
    |--------------------------------------------------------------------------
    |
    | Üretimde bunu uygulama sunucusunun dışındaki bir yola veya bağlı bir
    | diske alın: sunucu çökerse yedeğin onunla birlikte gitmesi anlamsızdır.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('yedek')),

    /*
    |--------------------------------------------------------------------------
    | Saklama süresi (gün)
    |--------------------------------------------------------------------------
    */

    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Döküm komutu
    |--------------------------------------------------------------------------
    |
    | Sunucuda PostgreSQL istemcisi kuruluysa "pg_dump" yeterlidir. Yerelde
    | veritabanı konteynerde çalıştığı için komut konteynerin içinde
    | çalıştırılır.
    |
    */

    'dump_command' => env('BACKUP_DUMP_COMMAND', 'pg_dump'),

];
