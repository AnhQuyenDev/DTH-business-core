<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('marketing.thank_you.title') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 48px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15,23,42,0.08);
        }
        .icon { font-size: 60px; margin-bottom: 16px; }
        h1 { font-size: 28px; color: #1d4ed8; margin-bottom: 12px; }
        p { font-size: 16px; color: #475569; line-height: 1.7; }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>{{ __('marketing.thank_you.heading') }}</h1>
        <p>{{ $successMessage }}</p>
        @if($landingPage)
            <a href="{{ route('marketing.landing-pages.public.show', $landingPage->slug) }}" class="back-link">← {{ __('marketing.thank_you.back') }}</a>
        @endif
    </div>
</body>
</html>
