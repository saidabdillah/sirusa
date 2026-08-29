<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewApplication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Applicant $applicant,
        public string $applicantName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pendaftaran Beasiswa Baru',
            'message' => $this->applicantName.' mendaftar beasiswa "'.$this->applicant->beasiswa->nama.'".',
            'icon' => 'fa-user-plus',
            'url' => route('admin.pendaftar.lihat', $this->applicant),
        ];
    }
}
