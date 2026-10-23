{{--
  Bagian KRS & KHS per semester.

  KRS dan KHS DUA dokumen berbeda, dan tiap semester punya barisnya sendiri
  (`dokumen_akademik[<jenis>][<semester>][file|nilai]`). Karena semesternya
  jadi kunci baris, unggahan untuk satu semester tidak pernah menimpa semester
  lain.

  KRS hanya BERKAS: KRS adalah rencana studi, tidak memuat nilai, jadi baris
  KRS tidak punya input nilai sama sekali (permintaan pengguna). Nilai hanya
  dimiliki KHS (hasil studi yang sudah keluar).

  TAMPILAN: satu BARIS TABEL per semester (permintaan pengguna), bukan satu
  blok dua kolom per semester. Semester 8 dulu menghasilkan 16 blok dan
  halaman jadi sangat panjang; dengan tabel hanya ada 8 baris, dan berkas
  yang sudah tersimpan tampil sebagai tautan "Lihat" supaya tinggi barisnya
  tidak ikut nama berkas yang panjang.

  Barisnya dirender untuk semua semester sampai jenjang TERPANJANG (8), lalu
  disembunyikan + dinonaktifkan sesuai jenjang/semester yang dipilih.
--}}
@php
$maksSemester = max(\App\Support\JenjangPendidikan::JUMLAH_SEMESTER);
$jenisList = \App\Models\DokumenAkademik::JENIS_LABELS;
$tersimpanPer = $dokumenAkademik ?? collect();
@endphp

<h6 class="mt-3 mb-2"><i class="fas fa-file-alt text-primary mr-1"></i>KRS &amp; KHS per Semester</h6>
{{-- Petunjuk yang teksnya diisi skrip di bawah mengikuti jenjang + semester
     aktif yang dipilih: KRS diminta sampai semester aktif, KHS sampai
     semester SEBELUMNYA (KHS semester berjalan belum terbit). --}}
<p id="petunjuk-akademik" class="text-muted small">
  Pilih jenjang dan semester terlebih dahulu; daftar berkas di bawah menyesuaikan
  otomatis: KRS diunggah sampai semester aktif, KHS sampai semester sebelumnya.
</p>

<div class="table-responsive">
  <table class="table table-sm table-bordered align-middle" id="tabel-akademik">
    <thead class="thead-light">
      <tr>
        <th style="width: 90px;">Semester</th>
        <th>Berkas KRS</th>
        <th>Berkas KHS</th>
        <th style="width: 150px;">Nilai KHS</th>
      </tr>
    </thead>
    <tbody>
      @for($semester = 1; $semester <= $maksSemester; $semester++)
        <tr class="baris-akademik" data-semester="{{ $semester }}">
          <td class="font-weight-bold">Semester {{ $semester }}</td>

          @foreach($jenisList as $jenis => $labelJenis)
            @php($tersimpan = ($tersimpanPer[$jenis] ?? collect())->firstWhere('semester', $semester))
            <td>
              <div class="sel-berkas">
                <div class="custom-file custom-file-sm">
                  <input type="file"
                    class="custom-file-input @error("dokumen_akademik.{$jenis}.{$semester}.file") is-invalid @enderror"
                    id="akademik-{{ $jenis }}-{{ $semester }}-file"
                    name="dokumen_akademik[{{ $jenis }}][{{ $semester }}][file]" accept="application/pdf">
                  <label class="custom-file-label" for="akademik-{{ $jenis }}-{{ $semester }}-file">
                    {{ $tersimpan?->file ? basename($tersimpan->file) : 'Pilih file' }}
                  </label>
                </div>
                @if($tersimpan?->file)
                <a href="{{ route('dokumen.show', $tersimpan->file) }}" target="_blank" rel="noopener noreferrer"
                  class="btn btn-sm btn-outline-primary flex-shrink-0" title="Lihat berkas tersimpan">
                  <i class="fas fa-eye"></i>
                </a>
                @endif
              </div>
              @error("dokumen_akademik.{$jenis}.{$semester}.file")
              <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </td>
          @endforeach

          {{-- Nilai hanya milik KHS. KRS adalah rencana studi dan tidak
               memuat nilai, jadi kolomnya kosong untuk baris KRS. --}}
          <td>
            @php($khsTersimpan = ($tersimpanPer[\App\Models\DokumenAkademik::JENIS_KHS] ?? collect())->firstWhere('semester', $semester))
            <input type="number" step="0.01" min="0" max="4"
              class="form-control form-control-sm nilai-akademik @error("dokumen_akademik.khs.{$semester}.nilai") is-invalid @enderror"
              id="akademik-khs-{{ $semester }}-nilai"
              name="dokumen_akademik[khs][{{ $semester }}][nilai]"
              value="{{ old("dokumen_akademik.khs.{$semester}.nilai", $khsTersimpan?->nilai) }}"
              placeholder="contoh 3.50">
            @error("dokumen_akademik.khs.{$semester}.nilai")
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </td>
        </tr>
      @endfor
    </tbody>
  </table>
</div>
<small class="text-muted">Format: PDF, maksimal 2 MB per berkas. Nilai berskala 0 sampai 4, sama seperti IPK.</small>
