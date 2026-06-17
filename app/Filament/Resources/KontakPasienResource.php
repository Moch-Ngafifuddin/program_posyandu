<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\KontakPasienResource\Pages;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KontakPasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    // Menggunakan ikon buku telepon / kontak
    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Kontak Orang Tua';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ubah Informasi Kontak')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Pasien')
                            ->disabled(), 

                        Forms\Components\TextInput::make('no_hp')
                            ->label('Nomor WhatsApp (Aktif)')
                            ->tel()
                            ->placeholder('Contoh: 08123456789')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('nama')
                ->label('Nama Pasien')
                ->searchable()
                ->sortable()
                ->weight('bold'),

            
            Tables\Columns\TextColumn::make('kategori')
                ->label('Kategori')
                ->state(function ($record): string {
                    
                    if ($record->pemeriksaanBayi()->exists()) {
                        return 'Balita';
                    }
                    if ($record->pemeriksaanRemaja()->exists()) {
                        return 'Remaja';
                    }
                    if ($record->pemeriksaanLansia()->exists()) {
                        return 'Lansia';
                    }
                    return 'Umum / Baru';
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'balita' => 'pink',
                    'remaja' => 'blue',
                    'lansia' => 'emerald',
                    default => 'gray',
                }),

            Tables\Columns\TextInputColumn::make('no_hp')
                ->label('Nomor WhatsApp (Klik untuk Edit)')
                ->searchable()
                ->rules(['required', 'numeric']),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make()
                ->label('Ubah via Modal')
                ->modalWidth('md'),
        ])
        ->bulkActions([]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKontakPasiens::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('kontak-pasiens', $user->akses_menu ?? []);
    }
}