@php
    $pasienId = $getRecord()?->id;
@endphp

@if($pasienId)
    <div class="space-y-6">
        <!-- 1. Grafik Utama BB/U (KMS) -->
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            @livewire('App\Filament\Resources\PasienResource\Widgets\GrafikKmsPersonal', ['pasienId' => $pasienId])
        </div>

        <!-- 2. Grafik Utama TB/U (Stunting) -->
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            @livewire('App\Filament\Resources\PasienResource\Widgets\GrafikTbuPersonal', ['pasienId' => $pasienId])
        </div>

        <!-- 3. Grafik Utama BB/TB (Wasting) -->
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            @livewire('App\Filament\Resources\PasienResource\Widgets\GrafikBbtbPersonal', ['pasienId' => $pasienId])
        </div>
    </div>
@else
    <div class="text-center py-6 text-gray-500">
        Data profil balita tidak dapat dimuat.
    </div>
@endif