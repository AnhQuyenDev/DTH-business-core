<?php

use App\Models\Crm\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name', 255)->nullable()->after('display_name');
            $table->string('last_name', 255)->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->string('customer_ward', 100)->nullable()->after('gender');
            $table->string('customer_district', 100)->nullable()->after('customer_ward');
            $table->string('customer_province', 100)->nullable()->after('customer_district');
            $table->string('customer_country', 100)->nullable()->after('customer_province');
            $table->string('occupation', 150)->nullable()->after('customer_country');

            $table->string('company_name', 255)->nullable()->after('occupation');
            $table->string('tax_code', 30)->nullable()->after('company_name');
            $table->string('company_address', 500)->nullable()->after('tax_code');
            $table->string('company_ward', 100)->nullable()->after('company_address');
            $table->string('company_province', 100)->nullable()->after('company_ward');
            $table->string('legal_representative', 255)->nullable()->after('company_province');
            $table->string('contact_position', 150)->nullable()->after('legal_representative');
            $table->string('business_email', 255)->nullable()->after('contact_position');
            $table->string('business_phone', 30)->nullable()->after('business_email');
            $table->string('industry', 150)->nullable()->after('business_phone');

            $table->string('company_group_id', 36)->nullable()->after('metadata');
            $table->index('company_group_id');
        });

        DB::statement('UPDATE customers
            SET
                first_name = (SELECT pcp.first_name FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                last_name = (SELECT pcp.last_name FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                date_of_birth = (SELECT pcp.date_of_birth FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                gender = (SELECT pcp.gender FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                customer_ward = (SELECT pcp.ward FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                customer_district = (SELECT pcp.district FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                customer_province = (SELECT pcp.province FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                customer_country = (SELECT pcp.country FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id),
                occupation = (SELECT pcp.occupation FROM personal_contact_profiles pcp WHERE pcp.contact_id = customers.contact_id)
            WHERE customer_type = "personal" OR customer_type IS NULL');

        DB::statement('UPDATE customers
            SET
                company_name = (SELECT bcp.company_name FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                tax_code = (SELECT bcp.tax_code FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                company_address = (SELECT bcp.company_address FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                company_ward = (SELECT bcp.ward FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                company_province = (SELECT bcp.province FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                legal_representative = (SELECT bcp.legal_representative FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                contact_position = (SELECT bcp.contact_position FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                business_email = (SELECT bcp.business_email FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                business_phone = (SELECT bcp.business_phone FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id),
                industry = (SELECT bcp.industry FROM business_contact_profiles bcp WHERE bcp.contact_id = customers.contact_id)
            WHERE customer_type = "business"');

        // Generate company_group_id for business customers with same company_name + tax_code
        $groups = Customer::whereNotNull('company_name')
            ->where('customer_type', 'business')
            ->select('company_name', 'tax_code', DB::raw('COUNT(*) as cnt'))
            ->groupBy('company_name', 'tax_code')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $groupId = (string) Str::uuid();
            Customer::where('company_name', $group->company_name)
                ->where('tax_code', $group->tax_code)
                ->update(['company_group_id' => $groupId]);
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['company_group_id']);
            $table->dropColumn([
                'first_name', 'last_name', 'date_of_birth', 'gender',
                'customer_ward', 'customer_district', 'customer_province', 'customer_country',
                'occupation', 'company_name', 'tax_code', 'company_address',
                'company_ward', 'company_province', 'legal_representative',
                'contact_position', 'business_email', 'business_phone', 'industry',
                'company_group_id',
            ]);
        });
    }
};
