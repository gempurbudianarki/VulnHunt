<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\SecuritySetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ScopeController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Halaman Publik & Landing Page
*/
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Override Fortify register.store with throttle:register (10 per hour per IP)
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['guest:'.config('fortify.guard'), 'throttle:register'])
    ->name('register.store');

// Inisialisasi Keamanan Staf (Wajib ganti password & 2FA pada login pertama)
Route::middleware(['auth'])->group(function () {
    Route::get('/keamanan/setup', [SecuritySetupController::class, 'show'])->name('security.setup');
    Route::post('/keamanan/setup', [SecuritySetupController::class, 'save'])->name('security.setup.save');
});

/*
|--------------------------------------------------------------------------
| Portal Peserta (/app)
| Dilindungi Middleware auth dan verified
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('app')->group(function () {
    // 1. Dasbor Ringkasan
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Kelola Tim
    Route::prefix('tim')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::post('/gabung', [TeamController::class, 'join'])->middleware('throttle:join-team')->name('join');
        Route::post('/keluar', [TeamController::class, 'leave'])->name('leave');
        Route::post('/transfer-ketua', [TeamController::class, 'transferLeadership'])->name('transfer-leadership');
        Route::post('/{team}/admin-pindah-ketua', [TeamController::class, 'adminReassignLeader'])->name('admin-reassign-leader');
        Route::delete('/anggota/{id}', [TeamController::class, 'removeMember'])->name('remove-member');
        Route::post('/regenerate-kode', [TeamController::class, 'regenerateInviteCode'])->name('regenerate-code');
    });

    // 3. Aturan Main (Rules of Engagement)
    Route::prefix('aturan')->name('rules.')->group(function () {
        Route::get('/', [RuleController::class, 'index'])->name('index');
        Route::post('/setujui', [RuleController::class, 'accept'])->name('accept');
    });

    // 4. Verifikasi NDA
    Route::prefix('verifikasi')->name('verification.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::get('/template', [VerificationController::class, 'downloadTemplate'])->name('template');
        Route::post('/unggah', [VerificationController::class, 'uploadSigned'])->name('upload');
        Route::get('/unduh/{id}', [VerificationController::class, 'download'])->name('download');
    });

    // 5. Papan Peringkat & Pengumuman
    Route::get('/peringkat', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/pengumuman', [AnnouncementController::class, 'index'])->name('announcements.index');

    // 6. Gerbang Akses Khusus Peserta Approved (middleware: participant.approved)
    Route::middleware(['participant.approved'])->group(function () {
        // Scope
        Route::get('/scope', [ScopeController::class, 'index'])->name('scope.index');

        // Laporan Kerentanan
        Route::prefix('laporan')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/baru', [ReportController::class, 'create'])->name('create');
            Route::post('/', [ReportController::class, 'store'])->name('store');
            Route::get('/{code}', [ReportController::class, 'show'])->name('show');
            Route::post('/{code}/balas', [ReportController::class, 'replyNeedsInfo'])->name('reply');
            Route::get('/{code}/lampiran/{attachmentId}', [ReportController::class, 'downloadAttachment'])->name('attachment.download');
        });
    });
});
