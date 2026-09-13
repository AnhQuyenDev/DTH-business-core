@php
    $theme = array_replace(\Dth\Marketing\Models\LandingPage::defaultTheme(), (array) ($page->theme_tokens ?? []));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->page_title ?: $page->name }}</title>
    <style>
        :root{--lp-primary:{{ $theme['primary'] }};--lp-primary-hover:{{ $theme['primary_hover'] }};--lp-bg:{{ $theme['background'] }};--lp-surface:{{ $theme['surface'] }};--lp-text:{{ $theme['text'] }};--lp-muted:{{ $theme['muted_text'] }};--lp-border:{{ $theme['border'] }};--lp-danger:{{ $theme['danger'] }};--lp-radius:{{ $theme['radius'] }}}
        *{box-sizing:border-box}body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;background:var(--lp-bg);color:var(--lp-text)}.lp-shell{max-width:1100px;margin:0 auto;padding:56px 22px}.lp-hero{padding:28px 0 36px}.lp-hero h1{font-size:clamp(34px,5vw,64px);line-height:1.05;margin:0 0 16px}.lp-sub{font-size:20px;color:var(--lp-muted);max-width:760px}.lp-cta{display:inline-block;margin-top:20px;padding:12px 18px;border-radius:var(--lp-radius);background:var(--lp-primary);color:#fff;text-decoration:none;font-weight:700}.lp-content{background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:var(--lp-radius);padding:28px;margin:18px 0}.lp-forms{display:block;width:100%;margin-top:24px}.lp-form-slot{display:block;width:100%}.lp-form-slot+.lp-form-slot{margin-top:28px}.dth-marketing-form-document-root{display:block;width:100%}.dth-marketing-form-preview{display:grid;gap:14px}.dth-marketing-field{display:grid;gap:6px;font-weight:600}.dth-marketing-field input,.dth-marketing-field textarea,.dth-marketing-field select{font:inherit;font-weight:400;padding:10px 12px;border:1px solid var(--lp-border);border-radius:var(--lp-radius);background:#fff;color:var(--lp-text)}.dth-marketing-checkbox{font-weight:500}.dth-marketing-submit{padding:11px 16px;border:0;border-radius:var(--lp-radius);background:var(--lp-primary);color:#fff;font-weight:700;opacity:.65}.lp-preview{position:fixed;right:16px;top:16px;background:#111827;color:#fff;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:700;z-index:9999}
        {!! $page->css_body !!}
    </style>
    {!! $formAssets ?? '' !!}
</head>
<body>
@if($previewMode)
    <div class="lp-preview">Preview</div>
@endif
<div class="lp-shell">
    @if($page->headline || $page->subheadline || $page->cta_text)
        <section class="lp-hero">
            @if($page->headline)<h1>{{ $page->headline }}</h1>@endif
            @if($page->subheadline)<div class="lp-sub">{{ $page->subheadline }}</div>@endif
            @if($page->cta_text)<a class="lp-cta" href="#lead-forms">{{ $page->cta_text }}</a>@endif
        </section>
    @endif

    @if($page->html_body)
        <section class="lp-content">{!! $page->html_body !!}</section>
    @elseif($page->content)
        <section class="lp-content">{!! nl2br(e($page->content)) !!}</section>
    @endif

    @if($personalForm || $businessForm)
        <section id="lead-forms" class="lp-forms">
            @if($personalForm)
                <div class="lp-form-slot" data-form-type="personal">{!! $personalForm !!}</div>
            @endif
            @if($businessForm)
                <div class="lp-form-slot" data-form-type="business">{!! $businessForm !!}</div>
            @endif
        </section>
    @endif
</div>
</body>
</html>
