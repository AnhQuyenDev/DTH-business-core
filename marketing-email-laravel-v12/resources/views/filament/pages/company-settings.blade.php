<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="dth-config-form-footer">
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('action.save') }}
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
