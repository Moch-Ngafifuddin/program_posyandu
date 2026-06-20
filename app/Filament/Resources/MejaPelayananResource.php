<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\MejaPelayananResource\Pages;
use App\Filament\Resources\MejaPelayananResource\RelationManagers;
use App\Models\MejaPelayanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MejaPelayananResource extends Resource
{
    protected static ?string $model = MejaPelayanan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengaturan Meja Pelayanan';
    protected static ?string $pluralModelLabel = 'Pengaturan Meja Pelayanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode_meja')
                    ->label('Kode Sistem Meja')
                    ->placeholder('Contoh: meja_1 (Gunakan huruf kecil & underscore)')
                    ->required()
                    ->unique('meja_pelayanan', 'kode_meja', ignoreRecord: true),
                Forms\Components\TextInput::make('nama_meja')
                    ->label('Nama Meja Pelayanan')
                    ->placeholder('Contoh: Meja 1: Pendaftaran & Data Dasar')
                    ->required(),
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi Tugas Meja')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_meja')->label('Kode Meja')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('nama_meja')->label('Nama Meja Pelayanan')->weight('semibold'),
                Tables\Columns\TextColumn::make('deskripsi')->label('Deskripsi Penugasan')->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMejaPelayanans::route('/'),
            'create' => Pages\CreateMejaPelayanan::route('/create'),
            'edit' => Pages\EditMejaPelayanan::route('/{record}/edit'),
        ];
    }
    public static function canAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('meja-pelayanans', $user->akses_menu ?? []);
    }
}
