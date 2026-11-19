<?php

return [
    // Token bearer yang dipakai agent di Local Server KPN (isi di .env: GAFILES_AGENT_TOKEN)
    'agent_token' => env('GAFILES_AGENT_TOKEN'),

    // Opsional: batasi IP publik KPN yang boleh memanggil API agent (pisahkan koma)
    'agent_allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('GAFILES_AGENT_IPS', ''))))),

    // Retensi salinan di Cloud
    'new_retention_days'     => (int) env('GAFILES_NEW_RETENTION_DAYS', 30),
    'restore_retention_days' => (int) env('GAFILES_RESTORE_RETENTION_DAYS', 3),

    // Request pengambilan file dari Local
    'lease_seconds' => (int) env('GAFILES_LEASE_SECONDS', 120),
    'max_attempts'  => (int) env('GAFILES_MAX_ATTEMPTS', 5),

    // --- Pengaman kehilangan data ---------------------------------------------------
    // Cloud hanya boleh menghapus bila file Local di-hash ULANG oleh agent dalam N jam terakhir
    'verify_max_age_hours' => (int) env('GAFILES_VERIFY_MAX_AGE_HOURS', 48),
    // Seberapa sering agent memeriksa ulang file yang masih ada di Cloud (jam) / yang hanya ada di Local (hari)
    'recheck_cloud_hours'  => (int) env('GAFILES_RECHECK_CLOUD_HOURS', 24),
    'audit_days'           => (int) env('GAFILES_AUDIT_DAYS', 7),
    // Saklar darurat: true = SEMUA penghapusan Cloud ditolak (pakai saat restore / maintenance server Local)
    'cleanup_paused'       => (bool) env('GAFILES_CLEANUP_PAUSED', false),

    // --- Peringatan (gafiles:health) --------------------------------------------------
    'heartbeat_max_minutes'  => (int) env('GAFILES_HEARTBEAT_MAX_MINUTES', 15),
    'alert_mail'             => env('GAFILES_ALERT_MAIL'),           // pisahkan koma; kosong = hanya log
    'alert_cooldown_minutes' => (int) env('GAFILES_ALERT_COOLDOWN_MINUTES', 360),

    // --- Cakupan pencadangan ----------------------------------------------------------
    // Nama folder yang TIDAK ikut dicadangkan oleh gafiles:backfill-all / discover (berlaku di folder mana pun,
    // mis. 'temp/' juga mengecualikan 'messenger/temp/'). File sementara tidak perlu dicadangkan.
    'discover_exclude' => ['temp/', 'livewire-tmp/'],

    // Awalan path (format "disk/path", relatif terhadap storage/app) yang BOLEH dihapus dari Cloud lewat menu Kompres.
    // Isi hanya untuk modul yang filenya dilayani lewat FileCacheService::respond() (restore otomatis saat diklik).
    // Modul lain cukup dicadangkan; menghapusnya dari Cloud akan membuat file 404 karena modulnya tidak bisa restore.
    'cloud_delete_prefixes' => array_values(array_filter(array_map('trim', explode(',', (string) env('GAFILES_CLOUD_DELETE_PREFIXES', 'public/mailing-foto/'))))),
];
