<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NdaStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $documentNumber,
        public string $status,
        public ?string $rejectReason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'approved' => 'Disetujui (Approved)',
            'rejected' => 'Ditolak (Rejected)',
            'pending' => 'Menunggu Verifikasi (Pending)',
        ];

        $label = $statusLabels[$this->status] ?? $this->status;

        $mail = (new MailMessage)
            ->subject("[UBBG VulnHunt] Pembaruan Status Verifikasi NDA {$this->documentNumber}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Status verifikasi dokumen Perjanjian Kerahasiaan (NDA) Anda dengan nomor berkas {$this->documentNumber} telah diperbarui menjadi: **{$label}**.");

        if ($this->status === 'approved') {
            $mail->line('Selamat! Dokumen NDA Anda telah diverifikasi oleh panitia. Anda kini dapat mengakses menu Target Scope dan mengirimkan laporan temuan celah keamanan.');
        } elseif ($this->status === 'rejected') {
            $mail->line('Mohon maaf, dokumen NDA yang Anda unggah belum memenuhi persyaratan verifikasi panitia.');
            if ($this->rejectReason) {
                $mail->line("Catatan Panitia: {$this->rejectReason}");
            }
            $mail->line('Silakan perbaiki dan unggah kembali dokumen NDA Anda melalui portal peserta.');
        }

        return $mail
            ->action('Buka Halaman Verifikasi', url('/app/verifikasi'))
            ->line('Terima kasih atas kepatuhan Anda terhadap etika dan aturan kompetisi Bug Bounty Kampus UBBG.');
    }
}
