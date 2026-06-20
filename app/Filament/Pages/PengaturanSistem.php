<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Notifications\Notification;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;

class PengaturanSistem extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan Tampilan';
    protected static ?string $title = 'Konfigurasi Tampilan & Branding';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.pengaturan-sistem';

    public ?array $data = [];

    public function mount(): void
    {
        $pengaturan = Pengaturan::find(1) ?? new Pengaturan();
        
        $this->form->fill($pengaturan->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Branding Sistem Informasi')
                    ->description('Sesuaikan identitas Puskesmas, teks halaman depan, dan warna dasar aplikasi.')
                    ->schema([
                        TextInput::make('nama_puskesmas')
                            ->label('Nama Puskesmas / Aplikasi')
                            ->required(),

                        TextInput::make('text_logo')
                            ->label('Input No / Kalimat di sebelah logo')
                            ->required(),

                        Textarea::make('teks_login')
                            ->label('Teks Selamat Datang di Halaman Login')
                            ->rows(3)
                            ->helperText('Teks ini akan menjadi judul utama halaman login menggantikan tulisan LOGIN AKUN.') 
                            ->required(),

                            FileUpload::make('logo')
                            ->label('Upload Logo Puskesmas Utama (Muncul di Pojok Kiri Atas / Sidebar Panel)')
                            ->image()
                            ->disk('public')
                            ->directory('branding-logo'),

                        Select::make('tinggi_logo_utama')
                            ->label('Ukuran Tinggi Logo Utama di Sidebar')
                            ->options([
                                '2rem' => 'Kecil (32px)',
                                '2.5rem' => 'Sedang (40px)',
                                '3rem' => 'Besar (48px)',
                                '3.5rem' => 'Sangat Besar (56px)',
                            ])
                            ->default('2.5rem')
                            ->required(),

                        Select::make('posisi_form_login')
                            ->label('Tata Letak Kotak Form Login')
                            ->helperText('Sesuaikan posisi kotak agar tidak menutupi objek gambar latar belakang.')
                            ->options([
                                'kiri' => 'Sebelah Kiri Screen',
                                'tengah' => 'Tepat di Tengah-Tengah',
                                'kanan' => 'Sebelah Kanan Screen',
                            ])
                            ->default('tengah')
                            ->required(),

                        FileUpload::make('background_login')
                            ->label('Upload Gambar Latar Belakang (Background Login)')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('branding-bg')
                            ->maxSize(10240),
                            
                        ColorPicker::make('warna_tema')
                            ->label('Warna Utama Aplikasi (Theme Color)')
                            ->required(),
                    ]),

                Section::make('Pengaturan Multi Logo Halaman Login')
                    ->description('Tambahkan satu atau beberapa logo instansi pendukung yang akan ditampilkan di atas judul login.')
                    ->schema([
                        Repeater::make('logos')
                            ->label('Daftar Logo Instansi Tambahan')
                            ->schema([
                                FileUpload::make('path_logo')
                                    ->label('Preview Logo Instansi')
                                    ->image()
                                    ->disk('public')
                                    ->directory('system-logos')
                                    ->imagePreviewHeight('150') 
                                    ->maxSize(2048) 
                                    ->required(),
                                    
                                Select::make('tinggi_logo')
                                    ->label('Ukuran Tinggi Logo')
                                    ->options([
                                        'h-12' => 'Kecil (48px)',
                                        'h-16' => 'Sedang (64px)',
                                        'h-20' => 'Besar (80px)',
                                        'h-24' => 'Sangat Besar (96px)',
                                    ])
                                    ->default('h-16')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->createItemButtonLabel('Tambah Logo Baru')
                            ->grid(2),
                    ]),
            ])
            ->statePath('data');
    }


    protected function getFormActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Simpan Perubahan Tampilan')
                ->submit('form')
                ->color('success')
                ->icon('heroicon-m-check'),
        ];
    }

    public function simpan(): void
    {
        $formData = $this->form->getState();
        Pengaturan::updateOrCreate(['id' => 1], $formData);
        
        Notification::make()
            ->title('Konfigurasi Berhasil Disimpan')
            ->body('Perubahan branding dan tampilan sukses diterapkan ke sistem.')
            ->success()
            ->duration(3000) 
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('pengaturan-sistems', $user->akses_menu ?? []);
    }
}