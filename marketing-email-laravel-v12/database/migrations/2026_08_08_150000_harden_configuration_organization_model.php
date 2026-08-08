<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('function_key', 50)->nullable()->after('name')->index();
            $table->string('color', 30)->default('gray')->after('function_key');
        });

        $defaults = [
            'admin' => ['function_key' => 'admin', 'color' => 'danger'],
            'marketing' => ['function_key' => 'marketing', 'color' => 'info'],
            'customer_service' => ['function_key' => 'customer_service', 'color' => 'warning'],
            'sales' => ['function_key' => 'sales', 'color' => 'success'],
            'technical' => ['function_key' => 'technical', 'color' => 'gray'],
            'email_service' => ['function_key' => 'other', 'color' => 'primary'],
            'finance' => ['function_key' => 'finance', 'color' => 'success'],
            'financial' => ['function_key' => 'finance', 'color' => 'success'],
            'accounting' => ['function_key' => 'finance', 'color' => 'success'],
            'tai_chinh' => ['function_key' => 'finance', 'color' => 'success'],
        ];

        foreach ($defaults as $code => $values) {
            DB::table('departments')
                ->where('code', $code)
                ->update($values);
        }

        DB::table('departments')
            ->whereIn('name', [
                "\u{0050}h\u{00f2}ng T\u{00e0}i ch\u{00ed}nh",
                "T\u{00e0}i ch\u{00ed}nh",
                'Finance',
            ])
            ->update([
                'function_key' => 'finance',
                'color' => 'success',
            ]);

        DB::table('departments')
            ->whereNull('function_key')
            ->update(['function_key' => 'other']);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role')->index();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        if (! DB::table('staff')->whereNull('user_id')->exists()) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['function_key']);
            $table->dropColumn(['function_key', 'color']);
        });
    }
};
