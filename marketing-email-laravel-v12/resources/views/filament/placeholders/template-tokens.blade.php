<div class="flex flex-wrap gap-1.5">
    @foreach($tokens as $token => $label)
        <button
            type="button"
            title="{{ $label }}"
            class="rounded-md border border-primary-300 bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 hover:bg-primary-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
            x-on:click="$dispatch('insert-template-token', { token: @js($token) })"
        >
            {{ $label }} <code class="font-mono text-primary-400">{!! $token !!}</code>
        </button>
    @endforeach
</div>
