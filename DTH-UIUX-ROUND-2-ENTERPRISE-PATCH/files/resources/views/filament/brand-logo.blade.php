<div class="flex items-center gap-2.5 min-w-0">
    @if(company_logo_url())
        <img src="{{ company_logo_url() }}" alt="{{ company_name() }}" class="h-8 w-auto max-w-10 object-contain" />
    @else
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-500/10 text-xs font-bold text-primary-600 dark:text-primary-400">
            {{ mb_substr(company_name(), 0, 2) }}
        </div>
    @endif
    <span class="truncate text-sm font-bold tracking-tight text-gray-950 dark:text-white">{{ company_name() }}</span>
</div>
