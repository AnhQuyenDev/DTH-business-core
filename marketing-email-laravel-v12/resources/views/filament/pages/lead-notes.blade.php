<div class="space-y-4 p-4">
    @forelse($notes as $note)
        <div class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <x-filament::badge>{{ __('field.note_type.' . $note->note_type) }}</x-filament::badge>
                    @if($note->outcome)
                        <x-filament::badge color="{{ in_array($note->outcome, ['no_need', 'unreachable', 'invalid_information']) ? 'danger' : 'success' }}">
                            {{ $note->outcome }}
                        </x-filament::badge>
                    @endif
                </div>
                <span class="text-sm text-gray-500">
                    {{ $note->staff?->full_name ?? __('common.not_available') }} -
                    {{ $note->created_at?->format('H:i d/m/Y') }}
                </span>
            </div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $note->content }}</p>
            @if($note->next_follow_up_at)
                <div class="mt-2 text-xs text-warning-600">
                    {{ __('field.follow_up') }}: {{ $note->next_follow_up_at->format('H:i d/m/Y') }}
                </div>
            @endif
        </div>
    @empty
        <p class="text-center text-gray-500 py-8">{{ __('page.lead_notes.empty') }}</p>
    @endforelse
</div>
