@props(['model' => 'period'])
<div class="dth-period-filter">
    <span>{{ __('analytics.period') }}</span>
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model.live="{{ $model }}">
            <option value="7d">{{ __('analytics.period_7d') }}</option>
            <option value="30d">{{ __('analytics.period_30d') }}</option>
            <option value="90d">{{ __('analytics.period_90d') }}</option>
            <option value="ytd">{{ __('analytics.period_ytd') }}</option>
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>
