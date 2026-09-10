<div class="space-y-5">
    @if ($preview->missingVariables !== [])
        <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800">
            <strong>Missing preview variables:</strong>
            {{ implode(', ', $preview->missingVariables) }}
        </div>
    @endif

    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Subject</div>
        <div class="mt-1 text-base font-semibold">{{ $preview->subject }}</div>
    </div>

    @if ($preview->preheader)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Preheader</div>
            <div class="mt-1 text-sm text-gray-700">{{ $preview->preheader }}</div>
        </div>
    @endif

    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">HTML preview</div>
        <div class="rounded-lg border bg-white p-6 text-gray-900">
            {!! str($preview->htmlBody)->sanitizeHtml() !!}
        </div>
    </div>

    @if ($preview->textBody)
        <div>
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Plain text</div>
            <pre class="whitespace-pre-wrap rounded-lg border bg-gray-50 p-4 text-sm">{{ $preview->textBody }}</pre>
        </div>
    @endif
</div>
