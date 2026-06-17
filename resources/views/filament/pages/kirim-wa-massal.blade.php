<x-filament-panels::page>
    <form wire:submit.prevent="eksekusiKirim" class="space-y-4">
        {{ $this->form }}
        
        <x-filament::button type="submit" size="lg" icon="heroicon-m-paper-airplane" class="w-fit">
            Mulai Kirim Pesan Massal
        </x-filament::button>
    </form>
</x-filament-panels::page>