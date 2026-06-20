@props([
    'warna_tema' => '#10b981',
    'nama_puskesmas' => 'Puskesmas',
    'teks_login' => null,
    'pengaturan' => null,
])

{{-- 🟢 ACUAN DOKUMEN CLOUDFLARE HALAMAN 4: Load Script dengan Mode Explicit --}}
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>

@php
    $posisiTataLetak = match($pengaturan?->posisi_form_login) {
        'kiri' => 'justify-start lg:ml-20',
        'kanan' => 'justify-end lg:mr-20',
        default => 'justify-center',
    };
@endphp

<div class="min-h-screen w-screen flex items-center {{ $posisiTataLetak }} p-4 sm:p-6 font-['Poppins'] bg-cover bg-center bg-no-repeat relative transition-all duration-500 bg-slate-50 dark:bg-slate-900"
     style="@if($pengaturan?->background_login) background-image: url('{{ Storage::url($pengaturan->background_login) }}'); @endif">

    @if($pengaturan?->background_login)
        <div class="absolute inset-0 bg-slate-900/10"></div>
    @endif

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght=300;400;500;600;700;800&display=swap');

        :root { --primary: {{ $warna_tema }}; }
        * { font-family: 'Poppins', sans-serif !important; }

        .login-card h2, .login-card label, .login-card label *, .login-card .text-slate-600, .login-card .text-slate-500, .login-card p, .login-card span {
            color: #1e293b !important; 
        }
        .login-card input {
            color: #1e293b !important;
            background-color: rgba(255, 255, 255, 0.8) !important;
        }
        .fi-btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            border-radius: 14px !important;
            min-height: 46px !important;
            font-weight: 600 !important;
        }
        .fi-btn-primary, .fi-btn-primary * { color: #ffffff !important; }
        .fi-input { border-radius: 14px !important; min-height: 46px !important; background-color: rgba(255, 255, 255, 0.6) !important; color: #1e293b !important; }
    </style>

    <div class="login-card relative z-10 w-full max-w-md rounded-3xl sm:rounded-[32px] shadow-2xl flex flex-col p-6 sm:p-8 xl:p-10"
         style="background-color: rgba(255, 255, 255, 0.75); backdrop-filter: blur(2px); border: 1px solid rgba(255, 255, 255, 0.45);">

        @if(is_array($pengaturan?->logos) && count($pengaturan->logos))
            <div class="mb-5 flex flex-row flex-wrap items-center justify-center gap-4">
                @foreach($pengaturan->logos as $item)
                    @continue(empty($item['path_logo']))
                    <div class="logo-item">
                        <img src="{{ Storage::url($item['path_logo']) }}" class="{{ $item['tinggi_logo'] ?? 'h-8' }} w-auto object-contain">
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-center mb-6 xl:mb-8 px-2">
            <h2 class="text-lg sm:text-xl xl:text-2xl font-bold text-slate-800 dark:text-slate-200 leading-snug">
                {{ $teks_login ?? 'Masuk menggunakan kredensial petugas yang valid.' }}
            </h2>
        </div>

        <div class="w-full">
            <x-filament-panels::form wire:submit="authenticate">
                
                {{ $this->form }}

                {{-- 🟢 ACUAN DOKUMEN HALAMAN 5: CONTAINER KOSONG UNTUK PROGRAMMATIC EXPLICIT RENDERING --}}
                <div class="mt-4 flex flex-col items-center justify-center">
                    <div wire:ignore 
                         id="turnstile-explicit-container"
                         x-data="{
                            init() {
                                if (window.turnstile) {
                                    this.initWidget();
                                } else {
                                    let checkExist = setInterval(() => {
                                        if (window.turnstile) {
                                            clearInterval(checkExist);
                                            this.initWidget();
                                        }
                                    }, 100);
                                }
                            },
                            initWidget() {
                                // Eksplisit render sesuai standard Cloudflare API docs Page 5
                                window.turnstile.render('#turnstile-explicit-container', {
                                    sitekey: '{{ env('CAPTCHA_SITE_KEY') ?? '0x4AAAAAADn3ibc28D-pYIDN' }}',
                                    callback: (token) => {
                                        // Set nilai ke properti Livewire dengan aman
                                        $wire.set('turnstileToken', token);
                                    }
                                });
                            }
                         }">
                    </div>

                    @error('turnstileToken')
                        <span class="text-red-500 text-xs font-semibold mt-2 text-center" style="color: #ef4444 !important;">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                {{-- 🟢 SELESAI EXPLICIT WIDGET --}}

                <div class="mt-5 xl:mt-6">
                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="true"
                    />
                </div>
                
            </x-filament-panels::form>
        </div>

        <div class="text-center mt-8 xl:mt-10 pt-5 xl:pt-6 border-t border-slate-300/60">
            <div class="text-[10px] xl:text-xs text-slate-600 dark:text-slate-400 font-bold">
                &copy; {{ now()->year }} {{ $nama_puskesmas }}
            </div>
        </div>

    </div>
</div>