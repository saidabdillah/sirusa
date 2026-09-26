<?php

namespace App\Notifications;

use App\Models\UserProfile;
use Illuminate\Notifications\Notification;

class DataVerificationChanged extends Notification
{
    /**
     * Nama admin yang melakukan verifikasi per tahap, supaya mahasiswa tahu
     * pihak mana yang memverifikasi datanya.
     */
    private const ACTORS = [
        'capil' => 'Admin Dukcapil',
        'kampus' => 'Admin Kampus',
        'kesra' => 'Admin Sirusa (Kesra)',
    ];

    private const TITLES = [
        'capil' => 'Verifikasi Capil',
        'kampus' => 'Verifikasi Kampus',
        'kesra' => 'Verifikasi Kesra',
    ];

    private const ICONS = [
        'capil' => 'fa-id-card',
        'kampus' => 'fa-university',
        'kesra' => 'fa-hand-holding-heart',
    ];

    public function __construct(
        public UserProfile $profile,
        public string $stage,
        public string $status,
        public ?string $catatan = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = 'Data profil Anda telah diverifikasi oleh '
            .$this->actor()
            .' dan kini berstatus '.$this->translatedStatus().'.';

        if (filled($this->catatan) && $this->status !== 'setuju') {
            $message .= ' Catatan: '.$this->catatan;
        }

        return [
            'title' => self::TITLES[$this->stage] ?? 'Status Verifikasi Profil',
            'message' => $message,
            'icon' => self::ICONS[$this->stage] ?? 'fa-clipboard-check',
            'url' => route('profile'),
        ];
    }

    private function actor(): string
    {
        return self::ACTORS[$this->stage] ?? 'Admin Verifikasi';
    }

    private function translatedStatus(): string
    {
        return match ($this->status) {
            'setuju' => 'disetujui',
            'revisi' => 'perlu perbaikan',
            'tolak' => 'ditolak',
            default => 'ditarik kembali ke daftar tunggu',
        };
    }
}
