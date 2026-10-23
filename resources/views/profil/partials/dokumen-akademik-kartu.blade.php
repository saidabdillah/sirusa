{{--
  VERSI PEMBANDING: KRS & KHS per semester sebagai daftar kartu bertumpuk
  (dua kolom per semester). Isi dan aturannya SAMA PERSIS dengan
  `dokumen-akademik-tabel.blade.php`; yang berbeda hanya bentuk tampilannya.
  Ini hanya untuk membandingkan di browser -- pilih satu, lalu file yang
  tidak dipakai dihapus.
--}}
@php
$maksSemester = max(\App\Support\JenjangPendidikan::JUMLAH_SEMESTER);
$jenisList = \App\Models\DokumenAkademik::JENIS_LABELS;
$tersimpanPer = $dokumenAkademik ?? collect();
@endphp

<h6 class="mt-3 mb-2"><i class="fas fa-file-alt text-primary mr-1"></i>KRS &amp; KHS per Semester</h6>
<p id="petunjuk-akademik" class="text-muted small">
  Pilih jenjang dan semester terlebih dahulu; daftar berkas di bawah menyesuaikan
  otomatis: KRS diunggah sampai semester aktif, KHS sampai semester sebelumnya.
</p>

@for($semester = 1; $semester <= $maksSemester; $semester++)
  <div class="baris-akademik border rounded p-3 mb-3" data-semester="{{ $semester }}">
    <h6 class="mb-3">Semester {{ $semester }}</h6>
    <div class="form-row">
      @foreach($jenisList as $jenis => $labelJenis)
        @php($tersimpan = ($tersimpanPer[$jenis] ?? collect())->firstWhere('semester', $semester))
        <div class="form-group col-md-6">
          <label for="akademik-{{ $jenis }}-{{ $semester }}-file">Berkas {{ $labelJenis }}</label>
          <div class="custom-file">
            <input type="file"
              class="custom-file-input @error("dokumen_akademik.{$jenis}.{$semester}.file") is-invalid @enderror"
              id="akademik-{{ $jenis }}-{{ $semester }}-file"
              name="dokumen_akademik[{{ $jenis }}][{{ $semester }}][file]" accept="application/pdf">
            <label class="custom-file-label" for="akademik-{{ $jenis }}-{{ $semester }}-file">
              {{ $tersimpan?->file ? basename($tersimpan->file) : 'Pilih file PDF' }}
            </label>
          </div>
          @error("dokumen_akademik.{$jenis}.{$semester}.file")
          <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
          @if($tersimpan?->file)
          <small class="text-muted">
            <a href="{{ route('dokumen.show', $tersimpan->file) }}" target="_blank"
              rel="noopener noreferrer">Lihat berkas tersimpan</a>
          </small>
          @endif
        </div>
      @endforeach
      <div class="form-group col-md-6">
        <label for="akademik-khs-{{ $semester }}-nilai">Nilai KHS</label>
        <input type="number" step="0.01" min="0" max="4"
          class="form-control @error("dokumen_akademik.khs.{$semester}.nilai") is-invalid @enderror"
          id="akademik-khs-{{ $semester }}-nilai"
          name="dokumen_akademik[khs][{{ $semester }}][nilai]"
          value="{{ old("dokumen_akademik.khs.{$semester}.nilai", ($tersimpanPer[\App\Models\DokumenAkademik::JENIS_KHS] ?? collect())->firstWhere('semester', $semester)?->nilai) }}"
          placeholder="contoh 3.50">
        @error("dokumen_akademik.khs.{$semester}.nilai")
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </div>
  </div>
@endfor
<small class="text-muted">Format: PDF, maksimal 2 MB per berkas. Nilai berskala 0 sampai 4, sama seperti IPK.</small>
