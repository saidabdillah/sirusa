<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Surat Permohonan Beasiswa</title>
  <style>
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 12pt;
      line-height: 1.6;
      margin: 40px;
      color: #000;
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
      border-bottom: 3px double #000;
      padding-bottom: 15px;
    }
    .header h1 {
      font-size: 14pt;
      margin: 0;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .content {
      margin-top: 20px;
    }
    .content p {
      text-align: justify;
      margin-bottom: 15px;
    }
    .signature {
      margin-top: 50px;
      text-align: right;
      padding-right: 50px;
    }
    .signature .name {
      margin-top: 5px;
      font-weight: bold;
      text-decoration: underline;
    }
    .print-btn {
      display: block;
      margin: 20px auto;
      padding: 10px 30px;
      background: #4a90d9;
      color: #fff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14pt;
    }
    .print-btn:hover {
      background: #357abd;
    }
    @media print {
      .print-btn { display: none; }
      body { margin: 20mm; }
    }
  </style>
</head>
<body>
  <button class="print-btn" onclick="window.print()">Cetak Surat</button>

  <div class="header">
    <h1>Surat Permohonan Beasiswa</h1>
  </div>

  <div class="content">
    <table style="width: 100%; margin-bottom: 20px;">
      <tr>
        <td style="width: 120px;">Kepada Yth.</td>
        <td>:</td>
      </tr>
      <tr>
        <td></td>
        <td><strong>Kepala Dinas Pendidikan<br>Kabupaten Balangan<br>di Tempat</strong></td>
      </tr>
    </table>

    <p style="text-indent: 40px;">
      Dengan hormat,
    </p>

    <p style="text-indent: 40px;">
      Yang bertanda tangan di bawah ini:
    </p>

    <table style="width: 100%; margin: 10px 0 20px 40px;">
      <tr>
        <td style="width: 150px;">Nama</td>
        <td>: {{ auth()->user()->name ?? '................................' }}</td>
      </tr>
      <tr>
        <td>NIK</td>
        <td>: {{ $profile->nik ?? '................................' }}</td>
      </tr>
      <tr>
        <td>Tempat/Tgl Lahir</td>
        <td>: {{ ($profile->tempat_lahir ?? '.................') }}, {{ \Carbon\Carbon::parse($profile->tanggal_lahir ?? now())->translatedFormat('d F Y') }}</td>
      </tr>
      <tr>
        <td>Alamat</td>
        <td>: {{ $profile->alamat ?? '................................' }}</td>
      </tr>
    </table>

    <p style="text-indent: 40px;">
      Dengan ini bermaksud mengajukan permohonan beasiswa pendidikan di Kabupaten Balangan, dengan data sebagai berikut:
    </p>

    <p style="text-indent: 40px;">
      Saya bermaksud memohon bantuan beasiswa pendidikan untuk melanjutkan studi di perguruan tinggi. Beasiswa yang saya ajukan ini sangat membantu meringankan beban biaya pendidikan saya dan keluarga.
    </p>

    <p style="text-indent: 40px;">
      Demikian surat permohonan ini saya buat dengan sebenar-benarnya. Besar harapan saya agar permohonan ini dapat dipertimbangkan dan disetujui oleh Bapak/Ibu. Atas perhatian dan kebijaksanaannya, saya ucapkan terima kasih.
    </p>

    <p style="text-indent: 40px;">
      &nbsp;
    </p>

    <div class="signature">
      <p style="margin-bottom: 0;">
        Balangan, {{ now()->translatedFormat('d F Y') }}
      </p>
      <p style="margin-top: 0;">Yang mengajukan,</p>
      <div style="margin-top: 60px;">
        <div class="name">{{ auth()->user()->name ?? '................................' }}</div>
      </div>
    </div>
  </div>
</body>
</html>
