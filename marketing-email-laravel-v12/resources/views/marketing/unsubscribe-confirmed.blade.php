<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('marketing.unsubscribe_confirmed.title') }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="mt-8 sm:mx-auto w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white px-6 py-10 shadow-sm ring-1 ring-slate-200 rounded-2xl sm:px-10 text-center">
            
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            
            <h1 class="mt-6 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                {{ __('marketing.unsubscribe_confirmed.heading') }}
            </h1>
            
            <p class="mt-3 text-sm text-slate-500 leading-relaxed">
                {{ __('marketing.unsubscribe_confirmed.description') }}
            </p>
            
            <div class="mt-4 inline-block rounded-lg bg-slate-50 px-4 py-2 border border-slate-100 max-w-full">
                <span class="font-mono text-xs font-semibold text-slate-600 break-all select-all">
                    {{ $recipient->email }}
                </span>
            </div>

            <div class="my-8 border-t border-slate-100"></div>

            <p class="text-xs text-slate-400">
                {{ __('marketing.unsubscribe_confirmed.footer_prefix') }} <a href="{{ url('/') }}" class="text-slate-600 hover:text-slate-800 underline">{{ __('marketing.unsubscribe_confirmed.home') }}</a>.
            </p>
            
        </div>
    </div>

</body>
</html>