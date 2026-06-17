<x-filament-panels::page>
    <!-- Informasi Meja Tugas Aktif -->
    <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-50 border border-primary-200 dark:bg-primary-950/30 dark:border-primary-800 text-sm w-fit">
        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        </svg>
        <span class="text-gray-600 dark:text-gray-300">Meja Tugas Aktif:</span>
        <span class="font-bold text-primary-600 dark:text-primary-400">
            {{ $mejaTugas == '1' ? 'Meja 1 (Pendaftaran Balita Baru)' : 'Meja ' . $mejaTugas }}
        </span>
    </div>

    <!-- Grid Menu Pemeriksaan Slim -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mt-1">
        
        <!-- MENU 1: BALITA -->
        <a href="{{ $urlPemeriksaanBalita }}" class="group flex items-center justify-between p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow hover:border-primary-500 dark:hover:border-primary-500 transition-all duration-150">
            <div class="flex items-center gap-3.5">
                <div class="p-2.5 w-11 h-11 bg-pink-50 dark:bg-pink-950/50 rounded-lg text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">
                        Pemeriksaan Balita
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Pencatatan Tumbuh Kembang & Gizi
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