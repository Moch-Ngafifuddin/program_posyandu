<div class="space-y-4">
    
    @php
        $punyaDataBalita = isset($pemeriksaan) && $pemeriksaan->count() > 0;
        $punyaData = isset($pemeriksaan) && $pemeriksaan->isNotEmpty();

        $tglLahir = isset($record->tgl_lahir) ? \Carbon\Carbon::parse($record->tgl_lahir) : null;
            $umurLengkap = "0 tahun - 0 bulan - 0 hari";
            
            if ($tglLahir) {
                // Kalkulasi selisih presisi dari tanggal lahir hingga sekarang
                $diff = $tglLahir->diff(\Carbon\Carbon::now());
                $umurLengkap = "{$diff->y} tahun - {$diff->m} bulan - {$diff->d} hari";
            }

            // Mengambil status gizi terbaru dari baris pemeriksaan pertama jika ada
            $pemeriksaanTerakhir = $pemeriksaan?->first();
            $kategoriGizi = $pemeriksaanTerakhir?->status_gizi ?? 'Belum Ada Pemeriksaan';
    @endphp
    
</div>
    @if(!$punyaDataBalita)
        <div class="text-center py-8 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat pemeriksaan bulanan yang tercatat.</p>
        </div>
    @endif

    @if($punyaDataBalita)
    <div class="p-4 mb-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs shadow-sm">
            <div>
                <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">Nama Lengkap</span>
                <strong class="text-gray-800 dark:text-gray-200 text-sm font-semibold">{{ $record?->nama ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">NIK Balita</span>
                <strong class="text-gray-800 dark:text-gray-200 text-sm font-mono">{{ $record?->nik ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">Tanggal Lahir</span>
                <strong class="text-gray-800 dark:text-gray-200 text-sm">{{ $tglLahir ? $tglLahir->format('d-m-Y') : '-' }}</strong>
            </div>
            <div>
            <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">Jenis Kelamin</span>
            <strong class="text-gray-800 dark:text-gray-200 text-sm">
                {{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
            </strong>
            </div>
            <div>
                <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">Umur Saat Ini</span>
                <strong class="text-blue-600 dark:text-blue-400 text-sm font-mono font-bold">({{ $umurLengkap }})</strong>
            </div>
            <div>
                <span class="text-gray-400 block font-medium uppercase tracking-wider text-[10px]">Kategori & Status</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                    Balita ({{ $kategoriGizi }})
                </span>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <!-- <h4 class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Arsip Rekam Medis Bulanan (Standar Kemenkes e-PPGBM)
                </h4> -->
            </div>
            
            <div class="w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-[10px] font-sans leading-tight tracking-tight break-words font-normal">
                        <thead>
                            <tr class="bg-gray-200/60 dark:bg-gray-900 text-gray-800 dark:text-gray-200 border-b border-gray-300 dark:border-gray-700 text-center font-normal">
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 w-6 font-normal">No</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">Tgl<br>Periksa</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">BB<br>(Kg)</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">TB<br>(Cm)</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">Cara<br>Ukur</th>
                                
                                {{-- Kemenkes BB/U --}}
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">BB/U<br>(Berat)</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">ZS<br>BB/U</th>
                                
                                {{-- Kemenkes TB/U --}}
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">TB/U<br>(Stunting)</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">ZS<br>TB/U</th>
                                
                                {{-- Kemenkes BB/TB (Wasting) --}}
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">BB/TB<br>(Gizi)</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">ZS<br>BB/TB</th>

                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">Naik<br>BB</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 w-16 font-normal">Ket.<br>Naik</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">LILA</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal">LK</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal" title="Pitting Edema">Edema</th>
                                <th class="p-1 border-r border-gray-300 dark:border-gray-700 font-normal" title="Kelas Ibu">Kls<br>Ibu</th>
                                <th class="p-1 font-normal" title="Menerima MBG">MBG</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($pemeriksaan as $index => $item)
                                @php
                                    // Konversi Z-Score ke float untuk validasi presisi tinggi sesuai PMK No 2/2020
                                    $zsBbu = isset($item->zscore_bbu) ? (float)$item->zscore_bbu : null;
                                    $zsTbu = isset($item->zscore_tbu) ? (float)$item->zscore_tbu : null;
                                    $zsBbtb = isset($item->zscore_bbtb) ? (float)$item->zscore_bbtb : null; // Pastikan kolom ini ditarik/dihitung

                                    $naik = strtolower($item->kenaikan_bb ?? '');
                                    
                                    // Indikator baris bermasalah (jika ada nilai di bawah -2 SD pada indeks manapun)
                                    $isBermasalah = ($zsBbu !== null && $zsBbu < -2.0) || 
                                                    ($zsTbu !== null && $zsTbu < -2.0) || 
                                                    ($zsBbtb !== null && $zsBbtb < -2.0);
                                @endphp
                                <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-colors {{ $isBermasalah ? 'bg-amber-50/60 dark:bg-amber-900/20 font-medium' : '' }}">
                                    
                                    {{-- 1. No --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 text-gray-500">
                                        {{ $index + 1 }}
                                    </td>
                                    
                                    {{-- 2. Tgl --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200">
                                        {{ \Carbon\Carbon::parse($item->tgl_periksa)->format('d/m/y') }}
                                    </td>
                                    
                                    {{-- 3. BB --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-blue-600 dark:text-blue-400">
                                        {{ $item->berat_badan ? number_format($item->berat_badan, 1) : '-' }}
                                    </td>
                                    
                                    {{-- 4. TB --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-emerald-600 dark:text-emerald-400">
                                        {{ $item->tinggi_badan ? number_format($item->tinggi_badan, 1) : '-' }}
                                    </td>

                                    {{-- 5. Cara Ukur --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400">
                                        {{ $item->cara_ukur ?? '-' }}
                                    </td>
                                    
                                    {{-- 6. BB/U Status --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800">
                                        @php
                                            $colorBbu = 'text-gray-700 dark:text-gray-300';
                                            if ($zsBbu !== null) {
                                                if ($zsBbu >= -2.0 && $zsBbu <= 1.0) $colorBbu = 'text-green-600 font-medium';
                                                elseif ($zsBbu < -2.0) $colorBbu = 'text-red-600 font-bold';
                                                else $colorBbu = 'text-blue-500';
                                            }
                                        @endphp
                                        <span class="{{ $colorBbu }}">
                                            {{ $item->status_gizi ?? '-' }}
                                        </span>
                                    </td>

                                    <!-- ZS BB/U -->
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 font-mono">
                                        {{ isset($item->zscore_bbu) ? number_format((float)$item->zscore_bbu, 2) : '-' }}
                                    </td>
                                    
                                    {{-- 8. TB/U Status (Stunting) --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800">
                                        @php
                                            $colorTbu = 'text-gray-700 dark:text-gray-300';
                                            if ($zsTbu !== null) {
                                                if ($zsTbu >= -2.0) $colorTbu = 'text-green-600 font-medium';
                                                elseif ($zsTbu >= -3.0 && $zsTbu < -2.0) $colorTbu = 'text-amber-600 font-semibold'; // Pendek (Stunted)
                                                elseif ($zsTbu < -3.0) $colorTbu = 'text-red-600 font-bold'; // Sangat Pendek
                                            }
                                        @endphp
                                        <span class="{{ $colorTbu }}">
                                            {{ $item->status_stunting ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- 9. ZS TB/U --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 font-mono">
                                        {{ isset($item->zscore_tbu) ? number_format((float)$item->zscore_tbu, 2) : '-' }}
                                    </td>

                                    {{-- 10. BB/TB Status (Wasting/Kurus-Gemuk) --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800">
                                        @php
                                            $colorBbtb = 'text-gray-700 dark:text-gray-300';
                                            if ($zsBbtb !== null) {
                                                if ($zsBbtb >= -2.0 && $zsBbtb <= 1.0) $colorBbtb = 'text-green-600 font-medium';
                                                elseif ($zsBbtb < -2.0) $colorBbtb = 'text-red-600 font-bold'; // Gizi Kurang / Buruk
                                                elseif ($zsBbtb > 1.0) $colorBbtb = 'text-purple-600 font-semibold'; // Gizi Lebih / Obesitas
                                            }
                                        @endphp
                                        <span class="{{ $colorBbtb }}">
                                            {{ $item->status_bbtb ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- 11. ZS BB/TB --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400 font-mono">
                                        {{ isset($item->zscore_bbtb) ? number_format((float)$item->zscore_bbtb, 2) : '-' }}
                                    </td>

                                    {{-- 12. Naik BB --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800
                                        {{ $naik === 'naik' ? 'text-green-600' : ($naik === 'tidak naik' ? 'text-red-500' : 'text-gray-500') }}">
                                        {{ strtoupper($item->kenaikan_bb ?? '-') }}
                                    </td>

                                    {{-- 13. Ket. Naik BB --}}
                                    <td class="p-1 border-r border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400">
                                        {{ $item->keterangan_bb ?? '-' }}
                                    </td>
                                    
                                    {{-- 14. LILA --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $item->lila ? number_format($item->lila, 1) : '-' }}
                                    </td>
                                    
                                    {{-- 15. LK --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $item->lingkar_kepala ? number_format($item->lingkar_kepala, 1) : '-' }}
                                    </td>

                                    {{-- 16. Edema (Y/T) --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800">
                                        {!! ($item->pitting_edema ?? false) ? '<span class="text-red-600 font-bold">Y</span>' : '<span class="text-gray-400">T</span>' !!}
                                    </td>

                                    {{-- 17. Kelas Ibu (Y/T) --}}
                                    <td class="p-1 text-center border-r border-gray-200 dark:border-gray-800">
                                        {!! ($item->kelas_ibu ?? false) ? '<span class="text-green-600">Y</span>' : '<span class="text-gray-400">T</span>' !!}
                                    </td>

                                    {{-- 18. MBG (Y/T) --}}
                                    <td class="p-1 text-center">
                                        {!! ($item->menerima_mbg ?? false) ? '<span class="text-blue-600">Y</span>' : '<span class="text-gray-400">T</span>' !!}
                                    </td>
                                    
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>