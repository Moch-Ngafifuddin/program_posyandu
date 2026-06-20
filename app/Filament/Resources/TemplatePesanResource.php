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
                            ->placeholder("Halo Ibu {nama_ibu}, besok pagi jam {jam_mulai} WIB diharapkan membawa {nama_balita} ke lokasi {lokasi}...")
                            ->required()
                            ->rows(6)
                            ->helperText(new \Illuminate\Support\HtmlString("
                                <div class='mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400'>
                                    <span class='font-bold text-primary-600 block mb-1'>💡 Kode Token Dinamis Otomatis Yang Didukung Sistem:</span>
                                    <ul class='list-disc pl-4 space-y-0.5'>
                                        <li><code class='bg-white dark:bg-gray-900 px-1 py-0.5 rounded font-mono border'>{nama_balita}</code> - Mengganti otomatis nama anak balita target.</li>
                                        <li><code class='bg-white dark:bg-gray-900 px-1 py-0.5 rounded font-mono border'>{nama_ibu}</code> - Mengganti otomatis nama Ibu/Wali (Default jika kosong: 'Ibu').</li>
                                        <li><code class='bg-white dark:bg-gray-900 px-1 py-0.5 rounded font-mono border'>{tanggal}</code> - Hari dan tanggal jalannya kegiatan posyandu (H-1).</li>
                                        <li><code class='bg-white dark:bg-gray-900 px-1 py-0.5 rounded font-mono border'>{lokasi}</code> - Tempat lokasi berlangsungnya acara posyandu.</li>
                                        <li><code class='bg-white dark:bg-gray-900 px-1 py-0.5 rounded font-mono border'>{jam_mulai}</code> - Jam/waktu dimulainya timbangan pelayanan.</li>
                                    </ul>
                                </div>
                            ")),
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
                    ->limit(70)
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
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('template-pesans', $user->akses_menu ?? []);
    }
}