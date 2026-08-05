<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('employee_code')->constrained('departments')->nullOnDelete();
        });

        $rows = DB::table('staff')->select('id', 'department')->get();

        foreach ($rows as $row) {
            $departmentId = null;

            if ($row->department !== null) {
                $departmentId = DB::table('departments')->where('code', $row->department)->value('id');
            }

            DB::table('staff')->where('id', $row->id)->update(['department_id' => $departmentId]);
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex('staff_department_index');
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('department', 50)->nullable()->after('department_id');
        });

        $rows = DB::table('staff')->select('id', 'department_id')->get();

        foreach ($rows as $row) {
            $code = null;

            if ($row->department_id !== null) {
                $code = DB::table('departments')->where('id', $row->department_id)->value('code');
            }

            DB::table('staff')->where('id', $row->id)->update(['department' => $code]);
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->index('department');
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
