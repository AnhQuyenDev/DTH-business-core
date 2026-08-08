# ViewQuotation.php - thay riêng action gửi

Không ghi đè toàn bộ `ViewQuotation.php` nếu file local của bạn đã có UI preview email / người được phép xác nhận.

Trong `Action::make('send')`, giữ nguyên phần `->form([...])` hiện tại và thay **chỉ** phần `->action(...)` bằng:

```php
->action(function (array $data) use ($q): void {
    $log = app(\App\Services\Sales\QuotationMailService::class)->send(
        quotation: $q,
        user: auth()->user(),
        recipientEmail: (string) $data['recipient_email'],
        options: [
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'sending_account_id' => $data['sending_account_id'] ?? null,
        ],
    );

    $this->record->refresh();

    if ($log->status === \App\Enums\Sales\QuotationEmailStatus::Failed) {
        \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Gửi báo giá thất bại')
            ->body($log->error_message ?: 'Không thể gửi email bằng tài khoản gửi đã cấu hình.')
            ->persistent()
            ->send();

        return;
    }

    \Filament\Notifications\Notification::make()
        ->success()
        ->title(
            $log->status === \App\Enums\Sales\QuotationEmailStatus::Sent
                ? 'Đã gửi báo giá'
                : 'Báo giá đã được đưa vào hàng đợi gửi email'
        )
        ->body('Từ: '.($log->sender_email ?: '—').' → '.$log->recipient_email)
        ->send();
})
```

Không `redirect()` ngay sau send. Như vậy notification lỗi/thành công và tab Lịch sử gửi email cập nhật dễ kiểm tra hơn.
