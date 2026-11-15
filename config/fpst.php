<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aturan Kehadiran & Kuota Audiens Seminar Skripsi (FPST UPB)
    |--------------------------------------------------------------------------
    */
    'seminar_audiens' => [
        // Total maksimal mahasiswa menonton seminar (sudah termasuk penyanggah).
        'maks_total_tonton' => 12,

        // Maksimal mahasiswa berperan sebagai penyanggah dalam 12 kesempatan menonton.
        'maks_penyanggah_per_mahasiswa' => 2,

        // Kapasitas maksimal penonton/peserta per satu sesi seminar.
        'maks_kuota_sesi' => 35,

        // Penegakan prasyarat hadir seminar saat mahasiswa mengajukan seminar miliknya sendiri.
        'enforce_syarat_seminar' => env('FPST_ENFORCE_SYARAT_SEMINAR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Praktik Lapang (PL) & Basis Data Tempat PL (Modul 2)
    |--------------------------------------------------------------------------
    */
    'praktik_lapang' => [
        // Syarat minimal SKS yang telah ditempuh mahasiswa untuk mendaftar PL.
        'min_sks' => (int) env('FPST_PL_MIN_SKS', 90),

        // Kuota bawaan mitra PL per periode akademik.
        'default_kuota_periode' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Verifikasi QR Code pada Surat Resmi Internal
    |--------------------------------------------------------------------------
    | Disembunyikan / dinonaktifkan sementara tanpa menghapus implementasi.
    | Ubah FPST_SURAT_QR_VERIFIKASI=true di .env untuk mengaktifkan kembali.
    */
    'surat_internal' => [
        'qr_verifikasi_enabled' => env('FPST_SURAT_QR_VERIFIKASI', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimasi Skala Peak & Keamanan HTTP (Fase 4)
    |--------------------------------------------------------------------------
    */
    'peak_performance' => [
        'jadwal_cache_ttl_seconds' => (int) env('FPST_JADWAL_CACHE_TTL', 300),
        'security_headers_enabled' => env('FPST_SECURITY_HEADERS', true),
    ],
];
