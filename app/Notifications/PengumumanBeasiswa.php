<?php

namespace App\Notifications;

use App\Models\Scholarship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengumumanBeasiswa extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Scholarship $scholarship,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengumuman Penerima Beasiswa - SIRUSA')
            ->greeting('Halo '.($notifiable->username ?? 'Pengguna').',')
            ->line('Pengumuman penerima untuk beasiswa "'.$this->scholarship->nama.'" telah tersedia.')
            ->line('Silakan lihat daftar penerima melalui tautan berikut.')
            ->action('Lihat Pengumuman', route('pengumuman.show', $this->scholarship))
            ->salutation('Salam, Tim SIRUSA');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pengumuman Beasiswa',
            'message' => 'Pengumuman penerima untuk beasiswa "'.$this->scholarship->nama.'" telah tersedia.',
            'icon' => 'fa-bullhorn',
            'url' => route('pengumuman.show', $this->scholarship),
        ];
    }
}
