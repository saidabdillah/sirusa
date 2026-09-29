<?php

namespace App\Notifications;

use App\Models\Applicant;
use Illuminate\Notifications\Notification;

class ApplicationDecision extends Notification
{
    public function __construct(
        public Applicant $applicant,
        public string $status,
        public ?string $catatan = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $scholarship = $this->applicant->beasiswa?->nama ?? 'beasiswa';

        $message = "Pendaftaran Anda pada Beasiswa {$scholarship} telah diputuskan oleh Admin Sirusa (Kesra) dan kini berstatus {$this->translatedStatus()}.";

        if ($this->status === 'ditolak' && filled($this->catatan)) {
            $message .= ' Alasan: '.$this->catatan;
        }

        return [
            'title' => 'Keputusan Pendaftaran Beasiswa',
            'message' => $message,
            'icon' => $this->status === 'diterima' ? 'fa-award' : 'fa-clipboard-check',
            'url' => route('user.pendaftaran.index'),
        ];
    }

    private function translatedStatus(): string
    {
        // Label diambil dari peta status yang sama dengan badge di UI, supaya
        // mahasiswa membaca "Disetujui" di notifikasi dan "Disetujui" di daftar
        // pendaftaran, bukan dua istilah berbeda untuk satu status.
        return Applicant::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
