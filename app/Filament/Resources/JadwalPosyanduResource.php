<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\JadwalPosyanduResource\Pages;
use App\Models\JadwalPosyandu;
use App\Models\TemplatePesan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set; 
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Builder;

class JadwalPosyanduResource extends Resource
{
    protected static ?string $model = JadwalPosyandu::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Kalender Pengingat';
    protected static ?string $navigationGroup = 'Pelayanan';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Agenda & Penjadwalan Siaran Otomatis')
                ->schema([
                    Forms\Components\Hidden::make('posyandu_id')
                        ->default(fn () => auth()->user()?->posyandu_id),

                    Forms\Components\TextInput::make('judul_agenda')
                        ->label('Nama Kegiatan / Agenda')
                        ->required()
                        ->placeholder('Contoh: Pelaksanaan Posyandu & Imunisasi Balita rutin')
                        ->columnSpan('full'), 
                        
                        Forms\Components\TextInput::make('tempat_acara')
                            ->label('Lokasi Tempat Pelaksanaan')
                            ->required()
                            ->placeholder('Contoh: Puskesmas Kembaran')
                            ->columnSpan('full'), 

                        Forms\Components\Select::make('kategori_target')
                            ->label('Kelompok Target')
                            ->options([
                                'balita' => 'Balita (Orang Tua)',
                                'remaja' => 'Remaja',
                                'lansia' => 'Lansia / Keluarga',
                                'bumil' => 'Ibu Hamil',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\DatePicker::make('tanggal_acara')
                            ->label('Tanggal Kegiatan')
                            ->required()
                            ->minDate(now()->toDateString()),

                        TimePicker::make('jam_kirim_pesan')
                            ->label('Jam Pengiriman Pesan (H-1)')
                            ->required()
                            ->default('17:00')
                            ->format('H:i')
                            ->displayFormat('H:i')
                            ->seconds(false),

                        TimePicker::make('waktu_acara')
                            ->label('Waktu Pelaksanaan')
                            ->required()
                            ->default('08:00')
                            ->format('H:i') 
                            ->displayFormat('H:i')
                            ->seconds(false),

                        Select::make('template_id')
                            ->label('Gunakan Template Pesan Master')
                            ->relationship('templatePesan', 'nama_template') 
                            ->placeholder('Kustom (Ketik Manual)')
                            ->searchable()
                            ->preload()
                            ->live() 
                            ->afterStateUpdated(function (?string $state, Set $set) {
                                if ($state) {
                                    $template = TemplatePesan::find($state);
                                    if ($template) {
                                        $set('isi_pesan', $template->isi_pesan); 
                                    }
                                } else {
                                    $set('isi_pesan', null);
                                }
                            }),

                        Textarea::make('isi_pesan') 
                            ->label('Isi Pesan Siaran (WhatsApp Content)')
                            ->placeholder('Tulis isi pesan pengingat di sini...')
                            ->rows(5)
                            ->required(),

                        Forms\Components\Toggle::make('is_aktif')
                            ->label('Aktifkan Jadwal Pengingat Ini')
                            ->default(true)
                            ->columnSpan('full'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user && $user->meja_tugas !== 'superadmin' && $user->email !== 'admin@posyandu.com') {
                    $query->where('posyandu_id', $user->posyandu_id);
                }
                $query->with(['templatePesan', 'posyandu']);
            })
            
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('judul_agenda')
                    ->label('Nama Kegiatan')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('tanggal_acara')
                    ->label('Tanggal Acara')
                    ->date('d-m-Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('templatePesan.nama_template')
                    ->label('Template Master')
                    ->default('Kustom (Ketik Manual)')
                    ->badge()
                    ->color(fn ($state) => $state === 'Kustom (Ketik Manual)' ? 'warning' : 'success'),

                    Tables\Columns\TextColumn::make('kategori_target')
                    ->label('Target')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'balita' => 'primary',
                        'remaja' => 'warning',
                        'lansia' => 'success',
                        'bumil' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('jam_kirim_pesan')
                    ->label('Jam Kirim (H-1)')
                    ->time('H:i'), 

                Tables\Columns\ToggleColumn::make('is_aktif')
                    ->label('Sakelar Otomatis'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_target')
                    ->options([
                        'balita' => 'Balita',
                        'remaja' => 'Remaja',
                        'lansia' => 'Lansia',
                        'bumil' => 'Ibu Hamil',
                    ])
            ])

            ->actions([
                Tables\Actions\EditAction::make(), 
                Tables\Actions\DeleteAction::make() 
                    ->modalHeading('Hapus Jadwal Posyandu')
                    ->modalDescription('Apakah Anda yakin ingin menghapus jadwal agenda ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Data'), 
            ])
            
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Hapus Banyak Jadwal Posyandu')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua jadwal posyandu yang dipilih?'),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwalPosyandus::route('/'),
            'create' => Pages\CreateJadwalPosyandu::route('/create'),
            'edit' => Pages\EditJadwalPosyandu::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('jadwal-posyandus', $user->akses_menu ?? []);
    }
}