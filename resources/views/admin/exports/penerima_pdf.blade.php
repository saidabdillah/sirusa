<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Daftar Penerima Beasiswa</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 13px;
      color: #333;
      padding: 30px;
    }

    .kop-table {
      width: 100%;
      border-collapse: collapse;
    }

    .kop-logo {
      width: 95px;
      vertical-align: middle;
      text-align: center;
    }

    .kop-logo img {
      width: 85px;
    }

    .kop-institusi {
      text-align: center;
      vertical-align: middle;
    }

    .kop-institusi .pemda {
      font-size: 24px;
      font-weight: bold;
      color: #000;
    }

    .kop-institusi .dinas {
      font-size: 30px;
      font-weight: bold;
      color: #000;
    }

    .kop-institusi .alamat {
      font-size: 12px;
      color: #555;
      line-height: 1.4;
    }

    .kop-garis {
      border-bottom: 3px solid #000;
      margin-top: 10px;
    }

    .kop-garis-tipis {
      border-bottom: 1px solid #000;
      margin-top: 1px;
    }

    .judul {
      text-align: center;
      margin: 25px 0 15px;
    }

    .judul h1 {
      font-size: 20px;
      color: #000;
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .judul h2 {
      font-size: 18px;
      color: #000;
      text-transform: uppercase;
    }

    table.data {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    table.data th,
    table.data td {
      border: 1px solid #ccc;
      padding: 5px 6px;
      text-align: left;
      font-size: 12px;
    }

    table.data th {
      background-color: #000;
      color: white;
    }

    table.data tr:nth-child(even) {
      background-color: #f8f9fa;
    }
  </style>
</head>

<body>
  @php
  $logoPath = public_path('images/logo-balangan.png');
  $logoSrc = is_file($logoPath)
  ? 'data:image/png;base64,'.base64_encode(\Illuminate\Support\Facades\File::get($logoPath))
  : null;
  @endphp

  <div class="kop">
    <table class="kop-table">
      <tr>
        <td class="kop-logo">
          @if($logoSrc)
          <img src="{{ $logoSrc }}" alt="Logo Kabupaten Balangan">
          @endif
        </td>
        <td class="kop-institusi">
          <div class="pemda">PEMERINTAH KABUPATEN BALANGAN</div>
          <div class="dinas">SEKRETARIAT DAERAH</div>
          <div class="alamat">Jl. Jenderal Ahmad Yani No. 1, Batu Piring, Kec. Paringin Selatan
          </div>
          <div class="alamat">Kabupaten Balangan, Kalimantan Selatan 71662</div>
        </td>
      </tr>
    </table>
    <div class="kop-garis"></div>
    <div class="kop-garis-tipis"></div>
  </div>

  <div class="judul">
    <h1>Daftar Penerima Beasiswa</h1>
    <h2>{{ strtoupper($scholarship->nama) }}</h2>
  </div>

  <table class="data">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama Lengkap</th>
        <th>NIM</th>
        <th>Jenis Kelamin</th>
        <th>Kampus</th>
        <th>Fakultas</th>
        <th>Program Studi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($penerima as $index => $applicant)
      <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ $applicant->user?->profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
        <td>{{ $applicant->user?->profile?->nim ?? '-' }}</td>
        <td>{{ $applicant->user?->profile?->jenis_kelamin ?? '-' }}</td>
        <td>{{ $applicant->user?->profile?->prodi?->fakultas?->kampus?->nama_kampus ?? $scholarship->kampus ?? '-' }}
        </td>
        <td>{{ $applicant->user?->profile?->prodi?->fakultas?->nama ?? $applicant->fakultas ?? '-' }}</td>
        <td>{{ $applicant->user?->profile?->prodi?->nama ?? $applicant->prodi ?? '-' }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align: center">Tidak ada penerima.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</body>

</html>