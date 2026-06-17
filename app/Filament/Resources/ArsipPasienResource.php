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
    
    // 🛠️ LANGSUNG DIKUNCI KE KELOMPOK PELAYANAN
    protected static ?string $navigationGroup = 'Pelayanan';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Kosongkan karena di arsip tidak ada input data baru
    }

    public static function table(Table $table): Table
    {
        return $table
            // KUNCI UTAMA: Hanya mengambil data pasien yang sudah diarsipkan
            ->modifyQueryUsing(fn ($query) => $query->where('is_arsip', true))
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Balita')
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->badge()
                    ->colors(['info' => 'L', 'danger' => 'P']),

                Tables\Columns\TextColumn::make('status_arsip')
                    ->label('Status/Alasan')
                    ->badge()
                    ->state(function ($record) {
                        return $record->tgl_meninggal ? 'Meninggal Dunia' : 'Pindah Domisili/Puskesmas';
                    })
                    ->colors([
                        'danger' => 'Meninggal Dunia',
                        'warning' => 'Pindah Domisili/Puskesmas',
                    ]),

                Tables\Columns\TextColumn::make('detail_kondisi')
                    ->label('Detail Informasi Arsip')
                    ->wrap()
                    ->state(function ($record) {
                        if ($record->tgl_meninggal) {
                            return "Wafat: " . \Carbon\Carbon::parse($record->tgl_meninggal)->format('d/m/Y') . 
                                   " | Penyebab: " . ($record->penyebab_meninggal ?? '-') . 
                                   " | Makam: " . ($record->tempat_pemakaman ?? '-');
                        }
                        return "Ket. Pindah: " . ($record->keterangan_pindah ?? '-');
                    }),
            ])
            ->actions([
                // Tombol Pulihkan
                Tables\Actions\Action::make('pulihkan')
                    ->label('Pulihkan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan Data Pasien?')
                    ->modalDescription('Pasien ini akan dikembalikan ke dalam daftar Data Balita aktif.')
                    ->action(function ($record) {
                        $record->update([
                            'is_arsip' => false,
                            'keterangan_pindah' => null,
                            'tgl_meninggal' => null,
                            'penyebab_meninggal' => null,
                            'tempat_pemakaman' => null,
                        ]);

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
        $user = Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin' || $user->mejaPelayanan?->kode_meja === 'superadmin') {
            return true;
        }
        
        return in_array('arsip-pasiens', $user->akses_menu ?? []);
    }
}