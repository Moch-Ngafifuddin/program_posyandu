<?php

namespace App\Filament\Resources;


use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Manajemen Akun';
    protected static ?string $pluralModelLabel = 'Manajemen Akun';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 🔐 SEKSI 1: INFORMASI AKUN & PENUGASAN MEJA
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique('users', 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                            
                        // 🟢 FIX 1: Memasukkan komponen meja ke dalam kontainer grid agar tata letaknya rapi sejajar
                        Forms\Components\Select::make('meja_pelayanan_id')
                            ->label('Penugasan Meja Pelayanan')
                            ->relationship('mejaPelayanan', 'nama_meja')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                // 🎛️ SEKSI 2: OTORISASI HAK AKSES MENU DINAMIS
                Forms\Components\Section::make('Hak Akses Menu')
                    ->description('Centang menu yang boleh diakses petugas ini secara spesifik')
                    ->schema([
                        Forms\Components\CheckboxList::make('akses_menu')
                            ->hiddenLabel()
                            ->options(function () {
                                $resources = \Filament\Facades\Filament::getResources();
                                $options = [];
                                
                                foreach ($resources as $resource) {
                                    $slug = $resource::getSlug();
                                    $label = $resource::getNavigationLabel();
                                    $options[$slug] = $label;
                                }

                                $options['pengaturan-sistem'] = 'Pengaturan Tampilan';
                                return $options;
                            })
                            ->columns(4)
                            ->gridDirection('row'),
                    ]),
                
                // 📍 SEKSI 3: KONFIGURASI LOKASI LAYANAN POSYANDU
                Forms\Components\Section::make('Konfigurasi Wilayah Kerja & Pelayanan')
                    ->description('Data ini akan digunakan secara otomatis sebagai identitas wilayah pada rekam medis dan menu Cek Riwayat.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('provinsi')
                                    ->label('Provinsi')
                                    ->placeholder('Contoh: JAWA TENGAH')
                                    ->required()
                                    ->maxLength(100),
    
                                Forms\Components\TextInput::make('kabupaten_kota')
                                    ->label('Kabupaten / Kota')
                                    ->placeholder('Contoh: KABUPATEN BANYUMAS')
                                    ->required()
                                    ->maxLength(100),
                            ]),
    
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->placeholder('Contoh: KEMBARAN')
                                    ->required()
                                    ->maxLength(100),
    
                                Forms\Components\TextInput::make('desa_kelurahan')
                                    ->label('Desa / Kelurahan')
                                    ->placeholder('Contoh: TAMBAKSARI KIDUL')
                                    ->required()
                                    ->maxLength(100),
                            ]),
    
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nama_puskesmas')
                                    ->label('Nama Puskesmas')
                                    ->placeholder('Contoh: KEMBARAN I')
                                    ->required()
                                    ->maxLength(100),
    
                                Forms\Components\TextInput::make('nama_posyandu')
                                    ->label('Nama Posyandu')
                                    ->placeholder('Contoh: ANYELIR')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Petugas')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->searchable(),

                // 🟢 Menampilkan info meja tugas saat ini di baris tabel manajemen
                Tables\Columns\TextColumn::make('mejaPelayanan.nama_meja')
                    ->label('Penugasan Meja')
                    ->badge()
                    ->color('info')
                    ->placeholder('Belum Diatur'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Sejak')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }


    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('users', $user->akses_menu ?? []);
    }
}