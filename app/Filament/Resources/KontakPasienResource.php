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
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => $state ? str_replace([' ', '-', '( ', ')'], '', $state) : null),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Balita')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nama_ibu')
                    ->label('Nama Ibu / Wali')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->state(function (Pasien $record) {
                        return 'Balita';
                    })
                    ->badge()
                    ->color('pink'),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('Nomor WhatsApp')
                    ->fontFamily('mono')
                    ->copyable()
                    ->placeholder('Belum ada nomor HP'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah Nomor')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->modalWidth('md')
                    ->successNotificationTitle('Nomor WhatsApp berhasil diperbarui secara aman!'),
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
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('kontak-pasiens', $user->akses_menu ?? []);
    }
}