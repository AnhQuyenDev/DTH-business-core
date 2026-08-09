<div class="space-y-4">
    @if($notice)
        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4 text-sm space-y-1">
            <div><strong>Người chuyển:</strong> {{ $notice->payer_name }}</div>
            <div><strong>Email:</strong> {{ $notice->payer_email }}</div>
            <div><strong>Số tiền khai báo:</strong> {{ number_format((float) $notice->declared_amount, 0, ',', '.') }} VND</div>
            <div><strong>Mã giao dịch:</strong> {{ $notice->transfer_reference ?: '—' }}</div>
            @if($notice->note)<div><strong>Ghi chú:</strong> {{ $notice->note }}</div>@endif
            <div><strong>Gửi lúc:</strong> {{ $notice->submitted_at?->format('d/m/Y H:i') }}</div>
        </div>

        <div class="space-y-3">
            <div class="font-semibold">Chứng từ khách hàng tải lên</div>
            @forelse($notice->files as $file)
                @php($evidenceUrl = route('finance.payment-evidence', ['file' => $file]))
                <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ $file->original_name }}</div>
                            <div class="text-xs text-gray-500">{{ $file->mime_type }} • {{ number_format($file->file_size / 1024, 1) }} KB</div>
                            <div class="text-[11px] text-gray-400">SHA-256: {{ $file->sha256 }}</div>
                        </div>
                        <a href="{{ $evidenceUrl }}" target="_blank" class="text-primary-600 text-sm font-medium">Mở bản gốc</a>
                    </div>

                    @if(str_starts_with((string) $file->mime_type, 'image/'))
                        <a href="{{ $evidenceUrl }}" target="_blank" class="block">
                            <img src="{{ $evidenceUrl }}" alt="{{ $file->original_name }}"
                                 class="mx-auto max-h-80 max-w-full rounded border border-gray-200 object-contain dark:border-white/10">
                        </a>
                    @elseif($file->mime_type === 'application/pdf')
                        <div class="rounded bg-gray-50 p-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            Chứng từ PDF — bấm <strong>Mở bản gốc</strong> để đối chiếu đầy đủ.
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-sm text-danger-600">Không có chứng từ. Không nên xác nhận Paid.</div>
            @endforelse
        </div>
    @else
        <div class="text-sm text-gray-500">Chưa có thông báo thanh toán.</div>
    @endif
</div>
