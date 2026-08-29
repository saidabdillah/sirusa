<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Applicant $applicant,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $description = match ($this->applicant->status) {
            'diterima' => 'Silakan lengkapi berkas tahap 2.',
            'revisi' => 'Silakan perbaiki data pendaftaran Anda.',
            'ditolak' => 'Anda dapat menghubungi admin untuk informasi lebih lanjut.',
            'selesai' => 'Selamat, pendaftaran Anda diterima sepenuhnya.',
            default => 'Silakan periksa status pendaftaran Anda.',
        };

        return (new MailMessage)
            ->subject('Status Pendaftaran Beasiswa Diperbarui - SIRUSA')
            ->greeting('Halo '.($notifiable->username ?? 'Pengguna').',')
            ->line('Pendaftaran Anda untuk beasiswa "'.$this->applicant->beasiswa->nama.'" kini berstatus **'.$this->newStatus.'**.')
            ->line($description)
            ->action('Lihat Pendaftaran', route('user.pendaftaran.lihat', $this->applicant))
            ->salutation('Salam, Tim SIRUSA');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Status Pendaftaran Diperbarui',
            'message' => 'Pendaftaran Anda untuk beasiswa "'.$this->applicant->beasiswa->nama.'" kini berstatus '.$this->newStatus.'.',
            'icon' => 'fa-file-alt',
            'url' => route('user.pendaftaran.lihat', $this->applicant),
        ];
    }
}
