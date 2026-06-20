<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArsipPasienResource\Pages;
use App\Models\Pasien; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;


class ArsipPasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Arsip Pasien';
    protected static ?string $pluralModelLabel = 'Arsip Pasien';
    protected static ?string $modelLabel = 'Arsip Pasien';
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]); 
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('is_arsip', true)->with(['kondisiKhusus']))
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->fontFamily('mono'),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Balita')
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status_arsip')
                    ->label('Status / Alasan')
                    ->badge()
                    ->state(function (Pasien $record) {
                        $kondisi = $record->kondisiKhusus;
                        return ($kondisi && $kondisi->tgl_meninggal) ? 'Meninggal Dunia' : 'Pindah Domisili';
                    })
                    ->colors([
                        'danger' => 'Meninggal Dunia',
                        'warning' => 'Pindah Domisili',
                    ]),

                Tables\Columns\TextColumn::make('detail_kondisi')
                    ->label('Detail Informasi Arsip')
                    ->wrap()
                    ->state(function (Pasien $record) {
                        $kondisi = $record->kondisiKhusus;
                        
                        if ($kondisi && $kondisi->tgl_meninggal) {
                            return "Wafat: " . \Carbon\Carbon::parse($kondisi->tgl_meninggal)->format('d/m/Y') . 
                                   " | Penyebab: " . ($kondisi->penyebab_meninggal ?? '-') . 
                                   " | Makam: " . ($kondisi->tempat_pemakaman ?? '-');
                        }
                        
                        return "Ket. Pindah: " . ($kondisi->keterangan_pindah ?? '-');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pulihkan')
                    ->label('Pulihkan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan Data Pasien?')
                    ->modalDescription('Pasien ini akan dikembalikan ke dalam daftar Data Balita aktif dan seluruh catatan arsip kondisi khusus akan dibersihkan.')
                    ->action(function (Pasien $record) {
                        $record->update([
                            'is_arsip' => false,
                        ]);

                        $record->kondisiKhusus()->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Pasien Dipulihkan')
                            ->body("Data {$record->nama} berhasil dikembalikan ke daftar aktif.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageArsipPasiens::route('/'),
        ];
    }


    public static function canAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('arsip-pasiens', $user->akses_menu ?? []);
    }

}