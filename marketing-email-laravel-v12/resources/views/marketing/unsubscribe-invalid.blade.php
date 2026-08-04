<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('marketing.unsubscribe_invalid.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto w-full sm:max-w-md">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
        </div>
        <h2 class="mt-6 text-center text-2xl font-bold tracking-tight text-slate-900">{{ __('marketing.unsubscribe_invalid.heading') }}</h2>
        <p class="mt-2 text-center text-sm text-slate-500">
            {{ __('marketing.unsubscribe_invalid.description') }}
        </p>
        <div class="mt-8 text-center">
            <a href="{{ url('/') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors underline">
                {{ __('marketing.unsubscribe_invalid.home') }}
            </a>
        </div>
    </div>
</body>
</html>
