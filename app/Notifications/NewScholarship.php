<?php

namespace App\Notifications;

use App\Models\Scholarship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewScholarship extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Scholarship $scholarship,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
