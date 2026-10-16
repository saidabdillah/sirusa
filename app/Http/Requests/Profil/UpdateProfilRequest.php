<?php

namespace App\Http\Requests\Profil;

use App\Models\Prodi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfilRequest extends FormRequest
{
    /**
     * Kolom-kolom NIK/No. KK yang harus saling berbeda, dalam urutan form.
     *
     * Kelimanya adalah nomor identitas orang yang berbeda-beda (mahasiswa, kartu
     * keluarga, ayah, ibu, wali), jadi satu nomor tidak boleh dipakai di dua
     * kolom: itu tanda salah isi, bukan variasi data yang sah. Nomor KK dan NIK
     * memang dua nomor yang berbeda pula, jadi No. KK pun tidak boleh sama
     * dengan NIK mana pun.
     *
     * Tiap kolom menyebut SEMUA kolom lain (bukan hanya yang di atasnya) supaya
     * pesan galatnya muncul tepat di kolom yang bentrok. `different` dilewati
     * Laravel untuk nilai string kosong, jadi kolom opsional seperti `nik_wali`
     * tidak ikut menghasilkan pesan saat memang sedang tidak diisi.
     *
     * @var array<string, string>
     */
    private const BEDA_DARI_NIK_LAIN = [
        'nik' => 'different:no_kk,nik_ayah,nik_ibu,nik_wali',
        'no_kk' => 'different:nik,nik_ayah,nik_ibu,nik_wali',
        'nik_ayah' => 'different:nik,no_kk,nik_ibu,nik_wali',
        'nik_ibu' => 'different:nik,no_kk,nik_ayah,nik_wali',
        'nik_wali' => 'different:nik,no_kk,nik_ayah,nik_ibu',
    ];

    public function authorize(): bool
    {
        // Hanya mahasiswa yang punya profil isian + dokumen. Akun staf
        // (super_admin/kesra/kampus/catpil) tidak punya baris profil, jadi form
        // ini tidak boleh bisa disimpan untuk mereka meski formnya disembunyikan
        // di halaman profil.
        return $this->user()->isMahasiswa();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ikut_kk' => $this->input('ikut_kk', 'ayah'),
            'ukt' => self::normalisasiRupiah($this->input('ukt')),
        ]);
    }

    /**
     * Buang semua karakter selain digit dari nominal UKT.
     *
     * Form mengirim angka berformat Indonesia ("1.500.000"), jadi pemisah ribuan
     * harus dibuang. `str_replace('.', '')` saja tidak cukup: pengguna juga bisa
     * mengetik koma atau spasi, dan sisa karakter itu akan lolos ke `numeric`
     * lalu disimpan sebagai 0.
     */
    private static function normalisasiRupiah(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public function rules(): array
    {
        $pakaiWali = $this->input('ikut_kk') === 'wali';

        $pekerjaan = 'in:PNS/TNI/Polri,Swasta,Wiraswasta,Petani,Buruh,Tidak Bekerja,Lainnya';

        // NIK dan NIM unik antar mahasiswa. Tabelnya sudah punya index unique
        // sejak awal, tapi tanpa rule di sini pelanggarannya baru ketahuan di
        // database: mahasiswa melihat pesan SQL mentah berikut seluruh isi
        // datanya, bukan pesan yang bisa dimengerti.
        //
        // `ignore()` memakai id baris profil milik sendiri supaya menyimpan
        // ulang tanpa mengubah NIK/NIM tidak dianggap bentrok dengan diri
        // sendiri.
        //
        // Diambil lewat `profile()` (query segar), BUKAN `profile` (relasi
        // yang bisa sudah ter-cache): pada request yang sama relasi itu bisa
        // terlanjur di-cache sebagai `null` -- misalnya saat request pertama
        // memang belum punya profil -- sehingga `ignore()` tidak mengecualikan
        // apa pun dan penyimpanan ulang ditolak oleh barisnya sendiri.
        // Saat profilnya belum ada, hasilnya `null` dan tidak ada baris yang
        // perlu dikecualikan.
        $profilId = $this->user()?->profile()->value('id');

        return [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Unik per akun. `ignore` memakai id akun yang sedang diedit,
                // jadi menyimpan tanpa mengubah email tidak akan bentrok
                // dengan dirinya sendiri.
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('profil_pengguna', 'nik')->ignore($profilId),
                self::BEDA_DARI_NIK_LAIN['nik'],
            ],
            // No. KK berdiri sendiri: wajib 16 digit angka dan bukan salinan
            // NIK siapa pun. Kolom ini SENGAJA tidak unik antar mahasiswa: dua
            // mahasiswa bersaudara memakai nomor KK yang sama, jadi mengunci
            // keunikannya akan menolak pendaftaran yang sah.
            'no_kk' => ['required', 'digits:16', self::BEDA_DARI_NIK_LAIN['no_kk']],
            'nim' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('profil_pengguna', 'nim')->ignore($profilId),
            ],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'in:Islam,Kristen,Katholik,Hindu,Buddha,Konghucu'],
            // Nomor HP khusus Indonesia: diawali `08` lalu 8-11 digit lagi,
            // jadi total 10-13 digit. Cleave di form sudah menahan huruf dan
            // memotong di digit ke-13, tapi aturan ini yang menutup jalur
            // tanpa JavaScript (POST yang dirakit manual).
            'telepon' => ['required', 'string', 'regex:/^08[0-9]{8,11}$/'],
            'alamat' => ['required', 'string'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota' => ['nullable', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'desa_kelurahan' => ['required', 'string', 'max:255'],
            'nama_kampus' => ['required', 'string', 'max:255'],
            'fakultas' => ['required', 'string', 'max:255'],
            'prodi_id' => ['required', 'exists:prodi,id'],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'semester' => ['required', 'integer', 'between:1,14'],
            'ukt' => ['required', 'numeric', 'min:0'],
            'desil' => ['required', 'integer', 'between:1,10'],
            'ikut_kk' => ['required', 'in:ayah,ibu,wali'],

            // Ketiga blok (ayah, ibu, wali) selalu tampil di form dan selalu bertanda *,
            // jadi wali hanya wajib bila KK-nya memang mengikuti wali.
            'nama_ayah' => ['required', 'string', 'max:255'],
            'nik_ayah' => ['required', 'digits:16', self::BEDA_DARI_NIK_LAIN['nik_ayah']],
            'pekerjaan_ayah' => ['required', $pekerjaan],
            'nama_ibu' => ['required', 'string', 'max:255'],
            'nik_ibu' => ['required', 'digits:16', self::BEDA_DARI_NIK_LAIN['nik_ibu']],
            'pekerjaan_ibu' => ['required', $pekerjaan],
            'nama_wali' => $pakaiWali ? ['required', 'string', 'max:255'] : ['nullable'],
            // Saat wali tidak dipakai, kolomnya `nullable` dan `different`
            // otomatis dilewati (Laravel melewati rule non-implicit untuk nilai
            // string kosong), jadi tidak ada pesan bentrok yang muncul dari
            // kolom yang memang sedang tidak diisi.
            'nik_wali' => $pakaiWali
                ? ['required', 'digits:16', self::BEDA_DARI_NIK_LAIN['nik_wali']]
                : ['nullable'],
            'pekerjaan_wali' => $pakaiWali ? ['required', $pekerjaan] : ['nullable'],
            'hubungan_wali' => $pakaiWali ? ['required', 'in:Paman,Bibi,Kakek,Nenek,Lainnya'] : ['nullable'],

            'foto_profil' => $this->foto('foto_profil'),
            'dokumen_ktp' => $this->dokumen('dokumen_ktp'),
            'dokumen_kk' => $this->dokumen('dokumen_kk'),
            'dokumen_desil' => $this->dokumen('dokumen_desil'),
            'dokumen_sktm' => $this->dokumen('dokumen_sktm'),
            'dokumen_transkrip' => $this->dokumen('dokumen_transkrip'),
            'dokumen_surat_aktif' => $this->dokumen('dokumen_surat_aktif'),
            'dokumen_surat_pernyataan' => $this->dokumen('dokumen_surat_pernyataan'),
            'dokumen_bukti_ukt' => $this->dokumen('dokumen_bukti_ukt'),
            'ktp_ayah' => $this->dokumen('ktp_ayah'),
            'ktp_ibu' => $this->dokumen('ktp_ibu'),
            'ktp_wali' => $pakaiWali ? $this->dokumen('ktp_wali') : ['nullable'],
            'kk_wali' => $pakaiWali ? $this->dokumen('kk_wali') : ['nullable'],
            'dokumen_prestasi' => ['nullable', 'array'],
            'dokumen_prestasi.*' => ['file', 'mimes:pdf', 'max:2048'],
        ];
    }

    /**
     * Cascade kampus -> fakultas -> prodi harus konsisten satu sama lain.
     *
     * `nama_kampus` dan `fakultas` sengaja tidak fillable, jadi yang benar-benar
     * tersimpan hanyalah `prodi_id`. Setelah ini, prodi yang tersimpan adalah
     * satu-satunya sumber kebenaran: seluruh tampilan (Kelompok Data Kampus di
     * `partials/profil-detail`, gate pendaftaran beasiswa, dan cek kelayakan
     * menuruni lewat `prodi->fakultas->kampus`.
     *
     * Tanpa pemeriksaan ini, form bisa saja mengirim kombinasi yang tidak
     * mungkin terjadi -- misalnya prodi dari Universitas A tapi nama kampus
     * Universitas B. Data yang tersimpan tetap benar (hanya prodi_id), tapi
     * yang tampil di form lain mengikuti `prodi_id`, sehingga mahasiswa
     * melihat pilihan yang berbeda dari yang ia kirim tanpa diberi tahu
     * alasannya.
     *
     * Pesan sengaja ditambahkan ke `prodi_id`, bukan ke `nama_kampus`/
     * `fakultas`: dua field itu hanya input penanda, bukan data yang disimpan,
     * jadi error harus menunjuk ke kontrol yang benar-benar bermasalah.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['prodi_id', 'nama_kampus', 'fakultas'])) {
                    return;
                }

                $prodi = Prodi::query()
                    ->with('fakultas')
                    ->find($this->input('prodi_id'));

                if (! $prodi?->fakultas) {
                    return;
                }

                $namaKampusProdi = $prodi->fakultas->kampus?->nama_kampus;

                if ($namaKampusProdi !== $this->input('nama_kampus')) {
                    $validator->errors()->add(
                        'prodi_id',
                        'Program studi yang dipilih tidak berada pada kampus yang dipilih.'
                    );

                    return;
                }

                if ($prodi->fakultas->nama !== $this->input('fakultas')) {
                    $validator->errors()->add(
                        'prodi_id',
                        'Program studi yang dipilih tidak berada pada fakultas yang dipilih.'
                    );
                }
            },
        ];
    }

    /**
     * Dokumen: WAJIB PDF, dan wajib diunggah kecuali filenya sudah tersimpan
     * (dokumen yang ada tidak perlu diunggah ulang saat mengubah data lain).
     *
     * Aturan di sini adalah sumber kebenaran format; teks bantuan di bawah tiap
     * input di `profil/index.blade.php` harus menyebut format yang sama, kalau
     * tidak pesan di layar berbohong soal apa yang sebenarnya diterima server.
     *
     * @return array<int, string>
     */
    private function dokumen(string $field): array
    {
        return [
            filled($this->user()?->profile?->{$field}) ? 'nullable' : 'required',
            'file',
            'mimes:pdf',
            'max:2048',
        ];
    }

    /**
     * Pas foto: satu-satunya berkas yang berupa GAMBAR (semua dokumen lain PDF).
     *
     * @return array<int, string>
     */
    private function foto(string $field): array
    {
        return [
            filled($this->user()?->profile?->{$field}) ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png',
            'max:2048',
        ];
    }

    /**
     * Label tampil tiap dokumen PDF, dipakai menyusun pesan format dan ukuran
     * sekaligus supaya tiap field PASTI punya pesannya sendiri. Tanpa ini,
     * berkas non-PDF jatuh ke pesan bawaan `lang/id/validation.php`
     * ("... harus berupa file dengan tipe: pdf.") yang menyebut istilah teknis.
     *
     * Pas foto sengaja tidak ada di sini: ia satu-satunya berkas berupa gambar
     * dan pesannya ditulis terpisah di `messages()`.
     *
     * @var array<string, string>
     */
    private const DOKUMEN_PDF = [
        'dokumen_ktp' => 'KTP',
        'dokumen_kk' => 'Kartu Keluarga',
        'dokumen_desil' => 'Dokumen Desil',
        'dokumen_sktm' => 'SKTM',
        'dokumen_transkrip' => 'Transkrip',
        'dokumen_surat_aktif' => 'Surat Aktif Kuliah',
        'dokumen_surat_pernyataan' => 'Surat Pernyataan',
        'dokumen_bukti_ukt' => 'Bukti UKT',
        'ktp_ayah' => 'KTP Ayah',
        'ktp_ibu' => 'KTP Ibu',
        'ktp_wali' => 'KTP Wali',
        'kk_wali' => 'KK Wali',
    ];

    public function messages(): array
    {
        $pesan = [
            'nama_lengkap.required' => 'Nama lengkap harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Email harus berupa alamat email yang valid.',
            'email.unique' => 'Email ini sudah digunakan user lain.',
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit.',
            'nik.unique' => 'NIK ini sudah terdaftar pada akun lain. Periksa kembali NIK Anda.',
            // Kelima kolom ini memuat nomor identitas orang yang berbeda-beda,
            // jadi satu nomor tidak boleh dipakai dua kali. Pesannya menyebut
            // kolom pasangannya supaya mahasiswa tahu mana yang harus diperiksa.
            'nik.different' => 'NIK tidak boleh sama dengan No. Kartu Keluarga, NIK Ayah, NIK Ibu, atau NIK Wali.',
            'no_kk.different' => 'No. Kartu Keluarga tidak boleh sama dengan NIK, NIK Ayah, NIK Ibu, atau NIK Wali.',
            'nik_ayah.different' => 'NIK Ayah tidak boleh sama dengan NIK, No. Kartu Keluarga, NIK Ibu, atau NIK Wali.',
            'nik_ibu.different' => 'NIK Ibu tidak boleh sama dengan NIK, No. Kartu Keluarga, NIK Ayah, atau NIK Wali.',
            'nik_wali.different' => 'NIK Wali tidak boleh sama dengan NIK, No. Kartu Keluarga, NIK Ayah, atau NIK Ibu.',
            'no_kk.required' => 'Nomor Kartu Keluarga harus diisi.',
            'no_kk.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka.',
            'nim.unique' => 'NIM ini sudah terdaftar pada akun lain. Periksa kembali NIM Anda.',
            'tempat_lahir.required' => 'Tempat lahir harus diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir harus diisi.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'jenis_kelamin.required' => 'Jenis kelamin harus dipilih.',
            'agama.required' => 'Agama harus dipilih.',
            'telepon.required' => 'Telepon harus diisi.',
            'telepon.regex' => 'Nomor HP harus berupa angka, diawali 08, dan panjang 10 sampai 13 digit.',
            'alamat.required' => 'Alamat harus diisi.',
            'kecamatan.required' => 'Kecamatan harus dipilih.',
            'desa_kelurahan.required' => 'Desa/Kelurahan harus dipilih.',
            'nama_kampus.required' => 'Nama kampus harus dipilih.',
            'fakultas.required' => 'Fakultas harus dipilih.',
            'prodi_id.required' => 'Program studi harus dipilih.',
            'prodi_id.exists' => 'Program studi tidak valid.',
            'ipk.required' => 'IPK harus diisi.',
            'ipk.numeric' => 'IPK harus berupa angka.',
            'ipk.between' => 'IPK harus antara 0 dan 4.',
            'semester.required' => 'Semester harus diisi.',
            'semester.integer' => 'Semester harus berupa angka.',
            'semester.between' => 'Semester harus antara 1 dan 14.',
            'ukt.required' => 'UKT harus diisi.',
            'ukt.numeric' => 'UKT harus berupa angka.',
            'desil.required' => 'Desil harus dipilih.',
            'desil.integer' => 'Desil harus berupa angka.',
            'desil.between' => 'Desil harus antara 1 dan 10.',
            'ikut_kk.required' => 'Ikut KK harus dipilih.',
            'nama_ayah.required' => 'Nama ayah harus diisi.',
            'nik_ayah.required' => 'NIK Ayah harus diisi.',
            'nik_ayah.digits' => 'NIK Ayah harus terdiri dari 16 digit angka.',
            'pekerjaan_ayah.required' => 'Pekerjaan ayah harus dipilih.',
            'nama_ibu.required' => 'Nama ibu harus diisi.',
            'nik_ibu.required' => 'NIK Ibu harus diisi.',
            'nik_ibu.digits' => 'NIK Ibu harus terdiri dari 16 digit angka.',
            'pekerjaan_ibu.required' => 'Pekerjaan ibu harus dipilih.',
            'nama_wali.required' => 'Nama wali harus diisi.',
            'nik_wali.required' => 'NIK Wali harus diisi.',
            'nik_wali.digits' => 'NIK Wali harus terdiri dari 16 digit angka.',
            'pekerjaan_wali.required' => 'Pekerjaan wali harus dipilih.',
            'hubungan_wali.required' => 'Hubungan dengan wali harus dipilih.',
            'foto_profil.required' => 'Pas foto 3x4 wajib diunggah.',
            'foto_profil.image' => 'Pas foto harus berupa gambar.',
            'foto_profil.mimes' => 'Format pas foto harus JPG, JPEG, atau PNG.',
            'foto_profil.max' => 'Ukuran pas foto maksimal 2MB.',
            'dokumen_ktp.required' => 'KTP wajib diunggah.',
            'dokumen_kk.required' => 'Kartu Keluarga wajib diunggah.',
            'dokumen_desil.required' => 'Dokumen Desil wajib diunggah.',
            'dokumen_sktm.required' => 'SKTM wajib diunggah.',
            'dokumen_transkrip.required' => 'Transkrip wajib diunggah.',
            'dokumen_surat_aktif.required' => 'Surat Aktif Kuliah wajib diunggah.',
            'dokumen_surat_pernyataan.required' => 'Surat Pernyataan wajib diunggah.',
            'dokumen_bukti_ukt.required' => 'Bukti UKT wajib diunggah.',
            'ktp_ayah.required' => 'KTP Ayah wajib diunggah.',
            'ktp_ibu.required' => 'KTP Ibu wajib diunggah.',
            'ktp_wali.required' => 'KTP Wali wajib diunggah.',
            'kk_wali.required' => 'KK Wali wajib diunggah.',
        ];

        // Semua dokumen wajib PDF (pas foto dikecualikan). Pesannya dibangun
        // dari daftar label supaya tidak mungkin ada dokumen yang lolos tanpa
        // pesan format saat pengguna mengunggah berkas non-PDF.
        foreach (self::DOKUMEN_PDF as $field => $label) {
            $pesan[$field.'.mimes'] = $label.' harus berupa file PDF.';
            $pesan[$field.'.max'] = 'Ukuran '.$label.' maksimal 2MB.';
        }

        $pesan['dokumen_prestasi.*.mimes'] = 'Dokumen prestasi harus berupa file PDF.';
        $pesan['dokumen_prestasi.*.max'] = 'Ukuran dokumen prestasi maksimal 2MB per file.';

        return $pesan;
    }
}
