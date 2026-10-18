{{--
  Bagian KRS & KHS per semester.

  Berkas dan nilainya DUA field terpisah, dan tiap semester punya barisnya
  sendiri (`dokumen_akademik[<jenis>][<semester>][file|nilai]`). Karena
  semesternya jadi kunci baris, unggahan untuk satu semester tidak pernah
  menimpa semester lain.

  Barisnya dirender untuk semua semester sampai jenjang TERPANJANG (8), lalu
  disembunyikan + dinonaktifkan sesuai jenjang yang dipilih. Dua alasannya:
  (1) pilihan semester yang tampil selalu mengikuti jenjang, dan
  (2) input yang nonaktif tidak ikut terkirim, jadi semester di luar jenjang
  tidak pernah sampai ke backend (yang tetap memvalidasi ulang).
--}}
@php
$maksSemester = max(\App\Support\JenjangPendidikan::JUMLAH_SEMESTER);
$jenisList = \App\Models\DokumenAkademik::JENIS_LABELS;
@endphp

<h6 class="mt-3 mb-2"><i class="fas fa-file-alt text-primary mr-1"></i>KRS &amp; KHS per Semester</h6>
{{-- Petunjuk yang teksnya diisi skrip di bawah mengikuti jenjang + semester
     aktif yang dipilih: KRS diminta sampai semester aktif, KHS sampai
     semester SEBELUMNYA (KHS semester berjalan belum terbit). --}}
<p id="petunjuk-akademik" class="text-muted small">
  Pilih jenjang dan semester terlebih dahulu; daftar berkas di bawah menyesuaikan
  otomatis: KRS diunggah sampai semester aktif, KHS sampai semester sebelumnya.
</p>

@foreach($jenisList as $jenis => $labelJenis)
<div class="mb-4">
  <h6 class="text-muted mb-2">{{ $labelJenis }}</h6>

  @for($semester = 1; $semester <= $maksSemester; $semester++)
    @php($tersimpan = ($dokumenAkademik[$jenis] ?? collect())->firstWhere('semester', $semester))
    <div class="form-row baris-akademik" data-jenis="{{ $jenis }}" data-semester="{{ $semester }}">
      <div class="form-group col-md-6">
        <label for="akademik-{{ $jenis }}-{{ $semester }}-file">
          Berkas {{ $labelJenis }} Semester {{ $semester }}
        </label>
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
        <small class="text-muted">
          Format: PDF, maksimal 2 MB per berkas.
          @if($tersimpan?->file)
          &middot; <a href="{{ route('dokumen.show', $tersimpan->file) }}" target="_blank"
            rel="noopener noreferrer">Lihat berkas tersimpan</a>
          @endif
        </small>
      </div>

      <div class="form-group col-md-6">
        <label for="akademik-{{ $jenis }}-{{ $semester }}-nilai">
          Nilai {{ $labelJenis }} Semester {{ $semester }}
        </label>
        <input type="number" step="0.01" min="0" max="4"
          class="form-control @error("dokumen_akademik.{$jenis}.{$semester}.nilai") is-invalid @enderror"
          id="akademik-{{ $jenis }}-{{ $semester }}-nilai"
          name="dokumen_akademik[{{ $jenis }}][{{ $semester }}][nilai]"
          value="{{ old("dokumen_akademik.{$jenis}.{$semester}.nilai", $tersimpan?->nilai) }}"
          placeholder="contoh 3.50">
        @error("dokumen_akademik.{$jenis}.{$semester}.nilai")
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Skala 0 sampai 4, sama seperti IPK.</small>
      </div>
    </div>
  @endfor
</div>
@endforeach