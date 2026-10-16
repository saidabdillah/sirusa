---
paths:
  - 'app/Http/Requests/Profil/**'
---

# Profil

## NIK dan NIM unik antar mahasiswa; No. KK dan NIK orang tua SENGAJA tidak
`profil_pengguna` punya index unique pada `nik` dan `nim` sejak migrasi awal. Rule di FormRequest WAJIB mencerminkannya — kalau tidak, pelanggarannya baru ketahuan di database dan mahasiswa melihat pesan exception yang memuat SELURUH perintah INSERT beserta NIK, No. KK, nomor HP, dan data pribadi lain. `UpdateProfilRequest` memakai `Rule::unique('profil_pengguna', 'nik'|'nim')->ignore($profilId)`, dengan `$profilId = $this->user()?->profile()->value('id')`.

- Ambil id-nya lewat `profile()` (query segar), BUKAN `profile` (relasi): relasi itu bisa sudah ter-cache sebagai `null` pada request yang sama, sehingga `ignore()` tidak mengecualikan apa pun dan penyimpanan ulang ditolak oleh barisnya sendiri.
- Pesannya `'nik.unique' => 'NIK ini sudah terdaftar pada akun lain. Periksa kembali NIK Anda.'` dan `'nim.unique'` serupa.
- `no_kk`, `nik_ayah`, `nik_ibu`, `nik_wali` SENGAJA tidak unik ANTAR MAHASISWA: dua mahasiswa bersaudara berbagi nomor KK dan NIK orang tua yang sama, jadi mengunci keunikannya akan menolak pendaftaran yang sah.
- NIK ikut dipakai sebagai kredensial masuk (`AuthController::simpanMasuk` mencari `whereHas('profile', nik = $login)`), jadi keunikannya menopang login, bukan sekadar kerapian data.
- Jalur admin (`StoreUserRequest`) sudah lebih dulu punya `Rule::unique('profil_pengguna','nik')`; jaga keduanya tetap sejalan.
- Covered by tests/Feature/ProfilValidasiTest.php.

## Kelima kolom NIK/No. KK tidak boleh saling sama (dalam satu profil)
`nik`, `no_kk`, `nik_ayah`, `nik_ibu`, `nik_wali` memuat nomor identitas orang (dan kartu) yang BERBEDA-BEDA, jadi satu nomor tidak boleh dipakai di dua kolom — itu tanda salah isi, bukan variasi data yang sah. Nomor KK juga bukan NIK, jadi `no_kk` pun tidak boleh sama dengan NIK mana pun.

Sumber tunggalnya konstanta `UpdateProfilRequest::BEDA_DARI_NIK_LAIN` (peta `field => 'different:<semua field lain>'`). Tiap kolom menyebut SEMUA kolom lain, bukan hanya yang di atasnya, supaya pesan galatnya muncul tepat di kolom yang bentrok — bukan cuma di satu kolom. Pesan `'<field>.different'` menyebut kolom pasangannya, mis. "NIK Ayah tidak boleh sama dengan NIK, No. Kartu Keluarga, NIK Ibu, atau NIK Wali.".

Jangan hapus rule `different` dari `nik_wali` saat wali tidak dipakai: Laravel melewati rule non-implicit untuk nilai string kosong (`Arr::has` + perbandingan string), jadi `['nullable', 'different:...']` tidak menghasilkan pesan palsu. Kalau ragu, `validateDifferent()` di `Illuminate/Validation/Concerns/ValidatesAttributes.php` mengembalikan `true` begitu nilainya kosong.

Teks bantuan di `profil/index.blade.php` mengikuti aturan ini: kolom NIK mahasiswa dan No. KK berbunyi "Hanya angka, tepat 16 digit.", sedangkan ketiga NIK orang tua berbunyi "Hanya angka, tepat 16 digit, dan tidak boleh sama dengan NIK lain." (jadi 3 + 2, dihitung tes).

## Pesan exception TIDAK boleh ditampilkan ke pengguna
`ProfileController::update` dibungkus `catch (\Throwable)` yang dulu menempelkan `$e->getMessage()` ke pesan `ajaxFail` — pesan SQL-nya memuat seluruh nilai kolom, jadi itu pembocoran data pribadi. Sekarang detailnya hanya ke `Log::error(...)` (dengan `user_id` + objek exception) dan yang tampil ke pengguna hanya "Profil gagal disimpan karena terjadi kesalahan pada sistem. Silakan coba lagi; bila masih gagal, hubungi admin.". Jangan kembalikan `$e->getMessage()` ke `ajaxFail`/flash di halaman mana pun.

## Nomor HP Indonesia dibatasi 08 + 10–13 digit; NIK/no HP/No. KK pakai Cleave angka
`UpdateProfilRequest` memvalidasi `telepon` dengan `regex:/^08[0-9]{8,11}$/` (diawali `08`, total 10–13 digit) — nomor asing sengaja TIDAK diterima karena kolom ini khusus nomor HP Indonesia (keputusan pengguna). Form `profil/index` memasang Cleave `numericOnly: true` dengan `blocks: [16]` untuk `#nik` DAN `#no_kk`, serta `blocks: [13]` untuk `#telepon` (satu blok `if (typeof Cleave !== 'undefined' && document.getElementById('nik'))`, sebelum blok UKT), sehingga huruf tertahan sejak ketikan pertama dan digit tidak bisa melewati batas. Aturan server tetap penutup jalur tanpa JavaScript — jangan hapus salah satunya. Teks bantuan nomor HP SUDAH DIHAPUS atas permintaan pengguna. Aturan format/ukuran berkas ada di bagian berikutnya. Covered by tests/Feature/ProfilTeleponDanAlertTest.php.

## No. KK wajib 16 digit angka (BUKAN harus sama dengan NIK); dokumen PDF
`no_kk` divalidasi `['required', 'digits:16']` — nomor KK adalah nomor yang BERDIRI SENDIRI dan memang berbeda dari NIK, jadi JANGAN pernah menambahkan `same:nik` (pernah dicoba dan salah tangkap permintaan pengguna). Pesannya `'no_kk.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka.'`. Form memakai Cleave angka `blocks: [16]` untuk `#no_kk` (sebelumnya hanya `#nik`/`#telepon`), dan teks bantuannya "Hanya angka, tepat 16 digit." (sama dengan NIK).

**Format berkas: PDF untuk semua dokumen, GAMBAR hanya untuk pas foto.** `UpdateProfilRequest::dokumen()` sekarang `['file','mimes:pdf','max:2048']` (parameter `$tipe`/`$ekstensi` DIHAPUS — jangan dikembalikan), sedangkan pas foto memakai helper terpisah `foto()` = `['image','mimes:jpg,jpeg,png','max:2048']`. `dokumen_prestasi.*` juga `mimes:pdf`. Form mengikuti: `accept="application/pdf"` untuk semua dokumen + dokumen prestasi, `accept="image/jpeg,image/png"` untuk pas foto, dan teks di bawah input HARUS mencerminkan aturannya — "Format: PDF. Ukuran maksimal 2 MB." untuk dokumen, "Format: JPG, JPEG, atau PNG. Ukuran maksimal 2 MB." untuk pas foto, "... per file." untuk dokumen prestasi. Pesan validasi format/ukuran dibangun otomatis dari konstanta `DOKUMEN_PDF` (label per field) di `messages()`, supaya tidak ada dokumen yang jatuh ke pesan bawaan `lang/id/validation.php` ("... harus berupa file dengan tipe: pdf.") yang menyebut istilah teknis. Teks bantuan nomor HP ("Nomor HP Indonesia: diawali 08...") sudah DIHAPUS atas permintaan pengguna — jangan ditambahkan kembali. Covered by tests/Feature/ProfilTeleponDanAlertTest.php dan ProfilSpesifikasiTest.php.

## Every * field in the profile form is required; files are exempt only when already stored
The `*` in resources/views/profil/index.blade.php is a real contract: a field marked `*` MUST have a `required` rule, an Indonesian `@error` message below the input, and `is-invalid` on the control (radios need it on the input itself). Rules come from `UpdateProfilRequest::rules()`. Two traps: (1) file fields use the private `dokumen()` helper, which returns `nullable` instead of `required` when `$this->user()?->profile?->{$field}` is already filled, so editing a name does not force re-uploading 11 documents; (2) wali fields are gated on `$this->input('ikut_kk') === 'wali'`, NOT `required_if` — `required_if` validates hidden fields and the user cannot see or fix the error. Never add an HTML `required` attribute (asserted in ProfilTest). `nama_kampus`/`fakultas` are validated but not fillable, so they are dropped by `fill()` by design.

## Normalize rupiah input in prepareForValidation, never in the controller
`UpdateProfilRequest::normalisasiRupiah()` strips every non-digit from `ukt` before validation. Doing it in the controller or the view left the raw string reaching `numeric`, which silently stored 0. The `decimal:0` cast on `UserProfile::$casts` is the other half: without it the `decimal(10,2)` column hands Blade `1500000.00` and Cleave then ships `150000000`.
