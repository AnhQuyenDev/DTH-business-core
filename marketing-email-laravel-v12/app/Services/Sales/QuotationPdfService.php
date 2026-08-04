<?php

namespace App\Services\Sales;

use App\Enums\Sales\DocumentType;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotationPdfService
{
    public function generate(Quotation $quotation): QuotationDocument
    {
        $quotation->loadMissing(['items', 'customer', 'assignedStaff.user', 'bankAccount']);

        $html = view('sales.quotation-pdf', [
            'quotation' => $quotation,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => true,
        ]);

        $fileName = sprintf('%s-V%d.pdf', $quotation->quotation_code, $quotation->version);
        $filePath = sprintf('quotations/%s/%s', $quotation->quotation_code, $fileName);
        $fullPath = Storage::disk('local')->path($filePath);

        Storage::disk('local')->makeDirectory(dirname($filePath));
        $pdf->save($fullPath);

        return QuotationDocument::query()->create([
            'quotation_id' => $quotation->id,
            'version' => $quotation->version,
            'document_type' => DocumentType::Pdf,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => 'application/pdf',
            'file_size' => filesize($fullPath),
            'file_hash' => hash_file('sha256', $fullPath),
            'generated_by' => auth()->id(),
            'generated_at' => now(),
        ]);
    }

    public function regenerate(Quotation $quotation): QuotationDocument
    {
        $oldDocs = QuotationDocument::where('quotation_id', $quotation->id)
            ->where('document_type', DocumentType::Pdf)
            ->get();

        foreach ($oldDocs as $doc) {
            Storage::disk('local')->delete($doc->file_path);
            $doc->delete();
        }

        return $this->generate($quotation);
    }

    public function getLatestPdf(Quotation $quotation): ?QuotationDocument
    {
        return QuotationDocument::where('quotation_id', $quotation->id)
            ->where('document_type', DocumentType::Pdf)
            ->latest('id')
            ->first();
    }
}
