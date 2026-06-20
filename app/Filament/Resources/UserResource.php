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
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? \Illuminate\Support\Facades\Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->placeholder(fn (string $context): bool => $context === 'edit' ? 'Kosongkan jika tidak ingin mengubah kata sandi' : ''),

                    Forms\Components\Select::make('meja_pelayanan_id')
                        ->label('Meja Pelayanan')
                        ->relationship('mejaPelayanan', 'nama_meja') 
                        ->placeholder('Pilih meja pelayanan tugas kader')
                        ->required()
                        ->live() 
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                $set('meja_tugas', null);
                                return;
                            }
                    
                            $meja = \App\Models\MejaPelayanan::find($state);
                            $set('meja_tugas', $meja ? $meja->kode_meja : null);
                        }),

                    Forms\Components\Hidden::make('meja_tugas')
                        ->dehydrated(true), 
                    ]),

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
                
                Forms\Components\Section::make('Konfigurasi Wilayah Kerja & Pelayanan')
                    ->description('Pilih instansi posyandu untuk memetakan wilayah kerja rekam medis secara otomatis.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        
                        Forms\Components\Select::make('posyandu_id')
                            ->label('Unit Posyandu Tugas')
                            ->relationship('posyandu', 'nama_posyandu')
                            ->placeholder('Pilih Posyandu tempat penugasan...')
                            ->required()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (! $state) {
                                    $set('provinsi', null);
                                    $set('kabupaten_kota', null);
                                    $set('kecamatan', null);
                                    $set('desa_kelurahan', null);
                                    $set('nama_puskesmas', null);
                                    $set('nama_posyandu', null);
                                    return;
                                }

                                $masterPosyandu = \App\Models\MasterPosyandu::find($state);
                                if ($masterPosyandu) {
                                    $set('provinsi', $masterPosyandu->provinsi);
                                    $set('kabupaten_kota', $masterPosyandu->kabupaten_kota);
                                    $set('kecamatan', $masterPosyandu->kecamatan);
                                    $set('desa_kelurahan', $masterPosyandu->desa_kelurahan);
                                    $set('nama_puskesmas', $masterPosyandu->nama_puskesmas);
                                    $set('nama_posyandu', $masterPosyandu->nama_posyandu);
                                }
                            }),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('provinsi')
                                    ->label('Provinsi')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(100),
                
                                Forms\Components\TextInput::make('kabupaten_kota')
                                    ->label('Kabupaten / Kota')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(100),
                            ]),
                
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(100),
                
                                Forms\Components\TextInput::make('desa_kelurahan')
                                    ->label('Desa / Kelurahan')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(100),
                            ]),
                
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nama_puskesmas')
                                    ->label('Nama Puskesmas')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
                                    ->maxLength(100),
                
                                Forms\Components\TextInput::make('nama_posyandu')
                                    ->label('Nama Posyandu')
                                    ->placeholder('Otomatis terisi...')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated()
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

                Tables\Columns\TextColumn::make('mejaPelayanan.nama_meja')
                    ->label('Penugasan Meja')
                    ->badge()
                    ->color('info')
                    ->placeholder('Belum Diatur'),

                Tables\Columns\TextColumn::make('nama_posyandu')
                    ->label('Wilayah Kerja')
                    ->placeholder('-')
                    ->searchable(),

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

    public static function canAccess(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        if (is_null($user) || $user->email === 'admin@posyandu.com' || $user->meja_tugas === 'superadmin') {
            return true;
        }

        return in_array('users', $user->akses_menu ?? []);
    }
}