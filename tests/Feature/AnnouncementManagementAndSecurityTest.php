<?php

namespace Tests\Feature;

use App\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\User;
use App\Services\MarkdownSanitizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdmin(): User
{
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $admin->assignRole('admin');

    return $admin;
}

function makeTriager(): User
{
    $triager = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $triager->assignRole('triager');

    return $triager;
}

function makeParticipant(): User
{
    $participant = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'rules_accepted_version' => '1',
        'is_active' => true,
    ]);
    $participant->assignRole('participant');

    return $participant;
}

test('triager dan peserta dilarang mengakses halaman kelola pengumuman di filament (403)', function () {
    $triager = makeTriager();
    $participant = makeParticipant();

    $this->actingAs($triager)
        ->get('/admin/announcements')
        ->assertForbidden();

    $this->actingAs($participant)
        ->get('/admin/announcements')
        ->assertForbidden();
});

test('admin dapat mengakses dan melakukan crud pengumuman di panel filament', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->get('/admin/announcements')
        ->assertSuccessful()
        ->assertSee('Pengumuman Lomba');

    // Admin membuat pengumuman via Livewire
    Livewire::actingAs($admin)
        ->test(CreateAnnouncement::class)
        ->fillForm([
            'title' => 'Pengumuman Uji Coba Panitia',
            'body' => "Halo peserta,\n\nIni pengumuman **penting** dari panitia lomba.",
            'is_pinned' => true,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Announcement::where('title', 'Pengumuman Uji Coba Panitia')->first();
    expect($created)->not->toBeNull()
        ->and($created->is_pinned)->toBeTrue()
        ->and($created->created_by)->toBe($admin->id);

    // Admin mengedit pengumuman
    Livewire::actingAs($admin)
        ->test(EditAnnouncement::class, ['record' => $created->id])
        ->fillForm([
            'title' => 'Pengumuman Uji Coba Panitia (Revisi)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $created->refresh();
    expect($created->title)->toBe('Pengumuman Uji Coba Panitia (Revisi)');
});

test('peserta hanya dapat melihat pengumuman yang sudah terbit bukan draft atau terjadwal masa depan', function () {
    $admin = makeAdmin();
    $participant = makeParticipant();

    // 1. Pengumuman sudah terbit
    $published = Announcement::create([
        'title' => 'Pengumuman Resmi Terbit',
        'body' => 'Pengumuman ini telah dipublikasikan kepada peserta.',
        'is_pinned' => false,
        'published_at' => now()->subHour(),
        'created_by' => $admin->id,
    ]);

    // 2. Pengumuman draf (published_at null)
    $draft = Announcement::create([
        'title' => 'Pengumuman Rahasia Draf',
        'body' => 'Draf internal panitia yang belum dipublikasikan.',
        'is_pinned' => false,
        'published_at' => null,
        'created_by' => $admin->id,
    ]);

    // 3. Pengumuman masa depan (published_at > now())
    $future = Announcement::create([
        'title' => 'Pengumuman Terjadwal Besok',
        'body' => 'Pengumuman untuk besok pagi.',
        'is_pinned' => false,
        'published_at' => now()->addDay(),
        'created_by' => $admin->id,
    ]);

    // Peserta mengakses /app/pengumuman
    $response = $this->actingAs($participant)->get(route('announcements.index'));
    $response->assertSuccessful();

    $response->assertSee('Pengumuman Resmi Terbit');
    $response->assertDontSee('Pengumuman Rahasia Draf');
    $response->assertDontSee('Pengumuman Terjadwal Besok');
});

test('pengumuman tersemat (is_pinned) tampil di atas pengumuman lainnya', function () {
    $admin = makeAdmin();
    $participant = makeParticipant();

    $regular = Announcement::create([
        'title' => 'Pengumuman Biasa',
        'body' => 'Isi biasa',
        'is_pinned' => false,
        'published_at' => now()->subMinutes(5),
        'created_by' => $admin->id,
    ]);

    $pinned = Announcement::create([
        'title' => 'Pengumuman PENTING Tersemat',
        'body' => 'Isi sangat penting',
        'is_pinned' => true,
        'published_at' => now()->subHour(), // Lebih lama dari yang biasa, tapi pinned
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($participant)->get(route('announcements.index'));
    $response->assertSuccessful();

    $content = $response->getContent();
    $posPinned = strpos($content, 'Pengumuman PENTING Tersemat');
    $posRegular = strpos($content, 'Pengumuman Biasa');

    expect($posPinned)->toBeLessThan($posRegular);
});

test('markdown disanitasi: payload xss script, onerror, iframe, dan tautan berbahaya tidak tereksekusi', function () {
    $admin = makeAdmin();
    $participant = makeParticipant();

    // Payload XSS berbahaya
    $xssBody = <<<'MD'
# Pengumuman Penting

<script>alert("XSS-SCRIPT")</script>
<img src="invalid-image" onerror="alert('XSS-IMG')">
<iframe src="javascript:alert('XSS-IFRAME')"></iframe>

Silakan klik [Tautan Berbahaya](javascript:alert('XSS-LINK')) atau [Tautan Data](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)

Tautan resmi kami: [Situs Resmi UBBG](https://ubbg.ac.id)
MD;

    $announcement = Announcement::create([
        'title' => 'Uji Coba Serangan XSS',
        'body' => $xssBody,
        'is_pinned' => false,
        'published_at' => now()->subMinute(),
        'created_by' => $admin->id,
    ]);

    // Request HTTP peserta melihat pengumuman
    $response = $this->actingAs($participant)->get(route('announcements.index'));
    $response->assertSuccessful();

    // 1. Tag script tidak boleh ada
    $response->assertDontSee('<script>', false);
    $response->assertDontSee('alert("XSS-SCRIPT")', false);

    // 2. Tag img dengan event handler onerror tidak boleh ada
    $response->assertDontSee('onerror=', false);
    $response->assertDontSee('XSS-IMG', false);

    // 3. Tag iframe tidak boleh ada
    $response->assertDontSee('<iframe', false);
    $response->assertDontSee('XSS-IFRAME', false);

    // 4. Protokol tautan berbahaya javascript: dan data: tidak boleh lolos sebagai tautan aktif
    $response->assertDontSee('href="javascript:', false);
    $response->assertDontSee('href="data:', false);

    // 5. Tautan resmi https://ubbg.ac.id tetap berfungsi aman
    $response->assertSee('href="https://ubbg.ac.id"', false);
    $response->assertSee('Situs Resmi UBBG');
});
