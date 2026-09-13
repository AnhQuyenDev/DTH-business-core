<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }}</title>
    <style>
        body{font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;margin:0;background:#f8fafc;color:#0f172a}
        .wrap{max-width:760px;margin:48px auto;padding:0 20px}.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:28px;box-shadow:0 8px 32px rgba(15,23,42,.06)}
        .meta{font-size:13px;color:#64748b;margin-bottom:24px}.dth-marketing-form-preview{display:grid;gap:16px}.dth-marketing-field{display:grid;gap:6px;font-weight:600}.dth-marketing-field input,.dth-marketing-field textarea,.dth-marketing-field select{font:inherit;font-weight:400;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff}.dth-marketing-checkbox{font-weight:500}.dth-marketing-submit{padding:11px 16px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:700;opacity:.65}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $template->name }}</h1>
        <div class="meta">{{ strtoupper($template->audience_type->value) }} · v{{ $template->version }} · {{ $template->status->value }}</div>
        {!! $preview !!}
    </div>
</div>
</body>
</html>
