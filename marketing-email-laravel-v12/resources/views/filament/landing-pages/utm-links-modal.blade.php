<x-filament::section>
    @if ($utmUrls->isEmpty())
        <div class="fi-ta-empty-state px-2 py-5 text-center text-sm text-gray-500">
            {{ __('field.report_no_data') }}
        </div>
    @else
        <div class="w-full overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/10 text-sm">
                <colgroup>
                    <col style="width: 22%;">
                    <col style="width: 22%;">
                    <col style="width: 36%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-semibold text-gray-700 dark:text-gray-200">{{ __('field.utm_col_source') }}</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-gray-700 dark:text-gray-200">{{ __('field.utm_col_medium') }}</th>
                        <th class="px-3 py-2.5 text-left font-semibold text-gray-700 dark:text-gray-200">{{ __('field.utm_col_campaign') }}</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-700 dark:text-gray-200">{{ __('field.utm_col_url') }}</th>
                        <th class="px-3 py-2.5 text-right font-semibold text-gray-700 dark:text-gray-200">{{ __('field.utm_col_ops') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10 bg-white dark:bg-transparent">
                    @foreach ($utmUrls as $utmUrl)
                        <tr id="utm-row-{{ $utmUrl->id }}">
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">
                                <div class="truncate" title="{{ $utmUrl->utm_source }}">{{ $utmUrl->utm_source ?: __('common.not_available') }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">
                                <div class="truncate" title="{{ $utmUrl->utm_medium }}">{{ $utmUrl->utm_medium ?: __('common.not_available') }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-700 dark:text-gray-200">
                                <div class="truncate" title="{{ $utmUrl->utm_campaign }}">{{ $utmUrl->utm_campaign ?: __('common.not_available') }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="info"
                                    data-url="{{ $utmUrl->url }}"
                                    data-copy-title="{{ __('notification.url_copied') }}"
                                    data-copy-failed="{{ __('notification.failed') }}"
                                    data-copy-fallback="{{ __('notification.copy_fallback') }}"
                                    onclick="
                                        const text = this.dataset.url || '';
                                        const copiedTitle = this.dataset.copyTitle || '';
                                        const failedTitle = this.dataset.copyFailed || '';
                                        const fallbackTitle = this.dataset.copyFallback || '';
                                        const notify = (ok, title) => {
                                            if (title && window.Livewire) {
                                                window.Livewire.dispatch('notificationSent', { title: title, status: ok ? 'success' : 'danger', duration: 3000 });
                                            }
                                        };
                                        const fallbackCopy = (value) => {
                                            const area = document.createElement('textarea');
                                            area.value = value;
                                            area.setAttribute('readonly', '');
                                            area.style.position = 'fixed';
                                            area.style.opacity = '0';
                                            document.body.appendChild(area);
                                            area.focus();
                                            area.select();
                                            let ok = false;
                                            try {
                                                ok = document.execCommand('copy');
                                            } catch (e) {
                                                ok = false;
                                            }
                                            document.body.removeChild(area);
                                            return ok;
                                        };
                                        const copyNow = async () => {
                                            let ok = false;
                                            try {
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    await navigator.clipboard.writeText(text);
                                                    ok = true;
                                                } else {
                                                    ok = fallbackCopy(text);
                                                }
                                            } catch (e) {
                                                ok = fallbackCopy(text);
                                            }
                                            if (!ok && fallbackTitle) {
                                                notify(false, fallbackTitle);
                                                return;
                                            }
                                            notify(ok, ok ? copiedTitle : failedTitle);
                                        };
                                        copyNow();
                                    "
                                >
                                    {{ __('action.copy_url') }}
                                </x-filament::button>
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap">
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="danger"
                                    data-delete-url="{{ route('marketing.landing-pages.utm-urls.destroy', ['landingPage' => $record, 'utmUrl' => $utmUrl]) }}"
                                    data-row-id="utm-row-{{ $utmUrl->id }}"
                                    data-delete-title="{{ __('notification.deleted') }}"
                                    data-delete-failed="{{ __('notification.failed') }}"
                                    data-confirm-text="{{ __('notification.confirm_delete') }}"
                                    onclick="
                                        const deleteUrl = this.dataset.deleteUrl;
                                        const rowId = this.dataset.rowId;
                                        const okTitle = this.dataset.deleteTitle || '';
                                        const failTitle = this.dataset.deleteFailed || '';
                                        const confirmText = this.dataset.confirmText || '';
                                        if (!deleteUrl || !rowId || !confirm(confirmText)) {
                                            return;
                                        }
                                        const notify = (ok, title) => {
                                            if (title && window.Livewire) {
                                                window.Livewire.dispatch('notificationSent', { title: title, status: ok ? 'success' : 'danger', duration: 3000 });
                                            }
                                        };
                                        fetch(deleteUrl, {
                                            method: 'DELETE',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json',
                                            },
                                        })
                                            .then((response) => {
                                                if (!response.ok) {
                                                    throw new Error();
                                                }
                                                const row = document.getElementById(rowId);
                                                if (row) {
                                                    row.remove();
                                                }
                                                notify(true, okTitle);
                                            })
                                            .catch(() => notify(false, failTitle));
                                    "
                                >
                                    {{ __('action.delete') }}
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>