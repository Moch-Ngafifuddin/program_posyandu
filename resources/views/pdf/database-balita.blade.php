<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Database Pelayanan Balita</title>
    <style>
        /* Pengaturan Dasar Halaman Cetak */
        @page {
            margin: 1.2cm 1cm 1cm 1cm;
        }
        
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px; /* Ukuran font kompak agar 12 kolom muat sempurna */
            color: #222;
            line-height: 1.3;
        }

        /* Kop Surat / Header Laporan Resmi */
        .kop-surat {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
        }
        .kop-surat h2 {
            margin: 0;
            padding: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111;
        }
        .kop-surat h3 {
            margin: 2px 0 0 0;
            padding: 0;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: normal;
            color: #444;
        }
        .kop-surat .meta-data {
            margin-top: 5px;
            font-size: 9px;
            color: #666;
            font-style: italic;
        }
        .line-separator {
            border-bottom: 2px solid #000;
            margin-top: 8px;
            margin-bottom: 15px;
        }

        /* Desain Tabel e-PPGBM Kemenkes */
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            border: 0.5px solid #666;
            padding: 5px 3px;
            text-align: center;
            color: #111;
        }
        td {
            border: 0.5px solid #888;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }
        
        /* Utility Classes Posisi Teks */
        .text-left {
            text-align: left;
            padding-left: 4px;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5px;
        }
        
        /* Zebra Striping Baris Tabel */
        tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Footer Halaman Cetak */
        .footer-document {
            margin-top: 20px;
            width: 100%;
            font-size: 8px;
            color: #777;
        }
        .footer-left {
            float: left;
            width: 50%;
        }
        .footer-right {
            float: right;
            width: 50%;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="kop-surat">
        <h2>Daftar Kondisi Terakhir Perkembangan Balita Aktif</h2>
        <h3>Sistem Pencatatan Rekam Medis Digital Layanan Posyandu</h3>
        <div class="meta-data">
            Dicetak Oleh Petugas pada: {{ $tgl_cetak }} WIB | Wilayah Kerja: {{ auth()->user()?->nama_posyandu ?? '-' }}
        </div>
    </div>
    
    <div class="line-separator"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 11%;">NIK</th>
                <th style="width: 15%;">Nama Balita</th>
                <th style="width: 3%;">JK</th>
                <th style="width: 8%;">Tgl Lahir</th>
                <th style="width: 12%;">Nama Ortu (Ibu)</th>
                <th style="width: 8%;">Prov</th>
                <th style="width: 9%;">Kab/Kota</th>
                <th style="width: 8%;">Kec</th>
                <th style="width: 9%;">Puskesmas</th>
                <th style="width: 8%;">Desa/Kel</th>
                <th style="width: 8%;">Posyandu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row->pasien?->nik ?? '-' }}</td>
                    <td class="text-left" style="font-weight: bold;">{{ $row->pasien?->nama ?? '-' }}</td>
                    <td>{{ $row->pasien?->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $row->pasien?->tgl_lahir ? \Carbon\\Carbon::parse($row->pasien->tgl_lahir)->format('d-m-Y') : '-' }}</td>
                    <td class="text-left row-nama-ibu">{{ $row->pasien?->nama_ibu ?? '-' }}</td>
                    
                    <td>{{ $row->pasien?->posyandu?->provinsi ?? '-' }}</td>
                    <td>{{ $row->pasien?->posyandu?->kabupaten_kota ?? '-' }}</td>
                    <td>{{ $row->pasien?->posyandu?->kecamatan ?? '-' }}</td>
                    <td>{{ $row->pasien?->posyandu?->nama_puskesmas ?? '-' }}</td>
                    <td>{{ $row->pasien?->posyandu?->desa_kelurahan ?? '-' }}</td>
                    <td>{{ $row->pasien?->posyandu?->nama_posyandu ?? '-' }}</td>
                </tr>
            @empty
               @endforelse
        </tbody>
    </table>

    <div class="footer-document">
    </div>

</body>
</html>