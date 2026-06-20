<x-filament-panels::page>
    <form wire:submit.prevent="simpan" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-4 justify-start">
            <x-filament::button type="submit" color="success" icon="heroicon-m-check">
                Simpan Perubahan Tampilan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>