<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cetak Penerima Beasiswa &mdash; SIRUSA</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: "Segoe UI", Arial, sans-serif; color: #191919; padding: 24px 32px; font-size: 13px; }
    .toolbar { margin-bottom: 16px; }
    .toolbar .btn { display: inline-block; padding: 6px 14px; border: 1px solid #6777ef; color: #6777ef; border-radius: 4px; text-decoration: none; margin-right: 8px; cursor: pointer; background: #fff; font-size: 13px; }
    .kop { display: flex; align-items: center; gap: 18px; padding-bottom: 10px; margin-bottom: 24px; border-bottom: 3px solid #191919; position: relative; }
    .kop::after { content: ''; position: absolute; left: 0; right: 0; bottom: -7px; border-bottom: 1px solid #191919; }
    .kop .logo { width: 76px; height: 76px; object-fit: contain; flex-shrink: 0; }
    .kop .teks { flex: 1; text-align: center; }
    .kop .instansi { font-weight: 700; font-size: 17px; letter-spacing: 1px; }
    .kop .badan { font-size: 14px; font-weight: 600; }
    .kop .alamat { font-size: 11px; margin-top: 4px; }
    h1 { font-size: 18px; text-align: center; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #444; padding: 6px 8px; vertical-align: middle; }
    th { background: #eef1f8; text-align: left; }
    .nomor { width: 40px; text-align: center; }
    .ttd { margin-top: 40px; display: flex; justify-content: space-between; }
    .ttd .blok { width: 260px; text-align: center; }
    .ttd .ruang { height: 60px; }
    @media print {
      .toolbar { display: none; }
      body { padding: 0; font-size: 12px; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" class="btn" onclick="window.print()"><i>&#128424;</i> Cetak Sekarang</button>
    <a class="btn" href="{{ route('admin.penerima.index') }}">Kembali</a>
  </div>

  <div class="kop">
    <img class="logo" src="{{ asset('images/logo-balangan.png') }}" alt="Logo Kabupaten Balangan">
    <div class="teks">
      <div class="instansi">PEMERINTAH KABUPATEN BALANGAN</div>
      <div class="badan">SEKRETARIS DAERAH</div>
      <div class="badan">BAGIAN KESEJAHTERAAN SOSIAL</div>
      <div class="alamat">Jl. Jenderal Ahmad Yani Km. 4,5 Telepon 0526-2028360 Paringin 71462</div>
    </div>
  </div>

  <h1>Daftar Penerima Beasiswa</h1>

  <table>
    <thead>
      <tr>
        <th class="nomor">No</th>
        <th>Nama</th>
        <th>NIK</th>
        <th>NIM</th>
        <th>Beasiswa</th>
        <th>Program Studi</th>
        <th>Kampus</th>
        <th>Tanggal Daftar</th>
      </tr>
    </thead>
    <tbody>
      @forelse($penerima as $applicant)
        @php $profile = $applicant->user?->profile; @endphp
        <tr>
          <td class="nomor">{{ $loop->iteration }}</td>
          <td>{{ $profile?->nama_lengkap ?? '-' }}</td>
          <td>{{ $profile?->nik ?? '-' }}</td>
          <td>{{ $profile?->nim ?? '-' }}</td>
          <td>{{ $applicant->beasiswa?->nama ?? '-' }}</td>
          <td>{{ $profile?->prodi?->nama ?? '-' }}</td>
          <td>{{ $profile?->prodi?->fakultas?->kampus?->nama_kampus ?? '-' }}</td>
          <td>{{ $applicant->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center; color:#777;">Belum ada penerima beasiswa yang disetujui.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="ttd">
    <div class="blok">
      <div>Mengetahui,</div>
      <div class="ruang"></div>
      <div>(....................................)</div>
    </div>
    <div class="blok">
      <div>Paringin Selatan, {{ now()->translatedFormat('d F Y') }}</div>
      <div class="ruang"></div>
      <div>(....................................)</div>
    </div>
  </div>

  <script>
    window.addEventListener('load', function () {
      window.print();
    });
  </script>
</body>
</html>