<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('bank_accounts')
            || ! Schema::hasTable('quotations')
        ) {
            return;
        }

        $bank = DB::table('bank_accounts')
            ->where('is_default', true)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($bank === null) {
            // Deliberately do not pick an arbitrary bank account. Existing
            // quotations stay blocked until Finance/Admin chooses a default.
            return;
        }

        DB::table('quotations')
            ->whereNull('bank_account_id')
            ->whereNull('sent_at')
            ->whereIn('status', [
                'draft',
                'pending_approval',
                'approved',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($quotations) use ($bank): void {
                foreach ($quotations as $quotation) {
                    $snapshot = [
                        'bank_account_id' => $bank->id,
                        'bank_code' => $bank->bank_code,
                        'bank_name' => $bank->bank_name,
                        'account_number' => $bank->account_number,
                        'account_name' => $bank->account_name,
                        'branch_name' => $bank->branch_name,
                        'swift_code' => $bank->swift_code,
                        'qr_provider' => $bank->qr_provider,
                        'qr_template' => $bank->qr_template,
                        'transfer_content' => $quotation->quotation_code,
                    ];

                    DB::table('quotations')
                        ->where('id', $quotation->id)
                        ->update([
                            'bank_account_id' => $bank->id,
                            'payment_snapshot' => json_encode(
                                $snapshot,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                            ),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill is intentionally irreversible. Reverting it could
        // remove payment information from already prepared commercial drafts.
    }
};
