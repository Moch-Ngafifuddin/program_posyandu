<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Models\Pengaturan;
use App\Filament\Pages\Auth\CustomLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Schema;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // 🟢 LANGKAH AMAN: Inisialisasi nilai default agar tidak crash saat migrasi awal
        $logoUrl = asset('logo_kecil.svg');
        $namaPuskesmas = 'Sistem Informasi Posyandu';
        $textLogo = '';
        $tinggiLogoUtama = '2.5rem';

        // Pengecekan database yang aman dari resiko infinite loop
        try {
            if (Schema::hasTable('pengaturan')) {
                $pengaturan = Pengaturan::first();
                if ($pengaturan) {
                    $logoUrl = $pengaturan->logo ? asset('storage/' . $pengaturan->logo) : asset('logo_kecil.svg');
                    $namaPuskesmas = $pengaturan->nama_puskesmas ?? 'Sistem Informasi Posyandu';
                    $textLogo = $pengaturan->text_logo ?? '';
                    $tinggiLogoUtama = $pengaturan->tinggi_logo_utama ?? '2.5rem';
                }
            }
        } catch (\Throwable $e) {
            // Abaikan eror jika database belum siap
        }

        return $panel
            ->default()
            ->id('admin')
            ->favicon(asset('logo_kecil.svg'))
            ->path('')
            ->login(CustomLogin::class)
            ->colors([
                'primary' => Color::Emerald,
                'success' => Color::Teal,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
                'gray'    => Color::Slate,
            ])
            
            // Mengunci nama brand utama
            ->brandName($namaPuskesmas) 

            // Render logo dan teks kustom di samping gambar logo
            ->brandLogo(new \Illuminate\Support\HtmlString("
                <div class='flex items-center gap-3 py-1'>
                    <img src='{$logoUrl}' alt='Logo' style='height: {$tinggiLogoUtama}; width: auto; object-fit: contain;' />
                    <div class='flex flex-col'>
                        <span class='text-sm font-bold text-slate-800 dark:text-white tracking-wide leading-tight break-words max-w-[160px]'>
                            " . e($textLogo) . "
                        </span>
                    </div>
                </div>
            "))
            ->brandLogoHeight($tinggiLogoUtama)
            
            // 🟢 SOLUSI AMAN JUDUL TAB: Gunakan renderHook hanya untuk menyuntikkan judul awal secara statis
            // TANPA JavaScript MutationObserver yang memicu loop tabrakan dengan Livewire
            ->renderHook(
                'panels::head.end',
                fn () => new \Illuminate\Support\HtmlString("<title>" . e($namaPuskesmas) . "</title>")
            )
            
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]); 
    }
}