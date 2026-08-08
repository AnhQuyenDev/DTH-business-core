<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        // Encrypted payloads are longer than the original secret. Store the
        // ciphertext in TEXT so a provider key can never be truncated.
        if (Schema::hasColumn('company_settings', 'vietqr_api_key')) {
            Schema::table('company_settings', function (Blueprint $table): void {
                $table->text('vietqr_api_key')->nullable()->change();
            });
        }

        $rows = DB::table('company_settings')
            ->whereNotNull('vietqr_api_key')
            ->where('vietqr_api_key', '!=', '')
            ->get(['id', 'vietqr_api_key']);

        foreach ($rows as $row) {
            $value = (string) $row->vietqr_api_key;

            try {
                Crypt::decryptString($value);
                continue;
            } catch (\Throwable) {
                // Existing value is plaintext. Encrypt it exactly once.
            }

            DB::table('company_settings')
                ->where('id', $row->id)
                ->update([
                    'vietqr_api_key' => Crypt::encryptString($value),
                ]);
        }
    }

    public function down(): void
    {
        // Secrets remain encrypted intentionally. Do not downgrade the column
        // back to VARCHAR because ciphertext may already be longer than 255.
    }
};
