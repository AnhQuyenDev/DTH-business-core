<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->quotation_code }}-V{{ $quotation->version }}</title>
    <style>
        @page { margin: 24px; }
        body { margin: 0; background: #fff; }
    </style>
</head>
<body>
    @include('sales.partials.quotation-document', [
        'quotation' => $quotation,
        'interactive' => false,
    ])
</body>
</html>
