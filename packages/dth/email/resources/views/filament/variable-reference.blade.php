<div class="space-y-5">
    <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
        {{ \Dth\Email\Support\UiText::get(
            'variables.help_intro',
            'Use these variables in the selected fields. They are replaced for each recipient when the campaign is sent.'
        ) }}
    </p>

    <div class="space-y-2">
        @foreach (($variables ?? []) as $variable => $definition)
            @php($token = '{'.'{'.$variable.'}}')
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm leading-6 dark:border-white/10 dark:bg-white/5">
                <code class="rounded-md bg-primary-100 px-2 py-1 font-bold text-primary-700 dark:bg-primary-400/15 dark:text-primary-300">{{ $token }}</code><span class="mx-1.5 font-bold text-gray-500 dark:text-gray-400">:</span><span class="font-bold text-gray-950 dark:text-white">{{ $definition['label'] }}</span><span class="text-gray-600 dark:text-gray-400"> &mdash; {{ $definition['description'] }}</span>
            </div>
        @endforeach
    </div>
</div>
