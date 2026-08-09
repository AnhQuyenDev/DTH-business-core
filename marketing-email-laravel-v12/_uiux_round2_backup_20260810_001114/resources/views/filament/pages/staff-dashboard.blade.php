<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament-widgets::widgets
            :columns="1"
            :data="$this->getWidgetData()"
            :widgets="$this->getWidgets()"
        />
    </div>
</x-filament-panels::page>
