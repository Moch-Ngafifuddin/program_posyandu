<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use App\Models\Pengaturan;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use App\Rules\TurnstileRule; // 🟢 Hubungkan ke Custom Rule yang baru dibuat

class CustomLogin extends BaseLogin
{
    protected static string $layout = 'filament-panels::components.layout.base';

    // Properti publik Livewire untuk menangkap token secara asinkron
    public $turnstileToken;

    public function authenticate(): ?LoginResponse
    {
        // Bypass otomatis jika testing di localhost development
        if (app()->environment('local')) {
            return parent::authenticate();
        }
        
        // 🟢 Jalankan validasi terisolasi menggunakan Custom Validation Rule
        $this->validate([
            'turnstileToken' => ['required', new TurnstileRule],
        ]);

        return parent::authenticate();
    }

    public function getHeading(): string | Htmlable
    {
        $pengaturan = Pengaturan::first();
        return $pengaturan?->teks_login ?? 'Selamat Datang';
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
            'nama_puskesmas' => $pengaturan?->nama_puskesmas ?? '#',
        ];
    }
}