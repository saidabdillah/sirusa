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
        $tanggalMulai = $this->scholarship->tanggal_pengumuman?->format('d F Y');
        $tanggalSelesai = $this->scholarship->tanggal_pengumuman_selesai?->format('d F Y');

        return (new MailMessage)
            ->subject('Pengumuman Beasiswa Dibuka - SIRUSA')
            ->greeting('Halo '.($notifiable->username ?? 'Pengguna').',')
            ->line('Pengumuman beasiswa "'.$this->scholarship->nama.'" telah dibuka.')
            ->line('Periode pengumuman: '.$tanggalMulai.' s.d. '.$tanggalSelesai)
            ->line('Pengumuman beasiswa telah dibuka. Silakan cek hasilnya.')
            ->action('Lihat Pengumuman', route('pengumuman.show', $this->scholarship))
            ->salutation('Salam, Tim SIRUSA');
    }

    public function toDatabase(object $notifiable): array
    {
        $tanggalMulai = $this->scholarship->tanggal_pengumuman?->format('d F Y');
        $tanggalSelesai = $this->scholarship->tanggal_pengumuman_selesai?->format('d F Y');

        return [
            'title' => 'Pengumuman Beasiswa Dibuka',
            'message' => 'Beasiswa "'.$this->scholarship->nama.'" diumumkan ('.$tanggalMulai.' s.d. '.$tanggalSelesai.'). Silakan cek hasil pengumumannya.',
            'icon' => 'fa-bullhorn',
            'url' => route('pengumuman.show', $this->scholarship),
        ];
    }

    /**
     * Kirim notifikasi secara synchronous (bypass queue)
     * Dipakai sebagai fallback jika queue worker tidak jalan
     */
    public function sendSync(object $notifiable): void
    {
        $this->sendNow($notifiable);
    }
}