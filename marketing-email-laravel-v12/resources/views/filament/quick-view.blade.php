<div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
    @foreach($rows as $row)
        <div class="min-w-0 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</div>
            <div class="mt-1 break-words text-sm font-medium text-gray-950 dark:text-white">{{ $row['value'] }}</div>
        </div>
    @endforeach
</div>
