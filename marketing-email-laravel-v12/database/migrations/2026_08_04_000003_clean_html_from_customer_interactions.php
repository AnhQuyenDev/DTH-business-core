<?php

use App\Models\Crm\CustomerInteraction;
use App\Services\Sales\QuotationEmailCrmSyncer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $interactions = CustomerInteraction::where('interaction_type', 'email')->get();

        foreach ($interactions as $interaction) {
            $cleaned = QuotationEmailCrmSyncer::cleanHtmlForContent($interaction->content);

            if ($cleaned !== $interaction->content) {
                $interaction->update(['content' => $cleaned]);
            }
        }
    }

    public function down(): void
    {
        // No rollback needed — data cleanup is irreversible.
    }
};
