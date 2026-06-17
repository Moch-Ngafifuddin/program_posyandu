<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Tumbuh Kembang Balita</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.4; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #28b54f; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; color: #28b54f; margin: 0; }
        .subtitle { font-size: 11px; color: #666; margin: 5px 0 0 0; }
        .table-info { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .table-info td { padding: 4px 0; vertical-align: top; }
        .section-title { font-size: 12px; font-weight: bold; background: #e8f5e9; color: #1b5e20; padding: 6px; margin-top: 15px; border-left: 4px solid #28b54f; }
        .table-data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table-data th, .table-data td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .table-data th { background-color: #f5f5f5; font-weight: bold; font-size: 11px; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; color: white; }
        .badge-success { background-color: #2e7d32; }
        .badge-danger { background-color: #c62828; }
        
        /* CSS KHUSUS SIMULASI GRAFIK KMS DI DALAM PDF */
        .kms-chart { width: 100%; margin-top: 10px; border: 1px solid #ccc; padding: 10px; background: #fafafa; }
        .kms-row { display: table; width: 100%; margin-bottom: 4px; }
        .kms-cell-age { display: table-cell; width: 15%; font-weight: bold; font-size: 10px; }
        .kms-cell-bar { display: table-cell; width: 65%; vertical-align: middle; }
        .kms-bar-fill { background: #81c784; height: 12px; border-radius: 3px; font-size: 9px; color: #fff; text-align: right; padding-right: 5px; line-height: 12px; }
        .kms-cell-target { display: table-cell; width: 20%; text-align: right; font-size: 10px; color: #666; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">POSYANDU TAMBAK SARI KIDUL</div>
        <div class="subtitle">Laporan Resmi Hasil Pemeriksaan & Kartu Menuju Sehat (KMS) Elektronik Balita</div>
    </div>

    <table class="table-info">
        <tr>
            <td style="width: 18%;"><strong>Nama Anak</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ $pasien->nama }}</td>
            <td style="width: 18%;"><strong>Tanggal Periksa</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 30%;">{{ \Carbon\Carbon::parse($pemeriksaanUtama->tgl_periksa)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td><strong>Jenis Kelamin</strong></td>
            <td>:</td>
            <td>{{ $pasien->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td><strong>Usia Balita</strong></td>
            <td>:</td>
            <td>{{ $pemeriksaanUtama->keterangan_umur }}</td>
        </tr>
    </table>

    <div class="section-title">I. Hasil Penimbangan & Pengukuran Bulan Ini</div>
    <table class="table-data">
        <thead>
            <tr>
                <th>Berat Badan</th>
                <th>Tinggi Badan</th>
                <th>Lingkar Kepala</th>
                <th>LiLA</th>
                <th>Status KMS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $pemeriksaanUtama->berat_badan ?? '-' }} Kg</strong></td>
                <td>{{ $pemeriksaanUtama->tinggi_badan ?? '-' }} Cm</td>
                <td>{{ $pemeriksaanUtama->lingkar_kepala ?? '-' }} Cm</td>
                <td>{{ $pemeriksaanUtama->lila ?? '-' }} Cm</td>
                <td>
                    @if($pemeriksaanUtama->kenaikan_bb === 'naik')
                        <span class="badge badge-success">N (Naik)</span>
                    @else
                        <span class="badge badge-danger">T (Tidak Naik)</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <p style="font-size: 11px; background: #fffde7; padding: 8px; border: 1px solid #fff59d; border-radius: 4px;">
        <strong>Analisis KMS:</strong> {{ $pemeriksaanUtama->keterangan_bb }}
    </p>

    <div class="section-title">II. Rangkuman Status Gizi (Standar Antropometri Kemenkes)</div>
    <table class="table-data">
        <thead>
            <tr>
                <th>Indikator</th>
                <th>Nilai Z-Score</th>
                <th>Kesimpulan Klinis</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Berat Badan menurut Umur (BB/U)</td>
                <td>{{ $pemeriksaanUtama->zscore_bbu ?? '0.00' }}</td>
                <td><strong>{{ $pemeriksaanUtama->status_gizi ?? 'Normal' }}</strong></td>
            </tr>
            <tr>
                <td>Tinggi Badan menurut Umur (TB/U)</td>
                <td>{{ $pemeriksaanUtama->zscore_tbu ?? '0.00' }}</td>
                <td><strong>{{ $pemeriksaanUtama->status_stunting ?? 'Normal' }}</strong></td>
            </tr>
            <tr>
                <td>Berat Badan menurut Tinggi Badan (BB/TB)</td>
                <td>{{ $pemeriksaanUtama->zscore_bbtb ?? '0.00' }}</td>
                <td><strong>{{ $pemeriksaanUtama->status_bbtb ?? 'Normal' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- <div class="section-title">III. Kurva Grafik KMS Perkembangan Berat Badan (BB/U)</div>
    <div class="kms-chart">
        @foreach($pemeriksaan as $riwayat)
            @php
                // Cari data standar median kemenkes untuk menghitung persentase bar panjang grafik
                $master = $masterKms->where('umur_bulan', $riwayat->usia_bulan)->first();
                $median = $master ? (float)$master->median : 5.0;
                $persenBar = min(100, max(20, ((float)$riwayat->berat_badan / $median) * 70));
            @endphp
            <div class="kms-row">
                <div class="kms-cell-age">Bulan ke-{{ $riwayat->usia_bulan }}</div>
                <div class="kms-cell-bar">
                    <div class="kms-bar-fill" style="width: {{ $persenBar }}%;">
                        {{ $riwayat->berat_badan }} Kg &nbsp;
                    </div>
                </div>
                <div class="kms-cell-target">
                    <span style="font-size: 9px; color: #999;">KBM:</span> 
                    @if($riwayat->kenaikan_bb === 'naik') ✅ N @else ❌ T @endif
                </div>
            </div>
        @endforeach
    </div> -->
    <!-- ========================================== -->
    <!-- GRAFIK 1: BERAT BADAN MENURUT UMUR (BB/U)   -->
    <!-- ========================================== -->
    <div class="section-title">III. Grafik 1: Tren Berat Badan menurut Umur (BB/U) - Standar KMS</div>
    <div style="margin-top: 5px; background: #ffffff; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px;">
        @foreach($pemeriksaan as $riwayat)
            @php
                $master = $masterKms->where('umur_bulan', $riwayat->usia_bulan)->first();
                $median = $master ? (float)$master->median : 5.0;
                $bbRiil = (float)$riwayat->berat_badan;
                $persenBar = min(100, max(15, ($bbRiil / 12) * 100)); 
                $warnaBar = ($bbRiil < $median) ? '#ffb74d' : '#81c784';
                $posisiMedian = min(95, max(5, ($median / 12) * 100));
            @endphp
            <div style="width: 100%; margin-bottom: 8px; clear: both; height: 18px;">
                <div style="float: left; width: 15%; font-weight: bold; font-size: 10px;">Bln ke-{{ $riwayat->usia_bulan }}</div>
                <div style="float: left; width: 70%; background: #eeeeee; border-radius: 4px; height: 16px; position: relative;">
                    <div style="background: {{ $warnaBar }}; width: {{ $persenBar }}%; height: 16px; border-radius: 4px; text-align: right;">
                        <span style="color: #000; font-weight: bold; font-size: 9px; line-height: 16px; padding-right: 5px;">{{ number_format($bbRiil, 2) }} Kg</span>
                    </div>
                    <div style="position: absolute; left: {{ $posisiMedian }}%; top: 0; width: 2px; height: 16px; background: #e53935; border-left: 1px dashed #ffffff;"></div>
                </div>
                <div style="float: left; width: 15%; text-align: right; font-size: 10px; font-weight: bold; color: {{ $riwayat->kenaikan_bb === 'naik' ? '#2e7d32' : '#c62828' }}">
                    {{ $riwayat->kenaikan_bb === 'naik' ? '✅ N' : '❌ T' }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- ========================================== -->
    <!-- GRAFIK 2: TINGGI BADAN MENURUT UMUR (TB/U)  -->
    <!-- ========================================== -->
    <div class="section-title">IV. Grafik 2: Tren Tinggi/Panjang Badan menurut Umur (TB/U) - Skrining Stunting</div>
    <div style="margin-top: 5px; background: #ffffff; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px;">
        @php
            // Ambil data master TB/U Kemenkes sesuai jenis kelamin anak untuk rentang 0-12 bulan
            $masterTbu = \App\Models\MasterTbu::where('jenis_kelamin', $pasien->jenis_kelamin)->whereBetween('umur_bulan', [0, 12])->get();
        @endphp
        @foreach($pemeriksaan as $riwayat)
            @php
                $masterT = $masterTbu->where('umur_bulan', $riwayat->usia_bulan)->first();
                $medianT = $masterT ? (float)$masterT->median : 50.0;
                $tbRiil = (float)$riwayat->tinggi_badan;
                // Skala maksimal tinggi bayi setel ke 85 cm
                $persenBarT = min(100, max(15, ($tbRiil / 85) * 100)); 
                $warnaBarT = ($tbRiil < $medianT) ? '#ffe082' : '#4db6ac';
                $posisiMedianT = min(95, max(5, ($medianT / 85) * 100));
            @endphp
            <div style="width: 100%; margin-bottom: 8px; clear: both; height: 18px;">
                <div style="float: left; width: 15%; font-weight: bold; font-size: 10px;">Bln ke-{{ $riwayat->usia_bulan }}</div>
                <div style="float: left; width: 70%; background: #eeeeee; border-radius: 4px; height: 16px; position: relative;">
                    <div style="background: {{ $warnaBarT }}; width: {{ $persenBarT }}%; height: 16px; border-radius: 4px; text-align: right;">
                        <span style="color: #000; font-weight: bold; font-size: 9px; line-height: 16px; padding-right: 5px;">{{ number_format($tbRiil, 1) }} Cm</span>
                    </div>
                    <div style="position: absolute; left: {{ $posisiMedianT }}%; top: 0; width: 2px; height: 16px; background: #d32f2f; border-left: 1px dashed #ffffff;"></div>
                </div>
                <div style="float: left; width: 15%; text-align: right; font-size: 9px; font-weight: bold; color: #555;">
                    {{ $riwayat->status_stunting ?? 'Normal' }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- ========================================== -->
    <!-- GRAFIK 3: BERAT BADAN MENURUT TINGGI (BB/TB)-->
    <!-- ========================================== -->
    <div class="section-title">V. Grafik 3: Proporsi Berat terhadap Tinggi Badan (BB/TB) - Skrining Wasting</div>
    <div style="margin-top: 5px; background: #ffffff; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
        @foreach($pemeriksaan as $riwayat)
            @php
                $tbBulat = round((float)$riwayat->tinggi_badan);
                // Ambil acuan median BB berdasarkan tinggi badannya saat itu dari master BB/TB
                $masterBbtb = \App\Models\MasterBbtb::where('jenis_kelamin', $pasien->jenis_kelamin)->where('tinggi_badan_cm', $tbBulat)->first();
                $medianW = $masterBbtb ? (float)$masterBbtb->median : 6.0;
                $bbRiilW = (float)$riwayat->berat_badan;
                $persenBarW = min(100, max(15, ($bbRiilW / 14) * 100)); 
                $warnaBarW = ($riwayat->status_bbtb === 'Gizi Baik (Normal)') ? '#9ccc65' : '#ef5350';
                $posisiMedianW = min(95, max(5, ($medianW / 14) * 100));
            @endphp
            <div style="width: 100%; margin-bottom: 8px; clear: both; height: 18px;">
                <div style="float: left; width: 15%; font-weight: bold; font-size: 10px;">{{ $tbBulat }} Cm</div>
                <div style="float: left; width: 70%; background: #eeeeee; border-radius: 4px; height: 16px; position: relative;">
                    <div style="background: {{ $warnaBarW }}; width: {{ $persenBarW }}%; height: 16px; border-radius: 4px; text-align: right;">
                        <span style="color: #000; font-weight: bold; font-size: 9px; line-height: 16px; padding-right: 5px;">{{ number_format($bbRiilW, 2) }} Kg</span>
                    </div>
                    <div style="position: absolute; left: {{ $posisiMedianW }}%; top: 0; width: 2px; height: 16px; background: #d32f2f; border-left: 1px dashed #ffffff;"></div>
                </div>
                <div style="float: left; width: 15%; text-align: right; font-size: 9px; font-weight: bold; color: #555;">
                    {{ str_replace(' (Normal)', '', $riwayat->status_bbtb ?? 'Normal') }}
                </div>
            </div>
        @endforeach
        <div style="margin-top: 10px; font-size: 8px; color: #666; text-align: center; font-style: italic;">
            *Garis putus-putus merah (<span style="color: #d32f2f; font-weight: bold;">|</span>) mencerminkan Nilai Median Ideal Standar Kementerian Kesehatan RI.
        </div>
    </div>

    <div class="section-title">IV. Intervensi Medis & Catatan Bidan Desa</div>
    <table class="table-info" style="margin-top: 8px;">
        <tr>
            <td style="width: 25%;"><strong>Pemberian Vitamin A</strong></td>
            <td style="width: 5%;">:</td>
            <td>{{ $pemeriksaanUtama->vitamin_a ? 'Sudah Diberikan' : 'Tidak Diberikan' }}</td>
        </tr>
        <tr>
            <td><strong>Pemberian Obat Cacing</strong></td>
            <td>:</td>
            <td>{{ $pemeriksaanUtama->obat_cacing ? 'Sudah Diberikan' : 'Tidak Diberikan' }}</td>
        </tr>
        <tr>
            <td><strong>Jenis Imunisasi</strong></td>
            <td>:</td>
            <td>{{ is_array($pemeriksaanUtama->jenis_imunisasi) ? implode(', ', $pemeriksaanUtama->jenis_imunisasi) : ($pemeriksaanUtama->jenis_imunisasi ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>Konseling/Catatan KIE</strong></td>
            <td>:</td>
            <td style="font-style: italic; color: #555;">"{{ $pemeriksaanUtama->catatan ?? 'Anak sehat, lanjutkan pemberian protein hewani dan jaga pola kebersihan.' }}"</td>
        </tr>
    </table>

</body>
</html>