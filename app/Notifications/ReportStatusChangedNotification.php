<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reportCode,
        public string $toStatus,
        public ?string $reasonNote = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'submitted' => 'Terkirim (Submitted)',
            'in_review' => 'Dalam Peninjauan (In Review)',
            'needs_info' => 'Memerlukan Informasi Tambahan (Needs Info)',
            'valid' => 'Diterima Valid (Valid)',
            'invalid' => 'Ditolak (Invalid)',
            'duplicate' => 'Duplikat (Duplicate)',
            'rewarded' => 'Telah Diberi Hadiah (Rewarded)',
        ];

        $label = $statusLabels[$this->toStatus] ?? $this->toStatus;

        $mail = (new MailMessage)
            ->subject("[UBBG VulnHunt] Pembaruan Status Laporan {$this->reportCode}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Status laporan kerentanan Anda dengan kode {$this->reportCode} telah diperbarui menjadi: **{$label}**.");

        if ($this->toStatus === 'needs_info') {
            $mail->line('Panitia membutuhkan rincian atau klarifikasi tambahan. Silakan buka tautan di bawah untuk memberikan tanggapan.');
        }

        return $mail
            ->action('Lihat Status Laporan', url('/app/laporan/'.$this->reportCode))
            ->line('Terima kasih atas partisipasi Anda dalam program Bug Bounty Kampus UBBG.');
    }
}
