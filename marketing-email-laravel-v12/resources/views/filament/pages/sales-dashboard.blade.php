<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.total_quotations') }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total']) }}</p>
            <p class="text-xs text-gray-400">{{ __('dashboard.sales.total_value') }}: {{ number_format($summary['total_value']) }} VND</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.sent') }}</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['sent']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.viewed') }}</p>
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($summary['viewed']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.accepted') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($summary['accepted']) }}</p>
            <p class="text-xs text-gray-400">{{ __('dashboard.sales.accepted_value') }}: {{ number_format($summary['accepted_value']) }} VND</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.accepted_unpaid') }}</p>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($summary['accepted_unpaid']) }}</p>
        </div>
    </div>

    {{-- Second row --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.draft') }}</p>
            <p class="text-xl font-bold text-gray-600">{{ number_format($summary['draft']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.pending_approval') }}</p>
            <p class="text-xl font-bold text-orange-600">{{ number_format($summary['pending_approval']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.rejected') }}</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($summary['rejected']) }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.expired') }}</p>
            <p class="text-xl font-bold text-gray-500">{{ number_format($summary['expired']) }}</p>
        </div>
    </div>

    {{-- Conversion rates --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.sent_to_viewed_rate') }}</p>
            <p class="text-2xl font-bold text-blue-600">{{ $conversion['sent_to_viewed'] }}%</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('dashboard.sales.viewed_to_accepted_rate') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $conversion['viewed_to_accepted'] }}%</p>
        </div>
    </div>

    {{-- Expiring soon alerts --}}
    @if($summary['expiring_soon'] > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <p class="text-amber-800 font-medium">@lang('dashboard.sales.expiring_soon_alert', ['count' => $summary['expiring_soon']])</p>
    </div>
    @endif

    {{-- Staff follow-up table --}}
    @if(count($followUp) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <h3 class="font-medium text-gray-900">{{ __('dashboard.sales.staff_follow_up') }}</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-4 py-2 text-gray-500 font-medium">{{ __('field.staff') }}</th>
                    <th class="px-4 py-2 text-gray-500 font-medium text-right">{{ __('dashboard.sales.pending_quotations') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($followUp as $item)
                <tr class="border-t border-gray-100">
                    <td class="px-4 py-2">{{ $item['staff'] }}</td>
                    <td class="px-4 py-2 text-right font-semibold">{{ $item['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
