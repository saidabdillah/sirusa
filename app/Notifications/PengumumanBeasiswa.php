<?php

namespace App\Notifications;

use App\Models\Applicant;
use App\Models\Scholarship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengumumanBeasiswa extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'diumumkan'|'dibayarkan'  $tipe
     */
    public function __construct(
        public Scholarship $scholarship,
        public string $tipe,
        public ?Applicant $applicant = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tanggal = $this->tipe === 'diumumkan'
            ? $this->scholarship->tanggal_pengumuman?->format('d F Y')
            : $this->scholarship->tanggal_pembayaran?->format('d F Y');

        if ($this->tipe === 'diumumkan') {
            $description = 'Beasiswa Anda telah diumumkan pada '.$tanggal.'. Anda dapat mengambil beasiswa terhitung dari tanggal pengumuman tersebut.';
        } else {
            $description = 'Beasiswa Anda telah dibayarkan pada '.$tanggal.'.';
        }

        return (new MailMessage)
            ->subject($this->tipe === 'diumumkan'
                ? 'Pengumuman Beasiswa - SIRUSA'
                : 'Pembayaran Beasiswa - SIRUSA')
            ->greeting('Halo '.($notifiable->username ?? 'Pengguna').',')
            ->line('Beasiswa "'.$this->scholarship->nama.'" '.($this->tipe === 'diumumkan' ? 'telah diumumkan' : 'telah dibayarkan').'.')
            ->line($description)
            ->action('Lihat Pendaftaran', $this->applicant
                ? route('user.pendaftaran.lihat', $this->applicant)
                : route('user.beasiswa.index'))
            ->salutation('Salam, Tim SIRUSA');
    }

    public function toDatabase(object $notifiable): array
    {
        $icon = $this->tipe === 'diumumkan' ? 'fa-bullhorn' : 'fa-money-bill-wave';

        return [
            'title' => $this->tipe === 'diumumkan' ? 'Pengumuman Beasiswa' : 'Pembayaran Beasiswa',
            'message' => 'Beasiswa "'.$this->scholarship->nama.'" '.($this->tipe === 'diumumkan' ? 'telah diumumkan.' : 'telah dibayarkan.'),
            'icon' => $icon,
            'url' => $this->applicant
                ? route('user.pendaftaran.lihat', $this->applicant)
                : route('user.beasiswa.index'),
        ];
    }
}
