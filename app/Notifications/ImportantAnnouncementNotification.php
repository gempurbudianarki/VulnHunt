<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportantAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Announcement $announcement
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[VulnHunt UBBG] Pengumuman Penting: {$this->announcement->title}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Panitia VulnHunt UBBG merilis pengumuman penting:")
            ->line("**{$this->announcement->title}**")
            ->action('Baca Selengkapnya di Dasbor', url('/app/pengumuman'))
            ->line('Harap senantiasa memeriksa dasbor untuk pembaruan terkini.');
    }
}
