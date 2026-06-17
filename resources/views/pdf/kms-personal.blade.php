<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>KMS & Grafik Tumbuh Kembang Personal</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333333; margin: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; }
        .header h2 { margin: 0; font-size: 16px; color: #1e3a8a; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 11px; color: #64748b; }
        
        .tabel-identitas { width: 100%; margin-bottom: 15px; border: none; }
        .tabel-identitas td { border: none; padding: 4px; text-align: left; font-size: 11px; }
        
        .box-ringkasan { margin-bottom: 25px; border: 2px solid #1e3a8a; padding: 12px; background-color: #f8fafc; border-radius: 8px; }
        .box-ringkasan h3 { margin: 0 0 10px 0; color: #1e3a8a; font-size: 12px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; text-transform: uppercase; }
        
        .table-kondisi-terakhir { width: 100%; margin-bottom: 0; border: none; }
        .table-kondisi-terakhir td { border: none; text-align: left; padding: 4px 0; font-size: 11px; }
        .badge-status { font-weight: bold; color: #1e3a8a; background-color: #dbeafe; padding: 2px 6px; border-radius: 4px; }
        
        .section-grafik { margin-top: 20px; }
        .grafik-container { text-align: center; margin-bottom: 30px; page-break-inside: avoid; }
        .grafik-title { font-size: 12px; margin-bottom: 8px; color: #1e3a8a; font-weight: bold; text-align: left; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Kartu Menuju Sehat (KMS) & Rekam Medis Personal</h2>
        <p>Sistem Informasi Pencatatan Digital Pelayanan Posyandu</p>
    </div>

    <table class="tabel-identitas">
        <tr>
            <td style="width: 18%; font-weight: bold;">Nama Balita</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%; font-weight: bold; color: #1e3a8a;">{{ $pasien->nama }}</td>
            
            <td style="width: 18%; font-weight: bold;">Nama Ibu Ortu</td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ $pasien->nama_ibu ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">NIK Balita</td>
            <td>:</td>
            <td>{{ $pasien->nik }}</td>
            
            <td style="font-weight: bold;">Desa/Kelurahan</td>
            <td>:</td>
            <td>{{ $pasien->desa_kelurahan ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jenis Kelamin</td>
            <td>:</td>
            <td>{{ $pasien->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            
            <td style="font-weight: bold;">Wilayah Kerja</td>
            <td>:</td>
            <td>{{ $pasien->nama_posyandu ?? 'Puskesmas Tambak Sari Kidul' }}</td>
        </tr>
    </table>

    @if($pemeriksaan->count() > 0)
        @php
            $terakhir = $pemeriksaan->last();
        @endphp
        <div class="box-ringkasan">
            <h3>Hasil Pengukuran & Kondisi Kesehatan Terakhir</h3>
            <table class="table-kondisi-terakhir">
                <tr>
                    <td style="width: 20%; font-weight: bold;">Tanggal Periksa</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 28%;">{{ \Carbon\Carbon::parse($terakhir->tgl_periksa)->format('d-m-Y') }}</td>
                    
                    <td style="width: 22%; font-weight: bold;">Kategori Gizi (BB/U)</td>
                    <td style="width: 2%;">:</td>
                    <td><span class="badge-status" style="color: #1e40af; background-color: #e0f2fe;">{{ $terakhir->status_gizi ?? '-' }}</span></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Usia Anak</td>
                    <td>:</td>
                    <td>{{ $terakhir->usia_bulan }} Bulan</td>
                    
                    <td style="font-weight: bold;">Kategori Stunting (TB/U)</td>
                    <td>:</td>
                    <td><span class="badge-status" style="color: #065f46; background-color: #d1fae5;">{{ $terakhir->status_stunting ?? '-' }}</span></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Berat Badan</td>
                    <td>:</td>
                    <td><strong>{{ $terakhir->berat_badan }} Kg</strong></td>
                    
                    <td style="font-weight: bold;">Nilai Z-Score (BB/U)</td>
                    <td>:</td>
                    <td><strong>{{ $terakhir->zscore_bbu ?? '-' }} SD</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tinggi Badan</td>
                    <td>:</td>
                    <td><strong>{{ $terakhir->tinggi_badan }} Cm</strong></td>
                    
                    <td style="font-weight: bold;">Petugas Pemeriksa</td>
                    <td>:</td>
                    <td>Kader Posyandu</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="section-grafik">
        <div class="grafik-container">
            <div class="grafik-title">KMS Digital: Kurva Standar Perkembangan Berat Badan menurut Umur (BB/U)</div>

            @php

                $width = 540;
                $height = 240;
                $padding = 40;
                $chartWidth = $width - ($padding * 2);
                $chartHeight = $height - ($padding * 2);

                $totalBulan = $masterData->count();
                
                $allValues = array_merge(
                    $masterData->pluck('plus_1_sd')->toArray(),
                    $masterData->pluck('minus_3_sd')->toArray(),
                    $pemeriksaan->pluck('berat_badan')->toArray()
                );
                $yMax = ceil(max($allValues) + 1);
                $yMin = 0;
                $yRange = $yMax - $yMin;
            @endphp

            <svg width="{{ $width }}" height="{{ $height }}" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;">
                 @for($i = 0; $i <= 4; $i++)
                    @php
                        $gridY = $padding + ($chartHeight * (4 - $i) / 4);
                        $valY = $yMin + ($yRange * $i / 4);
                    @endphp
                    <line x1="{{ $padding }}" y1="{{ $gridY }}" x2="{{ $width - $padding }}" y2="{{ $gridY }}" stroke="#f1f5f9" stroke-width="1" />
                    <text x="{{ $padding - 8 }}" y="{{ $gridY + 3 }}" font-size="8.5" fill="#64748b" text-anchor="end">{{ number_format($valY, 0) }} kg</text>
                @endfor

                @php
                    $lines = [
                        'plus_1_sd'  => ['#34d399', '2', '4,4'], // Batas Atas (Hijau Putus-putus)
                        'median'     => [$pasien->jenis_kelamin === 'L' ? '#60a5fa' : '#f472b6', '2', '0'], // Garis Ideal (Biru/Pink)
                        'minus_2_sd' => ['#f59e0b', '2', '0'], // Batas Kurang (Kuning)
                        'minus_3_sd' => ['#ef4444', '2', '0']  // Gizi Buruk/BGM (Merah)
                    ];
                @endphp

                @foreach($lines as $kolom => $prop)
                    @php
                        $pointsMaster = [];
                        foreach($masterData as $index => $master) {
                            $x = $padding + ($chartWidth * $index / ($totalBulan - 1));
                            $y = $height - $padding - (((float)$master->{$kolom} - $yMin) / $yRange * $chartHeight);
                            $pointsMaster[] = "$x,$y";
                        }
                    @endphp
                    <polyline points="{{ implode(' ', $pointsMaster) }}" fill="none" stroke="{{ $prop[0] }}" stroke-width="{{ $prop[1] }}" stroke-dasharray="{{ $prop[2] }}" />
                @endforeach

                @if($pemeriksaan->count() > 0)
                    @php
                        $pointsAnak = [];
                        foreach($pemeriksaan as $item) {
                            // Hitung posisi X berdasarkan proporsi umur_bulan (skala 0 sampai 24 bulan)
                            $x = $padding + ($chartWidth * $item->usia_bulan / 24);
                            $y = $height - $padding - (((float)$item->berat_badan - $yMin) / $yRange * $chartHeight);
                            $pointsAnak[] = "$x,$y";
                        }
                    @endphp

                    @if($pemeriksaan->count() > 1)
                        <polyline points="{{ implode(' ', $pointsAnak) }}" fill="none" stroke="#000000" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    @endif

                    @foreach($pemeriksaan as $item)
                        @php
                            $x = $padding + ($chartWidth * $item->usia_bulan / 24);
                            $y = $height - $padding - (((float)$item->berat_badan - $yMin) / $yRange * $chartHeight);
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" fill="#000000" stroke="#ffffff" stroke-width="1" />
                        <text x="{{ $x }}" y="{{ $y - 8 }}" font-size="8" font-weight="bold" fill="#000000" text-anchor="middle">{{ number_format($item->berat_badan, 1) }}</text>
                    @endforeach
                @endif

                <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $height - $padding }}" stroke="#475569" stroke-width="1.5" />
                <line x1="{{ $padding }}" y1="{{ $height - $padding }}" x2="{{ $width - $padding }}" y2="{{ $height - $padding }}" stroke="#475569" stroke-width="1.5" />

                @foreach([0, 4, 8, 12, 16, 20, 24] as $bln)
                    @php
                        $xLabel = $padding + ($chartWidth * $bln / 24);
                    @endphp
                    <line x1="{{ $xLabel }}" y1="{{ $height - $padding }}" x2="{{ $xLabel }}" y2="{{ $height - $padding + 4 }}" stroke="#475569" stroke-width="1" />
                    <text x="{{ $xLabel }}" y="{{ $height - $padding + 13 }}" font-size="8" fill="#475569" text-anchor="middle">Bln {{ $bln }}</text>
                @endforeach
            </svg>
        </div>
    </div>

</body>
</html>