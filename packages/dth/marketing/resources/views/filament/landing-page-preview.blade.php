<div style="max-height: 70vh; overflow:auto; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
    @if($record->headline)<h2 style="font-size:1.75rem;font-weight:700;margin-bottom:.5rem">{{ $record->headline }}</h2>@endif
    @if($record->subheadline)<p style="color:#6b7280;margin-bottom:1rem">{{ $record->subheadline }}</p>@endif
    @if($record->html_body){!! $record->html_body !!}@elseif($record->content)<p>{!! nl2br(e($record->content)) !!}</p>@endif
</div>
