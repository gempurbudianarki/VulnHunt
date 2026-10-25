<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Mode
    |--------------------------------------------------------------------------
    | True = Content-Security-Policy-Report-Only (tidak memblokir, catat pelanggaran)
    | False = Content-Security-Policy (mode ENFORCE aktif, blokir pelanggaran)
    */
    'csp_report_only' => env('CSP_REPORT_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Session Idle Timeout (dalam menit)
    |--------------------------------------------------------------------------
    | Batas waktu inaktivitas sesi sebelum otomatis logout.
    | Admin & Triager memiliki batas waktu lebih singkat demi keamanan.
    */
    'idle_timeout_staff' => env('SESSION_IDLE_TIMEOUT_STAFF', 30),
    'idle_timeout_participant' => env('SESSION_IDLE_TIMEOUT_PARTICIPANT', 120),
];
