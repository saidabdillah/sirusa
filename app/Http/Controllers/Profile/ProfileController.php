<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateProfilRequest;
use App\Models\DokumenAkademik;
use App\Models\Kampus;
use App\Models\UserProfile;
use App\Models\VerifikasiSemester;
use App\Services\WilayahService;
use App\Support\JenjangPendidikan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use RespondsToAjax;

    private const PROVINSI = 'Kalimantan Selatan';

    private const KABUPATEN = 'Balangan';

    private const KABUPATEN_CODE = '6311';

    /**
     * Dokumen yang SATU berkas per mahasiswa.
     *
     * SKTM dan Transkrip sudah tidak ada di daftar ini (permintaan pengguna):
     * SKTM tidak lagi disyaratkan, sedangkan Transkrip digantikan PDDIKTI.
     * Kolom `dokumen_sktm` dan `dokumen_transkrip` sengaja DIBIARKAN di tabel
     * supaya berkas mahasiswa lama tidak hilang -- hanya tidak lagi diunggah
     * atau diminta.
     */
    private array $documentFields = [
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_pddikti',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
    ];

    public function __construct(
        private WilayahService $wilayah
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $isMahasiswa = $user->isMahasiswa();

        // Akun staf tidak punya baris profil, jadi form isian + dokumen dan
        // pilihan wilayah/kampus tidak perlu dihitung -- `getDistricts()` sampai
        // ke API luar, dan itu sia-sia untuk halaman yang hanya menampilkan
        // informasi akun.
        $profileComplete = $isMahasiswa && $user->isProfileComplete();
        $pendaftaranAktif = $isMahasiswa ? $user->blockingApplicant() : null;
        $tahapDapatDiedit = $isMahasiswa ? $user->editableProfileStages() : [];
        $canEditCatpil = in_array('catpil', $tahapDapatDiedit, true);
        $canEditKampus = in_array('kampus', $tahapDapatDiedit, true);
        $canAdvanceSemester = $pendaftaranAktif?->status === 'diterima';
        $semesterRevisi = $canAdvanceSemester
            ? $pendaftaranAktif->verifikasiSemester()
                ->where('status_kampus', VerifikasiSemester::KAMPUS_REVISI)
                ->pluck('semester')
                ->map(fn ($semester) => (int) $semester)
                ->all()
            : [];

        $districts = $isMahasiswa
            ? $this->wilayah->getDistricts(self::KABUPATEN_CODE)
            : [];

        $kampusList = $isMahasiswa
            ? Kampus::with('fakultas.prodi')
                ->orderBy('nama_kampus')
                ->get()
            : collect();

        $kampusJson = $kampusList->map(function (Kampus $kampus) {
            return [
                'nama' => $kampus->nama_kampus,
                'fakultas' => $kampus->fakultas->map(function ($fakultas) {
                    return [
                        'nama' => $fakultas->nama,
                        'prodi' => $fakultas->prodi->map(fn ($prodi) => [
                            'id' => $prodi->id,
                            'nama' => $prodi->nama,
                        ])->values(),
                    ];
                })->values(),
            ];
        });

        // Dokumen KRS/KHS yang sudah tersimpan, dikelompokkan per jenis lalu
        // per semester supaya view tinggal mencarinya. Baris yang ada tetap
        // ditampilkan walau semester aktifnya sudah berubah.
        $dokumenAkademik = $isMahasiswa && $profile
            ? $profile->dokumenAkademik()->get()->groupBy('jenis')
            : collect();

        return view('profil.index', compact(
            'profile',
            'profileComplete',
            'isMahasiswa',
            'districts',
            'kampusList',
            'kampusJson',
            'dokumenAkademik',
            'pendaftaranAktif',
            'canEditCatpil',
            'canEditKampus',
            'canAdvanceSemester',
            'semesterRevisi',
        ) + [
            'jenjangList' => JenjangPendidikan::JENJANG,
            'semesterMap' => JenjangPendidikan::JUMLAH_SEMESTER,
        ]);
    }

    public function update(UpdateProfilRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['provinsi'] = self::PROVINSI;
        $data['kabupaten_kota'] = self::KABUPATEN;

        // Email ada di `users`, bukan di `profil_pengguna`. Ditarik dari payload
        // supaya tidak ikut tersimpan ke tabel profil (tidak fillable, jadi
        // `fill()` akan membuangnya diam-diam kalau tidak dilepas di sini).
        $email = $data['email'] ?? null;
        unset($data['email']);

        $ikutKk = $data['ikut_kk'];

        $data['ikut_kk'] = $ikutKk;
        $data['kk_ikut_famili_lain'] = $ikutKk === 'famili_lain';

        if (! $data['kk_ikut_famili_lain']) {
            $data['nama_wali'] = null;
            $data['nik_wali'] = null;
            $data['pekerjaan_wali'] = null;
            $data['hubungan_wali'] = null;
            $data['ktp_wali'] = null;
            $data['kk_wali'] = null;
        }

        $profile = auth()->user()->profile;
        $uploadDisk = Storage::disk('public');
        $pendaftaranAktif = auth()->user()->blockingApplicant();
        $punyaPendaftaranAktif = $pendaftaranAktif !== null;
        $tahapDapatDiedit = $request->editableStages();

        try {
            foreach (['foto_profil'] as $field) {
                if (! $request->canEditStage('catpil')) {
                    unset($data[$field]);

                    continue;
                }

                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            foreach ($this->documentFields as $field) {
                $stage = UserProfile::editableFieldStage($field);

                if (! $stage || ! $request->canEditStage($stage)) {
                    unset($data[$field]);

                    continue;
                }

                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            if (! $request->canEditStage('kampus')) {
                unset($data['dokumen_prestasi']);
            } elseif ($request->hasFile('dokumen_prestasi')) {
                $prestasi = $profile?->dokumen_prestasi ?? [];
                foreach ($request->file('dokumen_prestasi') as $file) {
                    $path = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $prestasi[] = $path;
                }
                $data['dokumen_prestasi'] = $prestasi;
            }

            $profile = UserProfile::updateOrCreate(
                ['user_id' => auth()->id()],
                $data
            );

            // KRS/KHS disimpan SETELAH profil, dan per semester: satu baris
            // `dokumen_akademik` per (mahasiswa, jenis, semester). Semester
            // yang tidak dikirim di request ini tidak disentuh sama sekali,
            // jadi mengunggah KHS semester 4 tidak pernah menimpa KHS
            // semester 3.
            $semesterAkademikDisimpan = $this->simpanDokumenAkademik($request, $uploadDisk);

            if ($punyaPendaftaranAktif) {
                foreach ($tahapDapatDiedit as $stage) {
                    [$statusField, $catatanField] = UserProfile::verifStages()[$stage];
                    $profile->{$statusField} = 'menunggu';
                    $profile->{$catatanField} = null;
                }
            } else {
                $profile->resetVerification();
            }

            $profile->save();

            if ($email !== null && $email !== auth()->user()->email) {
                auth()->user()->forceFill(['email' => $email])->save();
            }

            if ($pendaftaranAktif?->status === 'diterima' && $semesterAkademikDisimpan !== []) {
                $pendaftaranAktif->verifikasiSemester()
                    ->whereIn('semester', $semesterAkademikDisimpan)
                    ->where('status_kampus', VerifikasiSemester::KAMPUS_REVISI)
                    ->update([
                        'status_kampus' => VerifikasiSemester::KAMPUS_MENUNGGU,
                        'catatan_kampus' => null,
                        'diputuskan_kampus_at' => null,
                        'diputuskan_kampus_oleh' => null,
                    ]);
            }

            // Setelah berkas akademik disimpan, paket semester yang dokumennya
            // kini LENGKAP otomatis masuk antrean Kampus. Pemicunya Simpan,
            // bukan tombol tambahan, supaya tidak ada paket yang tertinggal
            // hanya karena mahasiswa lupa menekan tombol kedua.
            VerifikasiSemester::sinkron(auth()->user());

            return $this->ajaxOk($request, 'Profil berhasil diperbarui', route('profile'));
        } catch (\Throwable $e) {
            // Detail teknisnya masuk log, TIDAK ke layar. Pesan exception
            // database memuat seluruh perintah INSERT beserta nilai-nilainya,
            // jadi menampilkannya sama dengan membocorkan NIK, No. KK, nomor
            // HP, dan data pribadi lain milik mahasiswa yang sedang mengisi.
            Log::error('Gagal update profil: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            return $this->ajaxFail(
                $request,
                'Profil gagal disimpan karena terjadi kesalahan pada sistem. Silakan coba lagi; bila masih gagal, hubungi admin.',
                null,
                [],
                500
            );
        }
    }

    private function deleteIfExists($disk, ?string $path): void
    {
        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Simpan berkas (+ nilai untuk KHS) per semester.
     *
     * Satu baris per (mahasiswa, jenis, semester) -- lihat unique index di
     * migrasinya. Yang penting di sini: baris yang TIDAK dikirim (tidak
     * diunggah ulang dan nilainya kosong) sama sekali tidak disentuh, jadi
     * dokumen semester sebelumnya tidak pernah tertimpa oleh unggahan semester
     * lain. Berkas lama juga tidak dihapus kalau pengguna hanya memperbarui
     * nilainya.
     *
     * KRS TIDAK punya nilai: rencana studi hanya berisi daftar mata kuliah,
     * jadi `nilai` hanya dibaca untuk KHS. Angka nilai yang tetap dikirim
     * pada baris KRS diabaikan, bukan disimpan.
     *
     * @return list<int> Semester yang benar-benar menyimpan file atau nilai baru.
     */
    private function simpanDokumenAkademik(UpdateProfilRequest $request, $uploadDisk): array
    {
        $semesterDisimpan = [];
        // Pada multipart browser, input file berada di FileBag dan TIDAK ikut
        // `$request->input()`. KRS hanya memiliki berkas (tanpa field teks),
        // sehingga mengiterasi input saja membuat seluruh KRS hilang walaupun
        // validasi `hasFile()` sudah lolos. Gabungkan parameter teks dan file
        // agar baris file-only diproses persis seperti request browser nyata.
        $semua = array_replace_recursive(
            (array) $request->input('dokumen_akademik', []),
            (array) $request->file('dokumen_akademik', []),
        );

        foreach ($semua as $jenis => $perSemester) {
            if (! array_key_exists($jenis, DokumenAkademik::JENIS_LABELS)) {
                continue;
            }

            // Nilai hanya milik KHS.
            $punyaNilai = $jenis === DokumenAkademik::JENIS_KHS;

            foreach ((array) $perSemester as $semester => $isi) {
                $semester = (int) $semester;

                if ($semester < 1) {
                    continue;
                }

                $file = $request->canEditAcademicField($jenis, $semester, 'file')
                    ? $request->file("dokumen_akademik.{$jenis}.{$semester}.file")
                    : null;
                $nilai = $punyaNilai && $request->canEditAcademicField($jenis, $semester, 'nilai')
                    ? ($isi['nilai'] ?? null)
                    : null;

                // Baris yang tidak diisi apa-apa dilewati, bukan dikosongkan:
                // form mengirim semua baris semester, dan memperlakukan yang
                // kosong sebagai "hapus" akan menghapus data yang sudah ada.
                if ($file === null && ($nilai === null || $nilai === '')) {
                    continue;
                }

                $dokumen = DokumenAkademik::firstOrNew([
                    'user_id' => auth()->id(),
                    'jenis' => $jenis,
                    'semester' => $semester,
                ]);
                $berubah = false;

                if ($file !== null) {
                    $lama = $dokumen->file;
                    $dokumen->file = $uploadDisk->putFile(
                        'profil/'.auth()->id().'/akademik',
                        $file,
                        'public'
                    );
                    $this->deleteIfExists($uploadDisk, $lama);
                    $berubah = true;
                }

                if ($nilai !== null && $nilai !== '') {
                    $nilaiBaru = number_format((float) $nilai, 2, '.', '');
                    $nilaiLama = $dokumen->nilai === null
                        ? null
                        : number_format((float) $dokumen->nilai, 2, '.', '');

                    if ($nilaiLama !== $nilaiBaru) {
                        $dokumen->nilai = $nilaiBaru;
                        $berubah = true;
                    }
                }

                if (! $berubah) {
                    continue;
                }

                $dokumen->save();
                $semesterDisimpan[] = $semester;
            }
        }

        return array_values(array_unique($semesterDisimpan));
    }
}
