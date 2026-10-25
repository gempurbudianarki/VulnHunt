<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\ReportComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Report $report,
        public ReportComment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[VulnHunt UBBG] Komentar Baru pada Laporan {$this->report->code}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Terdapat tanggapan baru pada laporan kerentanan {$this->report->code}.")
            ->line("Judul Laporan: {$this->report->title}")
            ->action('Buka Laporan', url("/app/laporan/{$this->report->code}"))
            ->line('Catatan Keamanan: Muatan teknis laporan dan tanggapan tidak disertakan di email demi menjaga kerahasiaan celah keamanan.');
    }
}
