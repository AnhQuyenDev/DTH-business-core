<!doctype html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('marketing.unsubscribe.title') }}</title>
    
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
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
            </svg>
        </div>
        
        <h2 class="mt-6 text-center text-2xl font-bold tracking-tight text-slate-900">{{ __('marketing.unsubscribe.heading') }}</h2>
        <p class="mt-2 text-center text-sm text-slate-500">
            {{ __('marketing.unsubscribe.description') }}
        </p>
    </div>

    <div class="mt-8 sm:mx-auto w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white px-6 py-8 shadow-sm ring-1 ring-slate-200 rounded-2xl sm:px-10">
            <div class="space-y-6">
                
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('marketing.unsubscribe.email_label') }}</label>
                    <div class="mt-2 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 border border-slate-100 select-all">
                        <span class="font-mono text-sm font-medium text-slate-700 break-all">{{ $recipient->email }}</span>
                    </div>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed text-center">
                    {{ __('marketing.unsubscribe.notice') }}
                </p>

                <form method="POST" action="{{ route('marketing.unsubscribe.store', ['token' => $token]) }}">
                    @csrf
                    <button type="submit" class="flex w-full justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900 transition-all active:scale-[0.98] cursor-pointer">
                        {{ __('marketing.unsubscribe.confirm') }}
                    </button>
                </form>
                
                <div class="text-center">
                    <a href="javascript:history.back()" class="text-xs font-medium text-slate-400 hover:text-slate-600 transition-colors">
                        {{ __('marketing.unsubscribe.back') }}
                    </a>
                </div>
                
            </div>
        </div>
    </div>

</body>
</html>