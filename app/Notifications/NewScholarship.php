<?php

namespace App\Notifications;

use App\Models\Scholarship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewScholarship extends Notification implements ShouldQueue
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
            ->subject('Beasiswa Baru Tersedia - SIRUSA')
            ->greeting('Halo '.($notifiable->username ?? 'Pengguna').',')
            ->line('Beasiswa "'.$this->scholarship->nama.'" telah dibuka di '.$this->scholarship->kampus.'.')
            ->line('Segera daftar sebelum batas waktu '.$this->scholarship->batas_waktu?->translatedFormat('d F Y').'!')
            ->action('Lihat Beasiswa', route('user.beasiswa.lihat', $this->scholarship))
            ->salutation('Salam, Tim SIRUSA');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Beasiswa Baru Tersedia',
            'message' => 'Beasiswa "'.$this->scholarship->nama.'" telah dibuka. Segera daftar sebelum batas waktu!',
            'icon' => 'fa-award',
            'url' => route('user.beasiswa.lihat', $this->scholarship),
        ];
    }
}
