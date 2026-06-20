<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use App\Models\Pengaturan;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Http\Responses\Auth\Contracts\LoginResponse; 
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class CustomLogin extends BaseLogin
{
    public $turnstileToken;

    public function authenticate(): ?LoginResponse
    {
        if (app()->environment('local')) {
            return parent::authenticate();
        }
        
        if (empty($this->turnstileToken)) {
            Notification::make()
                ->title('Verifikasi Diperlukan')
                ->body('Silakan selesaikan verifikasi keamanan (Cloudflare) terlebih dahulu.')
                ->warning()
                ->send();

            return null;
        }
        
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key', env('CLOUDFLARE_TURNSTILE_SECRET_KEY')),
            'response' => $this->turnstileToken,
            'remoteip' => request()->ip(),
        ]);

        $captchaResult = $response->json();

        if (!$captchaResult['success']) {
            Notification::make()
                ->title('Akses Masuk Ditolak')
                ->body('Terdeteksi aktivitas mencurigakan atau CAPTCHA kadaluarsa. Silakan muat ulang halaman.')
                ->danger()
                ->send();

            return null;
        }

        return parent::authenticate();
    }

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getHeading(): string | Htmlable
    {
        try {
            $pengaturan = Pengaturan::first();
            return $pengaturan?->teks_login ?? 'Selamat Datang';
        } catch (\Throwable $e) {
            return 'Selamat Datang';
        }
    }

    public function getView(): string
    {
        return 'filament.pages.auth.custom-login';
    }

    public function getViewData(): array
    {
        $pengaturan = Pengaturan::first();

        return [
            'pengaturan'     => $pengaturan,
            'warna_tema'     => $pengaturan?->warna_tema ?? '#10b981',
            'teks_login'     => $pengaturan?->teks_login ?? 'Selamat Datang Di Sistem Informasi Balita',
            'nama_puskesmas' => $pengaturan?->nama_puskesmas ?? 'Puskesmas Lokal',
        ];
    }
}