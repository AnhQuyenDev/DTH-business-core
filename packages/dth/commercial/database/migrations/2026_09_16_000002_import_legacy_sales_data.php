<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! (bool) config('dth-commercial.legacy_import.enabled', true)) {
            return;
        }

        $this->importServices();
        $this->importPackages();
        $this->importOpportunities();
    }

    private function importServices(): void
    {
        if (! Schema::hasTable('services') || ! Schema::hasTable('commercial_services')) {
            return;
        }

        DB::table('services')->orderBy('id')->get()->each(function (object $row): void {
            DB::table('commercial_services')->updateOrInsert(
                ['service_code' => (string) $row->service_code],
                [
                    'name' => (string) $row->name,
                    'slug' => $row->slug ?? null,
                    'description' => $row->description ?? null,
                    'default_scope' => $row->default_scope ?? null,
                    'default_terms' => $row->default_terms ?? null,
                    'status' => (string) ($row->status ?? 'active'),
                    'sort_order' => (int) ($row->sort_order ?? 0),
                    'legacy_sales_id' => (int) $row->id,
                    'metadata' => json_encode(['legacy_table' => 'services']),
                    'created_by' => $row->created_by ?? null,
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );
        });
    }

    private function importPackages(): void
    {
        if (! Schema::hasTable('service_packages') || ! Schema::hasTable('commercial_service_packages')) {
            return;
        }

        DB::table('service_packages')->orderBy('id')->get()->each(function (object $row): void {
            $serviceId = DB::table('commercial_services')->where('legacy_sales_id', $row->service_id)->value('id');
            if (! $serviceId) {
                return;
            }

            DB::table('commercial_service_packages')->updateOrInsert(
                ['package_code' => (string) $row->package_code],
                [
                    'service_id' => $serviceId,
                    'name' => (string) $row->name,
                    'description' => $row->description ?? null,
                    'audience_type' => (string) ($row->audience_type ?? 'both'),
                    'billing_period' => $row->billing_period ?? null,
                    'billing_period_unit' => $row->billing_period_unit ?? null,
                    'unit' => (string) ($row->unit ?? 'package'),
                    'default_quantity' => (int) ($row->default_quantity ?? 1),
                    'status' => (string) ($row->status ?? 'active'),
                    'sort_order' => (int) ($row->sort_order ?? 0),
                    'legacy_sales_id' => (int) $row->id,
                    'metadata' => json_encode(['legacy_table' => 'service_packages']),
                    'created_by' => $row->created_by ?? null,
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );
        });
    }

    private function importOpportunities(): void
    {
        if (! Schema::hasTable('sales_opportunities') || ! Schema::hasTable('commercial_opportunities')) {
            return;
        }

        DB::table('sales_opportunities')->orderBy('id')->get()->each(function (object $row): void {
            $service = null;
            if (! empty($row->service_interest)) {
                $service = DB::table('commercial_services')
                    ->where('service_code', $row->service_interest)
                    ->orWhere('slug', $row->service_interest)
                    ->first();
            }

            DB::table('commercial_opportunities')->updateOrInsert(
                ['opportunity_code' => (string) $row->opportunity_code],
                [
                    'lead_reference' => isset($row->lead_id) && $row->lead_id ? (string) $row->lead_id : null,
                    'contact_reference' => isset($row->primary_contact_id) && $row->primary_contact_id ? (string) $row->primary_contact_id : null,
                    'company_reference' => isset($row->company_id) && $row->company_id ? (string) $row->company_id : null,
                    'assigned_employee_reference' => isset($row->assigned_staff_id) && $row->assigned_staff_id ? (string) $row->assigned_staff_id : null,
                    'service_id' => $service?->id,
                    'service_reference' => $service?->slug ?? $service?->service_code ?? ($row->service_interest ?? null),
                    'service_name_snapshot' => $service?->name,
                    'title' => (string) $row->title,
                    'stage' => (string) ($row->stage ?? 'qualified'),
                    'estimated_value' => $row->estimated_value ?? null,
                    'probability' => (int) ($row->probability ?? 50),
                    'expected_close_date' => $row->expected_close_date ?? null,
                    'won_at' => $row->won_at ?? null,
                    'lost_at' => $row->lost_at ?? null,
                    'lost_reason' => $row->lost_reason ?? null,
                    'legacy_sales_id' => (int) $row->id,
                    'metadata' => json_encode(['legacy_table' => 'sales_opportunities', 'legacy_metadata' => isset($row->metadata) ? json_decode((string) $row->metadata, true) : null]),
                    'created_by' => $row->created_by ?? null,
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ],
            );
        });
    }

    public function down(): void
    {
        // Imported records are deliberately retained. Rolling this migration
        // back must not delete business data that may already be in use.
    }
};
