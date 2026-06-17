<div class="space-y-4">
    {{-- Memanggil komponen Livewire dari Widget GrafikKmsPersonal secara dinamis --}}
    @livewire( app\Filament\Resources\PasienResource\Widgets\GrafikKmsPersonal::class, [
        'pasienId' => $getRecord()?->id
    ])
</div>