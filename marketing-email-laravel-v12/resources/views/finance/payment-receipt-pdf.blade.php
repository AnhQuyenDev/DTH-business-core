<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $snapshot['receipt_code'] }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; line-height: 1.55; }
        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 14px; margin-bottom: 22px; }
        .header td { vertical-align: top; }
        .logo { max-width: 180px; max-height: 58px; margin-bottom: 8px; }
        .company { font-size: 16px; font-weight: bold; color: #1d4ed8; }
        .title { font-size: 23px; font-weight: bold; color: #1d4ed8; text-align: right; }
        .code { text-align: right; margin-top: 6px; }
        .box { border: 1px solid #d1d5db; border-radius: 5px; padding: 14px; margin-bottom: 16px; }
        .row { margin: 5px 0; }
        .label { color: #6b7280; }
        .amount { font-size: 20px; font-weight: bold; color: #15803d; }
        .status { display: inline-block; padding: 5px 10px; background: #dcfce7; color: #166534; font-weight: bold; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td style="width:62%">
            @if(!empty($snapshot['company']['logo_data_uri']))
                <img class="logo" src="{{ $snapshot['company']['logo_data_uri'] }}" alt="Logo">
            @endif
            <div class="company">{{ $snapshot['company']['name'] }}</div>
            @if($snapshot['company']['address'])<div>{{ $snapshot['company']['address'] }}</div>@endif
            @if($snapshot['company']['phone'])<div>Hotline: {{ $snapshot['company']['phone'] }}</div>@endif
            @if($snapshot['company']['email'])<div>Email: {{ $snapshot['company']['email'] }}</div>@endif
            @if($snapshot['company']['tax_code'])<div>MST: {{ $snapshot['company']['tax_code'] }}</div>@endif
        </td>
        <td style="width:38%">
            <div class="title">PHIẾU XÁC NHẬN THANH TOÁN</div>
            <div class="code">Số: <strong>{{ $snapshot['receipt_code'] }}</strong></div>
        </td>
    </tr>
</table>

<div class="box">
    <div class="row"><span class="label">Khách hàng:</span> <strong>{{ $snapshot['customer']['name'] ?? '—' }}</strong></div>
    @if(!empty($snapshot['customer']['code']))<div class="row"><span class="label">Mã khách hàng:</span> {{ $snapshot['customer']['code'] }}</div>@endif
    @if(!empty($snapshot['customer']['email']))<div class="row"><span class="label">Email:</span> {{ $snapshot['customer']['email'] }}</div>@endif
    <div class="row"><span class="label">Báo giá:</span> {{ $snapshot['quotation']['code'] }}-V{{ $snapshot['quotation']['version'] }}</div>
    <div class="row"><span class="label">Nội dung:</span> {{ $snapshot['quotation']['title'] }}</div>
</div>

<div class="box">
    <div class="row"><span class="label">Mã thanh toán:</span> <strong>{{ $snapshot['payment']['payment_code'] }}</strong></div>
    <div class="row"><span class="label">Số tiền đã nhận:</span></div>
    <div class="amount">{{ number_format($snapshot['payment']['amount'], 0, ',', '.') }} {{ $snapshot['payment']['currency'] }}</div>
    <div class="row"><span class="label">Doanh thu trước VAT:</span> {{ number_format($snapshot['payment']['net_amount'], 0, ',', '.') }} {{ $snapshot['payment']['currency'] }}</div>
    <div class="row"><span class="label">VAT:</span> {{ number_format($snapshot['payment']['tax_amount'], 0, ',', '.') }} {{ $snapshot['payment']['currency'] }}</div>
    <div class="row"><span class="label">Hình thức:</span> Chuyển khoản ngân hàng</div>
    @if(!empty($snapshot['payment']['transfer_reference']))<div class="row"><span class="label">Mã giao dịch / tham chiếu:</span> {{ $snapshot['payment']['transfer_reference'] }}</div>@endif
    @if(!empty($snapshot['bank']['bank_name']))<div class="row"><span class="label">Tài khoản nhận:</span> {{ $snapshot['bank']['bank_name'] }} - {{ $snapshot['bank']['account_number'] }} - {{ $snapshot['bank']['account_name'] }}</div>@endif
    <div class="row"><span class="label">Ngày thanh toán:</span> {{ \Illuminate\Support\Carbon::parse($snapshot['payment']['paid_at'])->format('d/m/Y H:i') }}</div>
    <div class="row"><span class="label">Xác minh lúc:</span> {{ \Illuminate\Support\Carbon::parse($snapshot['payment']['verified_at'])->format('d/m/Y H:i') }}</div>
    <div class="row"><span class="label">Người đối soát:</span> {{ $snapshot['payment']['verified_by'] ?? '—' }}</div>
    <div class="row" style="margin-top:12px"><span class="status">ĐÃ XÁC NHẬN THANH TOÁN</span></div>
</div>

<div class="footer">
    Phiếu này xác nhận hệ thống của {{ $snapshot['company']['name'] }} đã ghi nhận khoản thanh toán nêu trên.
    Phiếu xác nhận thanh toán này không thay thế hóa đơn điện tử/hóa đơn VAT theo quy định pháp luật.
</div>
</body>
</html>
