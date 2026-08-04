<div class="space-y-4">
    <div class="rounded-lg bg-slate-50 p-4 border border-slate-200">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="font-medium text-slate-500">{{ __('field.subject') }}</dt>
                <dd class="mt-1 text-slate-900">{{ $subject }}</dd>
            </div>
            @if ($preheader)
            <div>
                <dt class="font-medium text-slate-500">{{ __('field.preheader') }}</dt>
                <dd class="mt-1 text-slate-900">{{ $preheader }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="rounded-lg overflow-hidden border border-slate-200">
        <div class="bg-slate-100 px-4 py-2 text-xs font-medium text-slate-500 uppercase tracking-wider border-b border-slate-200">
            {{ __('page.email_preview') }}
        </div>
        <iframe srcdoc='{!! str_replace("'", "&#039;", $html_body) !!}' style="width:100%;height:65vh;border:0;" title="{{ __('page.email_preview') }}"></iframe>
    </div>
</div>
