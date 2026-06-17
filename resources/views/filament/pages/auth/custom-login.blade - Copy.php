@props([
    'warna_tema' => '#10b981',
    'nama_puskesmas' => 'Puskesmas',
    'teks_login' => null,
    'pengaturan' => null,
])

{{-- Memetakan pilihan database ke utility class Tailwind CSS untuk posisi --}}
@php
    $posisiTataLetak = match($pengaturan?->posisi_form_login) {
        'kiri' => 'justify-start lg:ml-20',
        'kanan' => 'justify-end lg:mr-20',
        default => 'justify-center',
    };
@endphp

<div class="min-h-screen w-screen flex items-center {{ $posisiTataLetak }} p-4 sm:p-6 font-['Poppins'] bg-cover bg-center bg-no-repeat relative transition-all duration-500"
     style="background-color: #f8fafc; @if($pengaturan?->background_login) background-image: url('{{ Storage::url($pengaturan->background_login) }}'); @endif">

    @if($pengaturan?->background_login)
        <div class="absolute inset-0 bg-slate-900/10"></div>
    @endif

    <style>
        /* 🛠️ PERBAIKAN IMPORT FONT POPPINS AGAR LEBIH SEMPURNA */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: {{ $warna_tema }};
        }

        * {
            font-family: 'Poppins', sans-serif !important;
        }

        .hide-scroll::-webkit-scrollbar {
            display: none;
        }

        .hide-scroll {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .login-card {
            transition: all .25s ease;
        }

        .fi-btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 14px !important;
            min-height: 46px !important;
            font-weight: 600 !important;
            transition: all .2s ease-in-out;
            font-family: 'Poppins', sans-serif !important;
        }

        .fi-btn-primary:hover {
            filter: brightness(.95);
            transform: translateY(-1px);
        }

        .fi-input {
            border-radius: 14px !important;
            min-height: 46px !important;
            background-color: rgba(255, 255, 255, 0.6) !important;
            font-family: 'Poppins', sans-serif !important;
        }

        .logo-item img {
            transition: transform .2s ease;
        }

        .logo-item:hover img {
            transform: scale(1.05);
        }

        .loading-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: white;
            border-radius: 9999px;
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <div class="login-card relative z-10 w-full max-w-md rounded-3xl sm:rounded-[32px] shadow-2xl flex flex-col p-6 sm:p-8 xl:p-10"
         style="background-color: rgba(255, 255, 255, 0.75); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.45);">

        {{-- Loop Multi Logo Instansi Pendukung --}}
        @if(is_array($pengaturan?->logos) && count($pengaturan->logos))
            <div class="mb-5 flex flex-row flex-wrap items-center justify-center gap-4">
                @foreach($pengaturan->logos as $item)
                    @continue(empty($item['path_logo']))

                    <div class="logo-item">
                        <img
                            src="{{ Storage::url($item['path_logo']) }}"
                            alt="Logo Instansi"
                            loading="lazy"
                            class="{{ $item['tinggi_logo'] ?? 'h-8' }} w-auto object-contain">
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 🛠️ PERBAIKAN HEADER: Tulisan "LOGIN AKUN" Dihapus, Teks Selamat Datang Diperbesar Proporsional --}}
        <div class="text-center mb-6 xl:mb-8 px-2">
            <h2 class="text-lg sm:text-xl xl:text-2xl font-bold text-slate-800 leading-snug tracking-tight">
                {{ $teks_login ?? 'Masuk menggunakan kredensial petugas yang valid.' }}
            </h2>
        </div>

        {{-- Form Login --}}
        <div class="w-full">
            <x-filament-panels::form wire:submit="authenticate">
                
                {{ $this->form }}

                <div class="mt-5 xl:mt-6">
                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="true"
                    />
                </div>

                {{-- Loading State --}}
                <div wire:loading.flex wire:target="authenticate" class="items-center justify-center gap-2 mt-4 text-sm text-slate-500">
                    <div class="loading-spinner"></div>
                    <span>Memverifikasi akun...</span>
                </div>

            </x-filament-panels::form>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-8 xl:mt-10 pt-5 xl:pt-6 border-t border-slate-300/60">
            <div class="text-[10px] xl:text-xs text-slate-600 font-bold">
                &copy; {{ now()->year }} {{ $nama_puskesmas }}
            </div>
            <div class="mt-1 text-[10px] text-slate-500 font-semibold">
                Sistem Informasi Posyandu Terintegrasi
            </div>
        </div>

    </div>

</div>