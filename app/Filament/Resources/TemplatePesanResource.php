<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\TemplatePesanResource\Pages;
use App\Models\TemplatePesan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemplatePesanResource extends Resource
{
    protected static ?string $model = TemplatePesan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Template Pesan WA';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Template')
                    ->description('Tuliskan judul template dan isi pesan otomatisnya di bawah ini.')
                    ->schema([
                        Forms\Components\TextInput::make('nama_template')
                            ->label('Nama / Judul Template')
                            ->placeholder('Contoh: Undangan Posyandu Balita Rutin')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('isi_pesan')
                            ->label('Isi Pesan WhatsApp')
                            ->placeholder("Gunakan teks kustom Anda. Contoh:\nHalo Ibu, besok pagi jam 08.00 WIB...")
                            ->rows(8)
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_template')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('isi_pesan')
                    ->label('Potongan Isi Pesan')
                    ->limit(70) // Memotong teks panjang agar tabel tetap rapi
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplatePesans::route('/'),
            'create' => Pages\CreateTemplatePesan::route('/create'),
            'edit' => Pages\EditTemplatePesan::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('template-pesans', $user->akses_menu ?? []);
    }
}