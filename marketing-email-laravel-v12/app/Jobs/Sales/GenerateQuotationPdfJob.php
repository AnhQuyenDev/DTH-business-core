<?php

namespace App\Jobs\Sales;

use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQuotationPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function handle(QuotationPdfService $pdfService): void
    {
        $pdfService->regenerate($this->quotation);
    }
}
