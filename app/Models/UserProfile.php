<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'profil_pengguna';

    /**
     * Default saat baris profil baru dibuat di memori (mis. `updateOrCreate`
     * yang belum menyentuh kolom status). Kolomnya sendiri sudah punya
     * `default('menunggu')` di migrasi, jadi ini cuma menutup jalur pembuatan
     * baris yang tidak lewat DEFAULT database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verif_capil' => 'menunggu',
        'verif_kampus' => 'menunggu',
        'verif_kesra' => 'menunggu',
    ];

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',
        'nim',
        'no_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'telepon',
        'alamat',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'foto_profil',
        'ikut_kk',
        'kk_ikut_wali',
        'nama_ayah',
        'pekerjaan_ayah',
        'nik_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'nik_ibu',
        'nama_wali',
        'pekerjaan_wali',
        'hubungan_wali',
        'nik_wali',
        'prodi_id',
        'ipk',
        'semester',
        'ukt',
        'desil',
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_sktm',
        'dokumen_prestasi',
        'dokumen_transkrip',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
        'verif_capil',
        'verif_kampus',
        'verif_kesra',
        'catatan_capil',
        'catatan_kampus',
        'catatan_kesra',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'kk_ikut_wali' => 'boolean',
            'dokumen_prestasi' => 'array',
            // Tanpa cast, `decimal(10,2)` dibaca sebagai string "1500000.00".
            // Input UKT dirender ke Cleave yang membuang titik tetapi
            // mempertahankan "00", jadi 1500000 -> 150000000 (x100) setiap
            // kali form dibuka lalu disimpan ulang. `decimal:0` memangkas nilai
            // ke bilangan bulat rupiah sebelum masuk ke Blade.
            'ukt' => 'decimal:0',
        ];
    }

    public static function verifStages(): array
    {
        return [
            'capil' => ['verif_capil', 'catatan_capil'],
            'kampus' => ['verif_kampus', 'catatan_kampus'],
            'kesra' => ['verif_kesra', 'catatan_kesra'],
        ];
    }

    /**
     * Prefix route tiap tahap verifikasi. Tidak seragam — Verifikasi Kampus
     * memakai `admin.kampusverif`, bukan `admin.kampus` — jadi pemetaannya
     * harus satu sumber kebenaran, bukan di-hardcode di tiap controller.
     */
    public static function verifRoutePrefixes(): array
    {
        return [
            'capil' => 'capil',
            'kampus' => 'kampusverif',
            'kesra' => 'kesra',
        ];
    }

    public static function verifStageOrder(): array
    {
        return array_keys(self::verifStages());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->desa_kelurahan,
            $this->kecamatan ? 'Kec. '.$this->kecamatan : null,
            $this->kabupaten_kota,
            $this->provinsi,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Data orang tua/wali yang dipilih mahasiswa lewat field "Kartu Keluarga".
     *
     * Satu sumber untuk nama, NIK, dan pekerjaan sekaligus, supaya tidak
     * mungkin ada tampilan yang menampilkan nama ayah tapi NIK ibu.
     * Pemanggil yang hanya butuh satu bagian tetap bisa ambil dari array ini
     * alih-alih menulis `match ($this->ikut_kk)` sendiri-sendiri.
     *
     * `default` memakai ayah supaya profil lama yang `ikut_kk`-nya kosong tetap
     * punya orang tua yang bisa ditampilkan, sama seperti bawaan form.
     *
     * @return array{label: string, nama: ?string, nik: ?string, pekerjaan: ?string}
     */
    public function orangTuaDipilih(): array
    {
        return match ($this->ikut_kk) {
            'wali' => [
                'label' => 'Wali',
                'nama' => $this->nama_wali,
                'nik' => $this->nik_wali,
                'pekerjaan' => $this->pekerjaan_wali,
            ],
            'ibu' => [
                'label' => 'Ibu',
                'nama' => $this->nama_ibu,
                'nik' => $this->nik_ibu,
                'pekerjaan' => $this->pekerjaan_ibu,
            ],
            default => [
                'label' => 'Ayah',
                'nama' => $this->nama_ayah,
                'nik' => $this->nik_ayah,
                'pekerjaan' => $this->pekerjaan_ayah,
            ],
        };
    }

    /**
     * Semua tahap sudah dituntaskan, termasuk tahap Kesra.
     *
     * Perlu diingat bahwa Kesra tidak memverifikasi profil: yang disimpulkan
     *Kesra adalah keputusan terhadap satu pendaftaran, dan keputusan itu bisa
     * berupa penolakan. Jadi nilai `true` berarti "prosesnya selesai", bukan
     * "mahasiswa ini layak diberi beasiswa". Jangan dipakai sebagai syarat
     * kelayakan.
     */
    public function isVerified(): bool
    {
        return $this->verif_capil === 'setuju'
            && $this->verif_kampus === 'setuju'
            && $this->verif_kesra === 'setuju';
    }

    /**
     * Data dasar mahasiswa sudah disetujui Capil.
     *
     * Ini bukan berarti terverifikasi penuh. Verifikasi Capil hanya menyatakan
     * bahwa identitas dan data dasar sudah benar, dan itu sudah cukup untuk
     * boleh mendaftar beasiswa. Campus dan Kesra memeriksa hal yang berbeda --
     * eligibility akademik dan kelayakan pendaftar -- dan baru bisa diperiksa
     * setelah mahasiswa benar-benar mendaftar. Kalau pendaftaran ikut menunggu
     * `isVerified()`, tidak akan pernah ada yang bisa mendaftar, karena tahap
     * kampus dan kesra tidak akan pernah disentuh.
     */
    public function isCapilVerified(): bool
    {
        return $this->verif_capil === 'setuju';
    }

    public function verifStatus(): string
    {
        if ($this->isVerified()) {
            return 'terverifikasi';
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'revisi') {
                return 'revisi';
            }
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'tolak') {
                return 'tolak';
            }
        }

        return 'proses';
    }

    public function verifStageLabels(): array
    {
        return [
            'capil' => 'Verifikasi Capil',
            'kampus' => 'Verifikasi Kampus',
            'kesra' => 'Verifikasi Kesra',
        ];
    }

    /**
     * Keputusan pada satu tahap, beserta label dan warna badge siap tampil.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public function verifStageDecision(string $stage): array
    {
        $statusField = self::verifStages()[$stage][0] ?? null;
        $status = $statusField ? $this->{$statusField} : 'menunggu';

        return [
            'status' => $status,
            'label' => match ($status) {
                'setuju' => 'Disetujui',
                'revisi' => 'Perlu Perbaikan',
                'tolak' => 'Ditolak',
                default => 'Menunggu',
            },
            'badge' => match ($status) {
                'setuju' => 'success',
                'revisi' => 'warning',
                'tolak' => 'danger',
                default => 'secondary',
            },
            'decided' => $status !== 'menunggu',
        ];
    }

    /**
     * Urutan antrean: yang paling butuh tindakan mahasiswa dulu, lalu yang
     * sudah diputuskan dipinda ke bawah agar mudah dibedakan dari yang baru.
     */
    public function verifQueueRank(string $stage): int
    {
        return match ($this->{self::verifStages()[$stage][0]}) {
            'revisi' => 0,
            'menunggu' => 1,
            'tolak' => 2,
            default => 3,
        };
    }

    /**
     * Apakah tahap ini masih relevan untuk ditangani oleh admin tahap tersebut.
     *
     * Syaratnya hanya urutan — semua tahap sebelumnya harus disetujui lebih dulu.
     * Keputusan tahap ini sendiri tidak dikunci, sehingga admin masih dapat
     * mengubah keputusan yang sudah dibuat. Namun, begitu tahap berikutnya sudah
     * diputuskan, mengubah kembali tahap ini akan membatalkan pekerjaan tahap
     * tersebut, jadi barisnya disembunyikan.
     */
    public function canVerifStage(string $stage): bool
    {
        $stages = self::verifStages();
        $order = self::verifStageOrder();
        $index = array_search($stage, $order, true);

        if ($index === false) {
            return false;
        }

        foreach (array_slice($order, 0, $index) as $previous) {
            if ($this->{$stages[$previous][0]} !== 'setuju') {
                return false;
            }
        }

        // Disetujui Capil bukan berarti otomatis terverifikasi di kampus.
        // Verifikasi kampus memeriksa kelayakan akademik mahasiswa yang sedang
        // mendaftar beasiswa, jadi tidak ada yang perlu diverifikasi sebelum
        // ada pendaftaran. Tanpa syarat ini semua mahasiswa masuk antrean
        // kampus padahal belum punya apa pun untuk diperiksa.
        if ($stage === 'kampus' && $this->isCapilVerified()
            && ! $this->user?->applicants()->exists()) {
            return false;
        }

        if ($this->{$stages[$stage][0]} === 'menunggu') {
            return true;
        }

        foreach (array_slice($order, $index + 1) as $downstream) {
            if ($this->{$stages[$downstream][0]} !== 'menunggu') {
                return false;
            }
        }

        return true;
    }

    public function resetVerification(): void
    {
        $stages = self::verifStages();

        foreach ($stages as $stage) {
            $this->{$stage[0]} = 'menunggu';
            $this->{$stage[1]} = null;
        }
    }

    /**
     * Reset semua tahap setelah tahap tertentu, karena urutan verifikasinya
     * jadi tidak berlaku lagi (mis. Capil ditarik kembali saat Kampus sudah
     * disetujui).
     */
    public function resetDownstreamStages(string $stage): void
    {
        $stages = self::verifStages();
        $order = self::verifStageOrder();
        $index = array_search($stage, $order, true);

        if ($index === false) {
            return;
        }

        foreach (array_slice($order, $index + 1) as $downstream) {
            $this->{$stages[$downstream][0]} = 'menunggu';
            $this->{$stages[$downstream][1]} = null;
        }
    }
}
