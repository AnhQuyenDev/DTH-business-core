<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $page->page_title ?: $page->name }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f8fafc;color:#0f172a;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif}.card{width:min(680px,calc(100% - 32px));padding:36px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 18px 50px rgba(15,23,42,.08);text-align:center}.card h1{margin:0 0 12px;font-size:28px}.card p{margin:0;color:#475569;line-height:1.6}.back{display:inline-block;margin-top:24px;padding:10px 16px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
<main class="card">
    <h1>{{ \Dth\Marketing\Support\UiText::get('submission.thank_you_title', 'Thank you') }}</h1>
    <p>{{ $successMessage }}</p>
    <a class="back" href="{{ $page->publicUrl() }}">{{ \Dth\Marketing\Support\UiText::get('submission.back_to_page', 'Back') }}</a>
</main>
</body>
</html>
