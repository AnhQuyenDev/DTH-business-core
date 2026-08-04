<?php

namespace App\Models\Sales;

use App\Enums\Sales\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'version',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'file_hash',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'generated_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
