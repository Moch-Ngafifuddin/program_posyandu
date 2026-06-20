<x-filament-panels::page>
    <!-- Informasi Meja Tugas - Versi Ringkas (Inline) -->
    <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-50 border border-primary-200 dark:bg-primary-950/30 dark:border-primary-800 text-sm w-fit">
        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        </svg>
        <span class="text-gray-600 dark:text-gray-300">Meja Tugas Aktif:</span>
        <span class="font-bold text-primary-600 dark:text-primary-400">
            <!-- 🟢 SINKRONISASI MEJA: Sesuaikan string pencocokan dengan isi database 'meja_1' -->
            {{ $mejaTugas == 'meja_1' ? 'Meja 1 (Pendaftaran Balita Baru)' : ucwords(str_replace('_', ' ', $mejaTugas)) }}
        </span>
    </div>

    <!-- Grid Menu Pendaftaran Balita Baru - Desain Menyamping Slim -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-1">
        
        <!-- MENU 1: BALITA -->
        <a href="{{ $urlDaftarBalita }}" class="group flex items-center justify-between p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow hover:border-primary-500 dark:hover:border-primary-500 transition-all duration-150">
            <div class="flex items-center gap-3.5">
                <div class="p-2.5 w-11 h-11 bg-pink-50 dark:bg-pink-950/50 rounded-lg text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <!-- 🟢 REVISI VISUAL: Hilangkan duplikasi kata Balita di akhir kalimat -->
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">
                        Pendaftaran Balita Baru
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Registrasi Bayi & Balita baru
                    </p>
                </div>
            </div>
            <div class="text-gray-400 group-hover:text-primary-600 group-hover:translate-x-1 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>
</x-filament-panels::page>