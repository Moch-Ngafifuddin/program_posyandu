<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Pemantauan - {{ $pasien->nama }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-4 md:p-8">

    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-500">
        
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-xl font-bold text-gray-800">KARTU PEMANTAUAN BALITA</h2>
            <a href="{{ route('pantau.index') }}" class="text-blue-500 hover:underline text-sm">&larr; Kembali Pencarian</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-sm">
            <div>
                <table class="w-full">
                    <tr><td class="text-gray-500 py-1 w-1/3">Nama Balita</td><td class="font-semibold">: {{ $pasien->nama }}</td></tr>
                    <tr><td class="text-gray-500 py-1">NIK Balita</td><td class="font-semibold">: {{ $pasien->nik }}</td></tr>
                    <tr><td class="text-gray-500 py-1">No KK</td><td class="font-semibold">: {{ $pasien->no_kk ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Nama Ayah</td><td class="font-semibold">: {{ $pasien->nama_ayah ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">NIK Ayah</td><td class="font-semibold">: {{ $pasien->nik_ayah ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Nama Ibu</td><td class="font-semibold">: {{ $pasien->nama_ibu ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">NIK Ibu</td><td class="font-semibold">: {{ $pasien->nik_ibu ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Pend/Pekerjaan Ayah</td><td class="font-semibold">: {{ $pasien->pendidikan_pekerjaan_ayah ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Pend/Pekerjaan Ibu</td><td class="font-semibold">: {{ $pasien->pendidikan_pekerjaan_ibu ?? '-' }}</td></tr>
                </table>
            </div>
            <div>
                <table class="w-full">
                    <tr><td class="text-gray-500 py-1 w-1/3">Alamat</td><td class="font-semibold">: {{ $pasien->alamat }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Anak Ke</td><td class="font-semibold">: {{ $pasien->anak_ke ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Jenis Kelamin</td><td class="font-semibold">: {{ $pasien->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Tempat, Tgl Lahir</td><td class="font-semibold">: {{ $pasien->tempat_lahir ?? '-' }}, {{ \Carbon\Carbon::parse($pasien->tgl_lahir)->format('d M Y') }}</td></tr>
                    <tr><td class="text-gray-500 py-1">BBL (Berat Lahir)</td><td class="font-semibold">: {{ $pasien->berat_lahir ?? '-' }} gram</td></tr>
                    <tr><td class="text-gray-500 py-1">PBL (Panjang Lahir)</td><td class="font-semibold">: {{ $pasien->panjang_lahir ?? '-' }} cm</td></tr>
                    <tr><td class="text-gray-500 py-1">IMD (Menyusu Dini)</td><td class="font-semibold">: {{ $pasien->imd ? 'YA' : 'TIDAK' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">Riwayat ASI</td><td class="font-semibold">: {{ $pasien->riwayat_asi ?? '-' }}</td></tr>
                    <tr><td class="text-gray-500 py-1">NO HP Aktif</td><td class="font-semibold">: {{ $pasien->no_hp ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        <h3 class="font-bold text-gray-700 mb-3 border-l-4 border-blue-500 pl-2">Riwayat Posyandu</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center border text-gray-600">
                <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                    <tr>
                        <th class="px-2 py-3 border-r">Tgl Posyandu</th>
                        <th class="px-2 py-3 border-r">Umur</th>
                        <th class="px-2 py-3 border-r">BB (Kg)</th>
                        <th class="px-2 py-3 border-r">Rambu (N/T/O)</th>
                        <th class="px-2 py-3 border-r">Titik Grafik</th>
                        <th class="px-2 py-3 border-r">TB/PB (Cm)</th>
                        <th class="px-2 py-3 border-r">LK (Cm)</th>
                        <th class="px-2 py-3 border-r">LILA (Cm)</th>
                        <th class="px-2 py-3 border-r">Vit A / Obat Cacing</th>
                        <th class="px-2 py-3 border-r">SDIDTK/PMBA/ASI</th>
                        <th class="px-2 py-3 border-r">Imunisasi</th>
                        <th class="px-2 py-3 border-r">S.TBC</th>
                        <th class="px-2 py-3 border-r">KIE</th>
                        <th class="px-2 py-3">Rujuk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pasien->pemeriksaanBayi as $riwayat)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-2 py-2 border-r">{{ \Carbon\Carbon::parse($riwayat->tgl_periksa)->format('d/m/Y') }}</td>
                        
                        <td class="px-2 py-2 border-r font-medium text-green-700">{{ $riwayat->keterangan_umur ?? '-' }}</td>
                        
                        <td class="px-2 py-2 border-r">{{ $riwayat->berat_badan ?? '-' }}</td>
                        <td class="px-2 py-2 border-r font-bold">{{ $riwayat->rambu_gizi ?? '-' }}</td>
                        <td class="px-2 py-2 border-r">{{ $riwayat->titik_pertumbuhan ?? '-' }}</td>
                        <td class="px-2 py-2 border-r">{{ $riwayat->tinggi_badan ?? '-' }}</td>
                        <td class="px-2 py-2 border-r">{{ $riwayat->lingkar_kepala ?? '-' }}</td>
                        <td class="px-2 py-2 border-r">{{ $riwayat->lingkar_lengan ?? '-' }}</td>
                        
                        <td class="px-2 py-2 border-r text-xs">
                            @php
                                $vit_cacing = [];
                                if($riwayat->vitamin_a) $vit_cacing[] = 'Vit A';
                                if($riwayat->obat_cacing) $vit_cacing[] = 'Obat Cacing';
                            @endphp
                            {!! count($vit_cacing) > 0 ? implode('<br>', $vit_cacing) : '-' !!}
                        </td>
                        
                        <td class="px-2 py-2 border-r text-xs">
                            @php
                                $layanan = [];
                                if($riwayat->sdidtk) $layanan[] = 'SDIDTK';
                                if($riwayat->pmba) $layanan[] = 'PMBA';
                                if($riwayat->asi_eksklusif) $layanan[] = 'ASI';
                            @endphp
                            {!! count($layanan) > 0 ? implode('<br>', $layanan) : '-' !!}
                        </td>
                        
                        <td class="px-2 py-2 border-r">{{ $riwayat->jenis_imunisasi ?? '-' }}</td>
                        <td class="px-2 py-2 border-r font-bold text-blue-600">{{ $riwayat->deteksi_tbc ? 'v' : '-' }}</td>
                        <td class="px-2 py-2 border-r font-bold text-blue-600">{{ $riwayat->kie ? 'v' : '-' }}</td>
                        <td class="px-2 py-2 font-bold text-red-500">{{ $riwayat->rujuk ? 'v' : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="px-4 py-6 text-center text-gray-500">
                            Belum ada data pemeriksaan posyandu untuk anak ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 text-xs text-gray-400 text-center">
            *Data ini di-generate otomatis oleh Sistem Posyandu. Hubungi Kader jika terdapat ketidaksesuaian data.
        </div>
    </div>

</body>
</html>