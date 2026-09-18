<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'phone')) $table->string('phone', 30)->nullable()->after('email');
                if (! Schema::hasColumn('users', 'timezone')) $table->string('timezone', 80)->nullable()->after('password');
                if (! Schema::hasColumn('users', 'account_status')) $table->string('account_status', 30)->default('active')->index()->after('timezone');
                if (! Schema::hasColumn('users', 'must_change_password')) $table->boolean('must_change_password')->default(false)->after('account_status');
                if (! Schema::hasColumn('users', 'locked_until')) $table->timestamp('locked_until')->nullable()->index()->after('must_change_password');
                if (! Schema::hasColumn('users', 'last_login_at')) $table->timestamp('last_login_at')->nullable()->after('locked_until');
                if (! Schema::hasColumn('users', 'last_login_ip')) $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
                if (! Schema::hasColumn('users', 'failed_login_attempts')) $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('last_login_ip');
                if (! Schema::hasColumn('users', 'account_metadata')) $table->json('account_metadata')->nullable()->after('failed_login_attempts');
            });
        }

        Schema::create('account_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 30)->default('gray');
            $table->string('data_scope', 20)->default('own')->index();
            $table->boolean('is_system')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('account_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 160)->unique();
            $table->string('module', 80)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('account_role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('account_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('account_permission_role', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained('account_permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('account_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('account_user_permissions', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('account_permissions')->cascadeOnDelete();
            $table->string('effect', 10)->default('allow');
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
        });

        Schema::create('account_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('account_groups')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('type', 30)->default('team')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('account_group_user', function (Blueprint $table): void {
            $table->foreignId('group_id')->constrained('account_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['group_id', 'user_id']);
        });

        Schema::create('account_invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('account_invitation_role', function (Blueprint $table): void {
            $table->foreignId('invitation_id')->constrained('account_invitations')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('account_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['invitation_id', 'role_id']);
        });

        Schema::create('account_invitation_group', function (Blueprint $table): void {
            $table->foreignId('invitation_id')->constrained('account_invitations')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('account_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['invitation_id', 'group_id']);
        });

        Schema::create('account_audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module', 80)->index();
            $table->string('event', 80)->index();
            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id', 100)->nullable()->index();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['module', 'created_at']);
        });

        Schema::create('account_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 160)->unique();
            $table->json('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ((array) config('dth-account-management.permissions', []) as $key => $definition) {
            DB::table('account_permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'module' => $definition['module'] ?? explode('.', $key)[0],
                    'name' => $definition['name'] ?? $key,
                    'description' => $definition['description'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        DB::table('account_roles')->insert([
            ['key' => 'super-admin', 'name' => 'Super Admin', 'description' => 'Toàn quyền quản trị DTH Business Core.', 'color' => 'danger', 'data_scope' => 'all', 'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'administrator', 'name' => 'Administrator', 'description' => 'Quản trị người dùng và các module nghiệp vụ.', 'color' => 'primary', 'data_scope' => 'all', 'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'viewer', 'name' => 'Viewer', 'description' => 'Chỉ xem dữ liệu được phép.', 'color' => 'gray', 'data_scope' => 'own', 'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $superId = DB::table('account_roles')->where('key', 'super-admin')->value('id');
        $adminId = DB::table('account_roles')->where('key', 'administrator')->value('id');
        $viewerId = DB::table('account_roles')->where('key', 'viewer')->value('id');
        $allPermissions = DB::table('account_permissions')->pluck('id');
        foreach ([$superId, $adminId] as $roleId) {
            foreach ($allPermissions as $permissionId) {
                DB::table('account_permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $viewPermissions = DB::table('account_permissions')->where(function ($query): void {
            $query->where('key', 'like', '%.view')->orWhere('key', 'like', '%.view-%')->orWhere('key', 'accounts.audit.view');
        })->pluck('id');
        foreach ($viewPermissions as $permissionId) {
            DB::table('account_permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $viewerId, 'created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('account_settings')->insert([
            ['key' => 'security.permissions_enforced', 'value' => json_encode(false), 'type' => 'boolean', 'description' => 'Bật sau khi đã gán vai trò cho người dùng để áp dụng permission lên các module.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'security.max_failed_logins', 'value' => json_encode((int) config('dth-account-management.security.max_failed_logins', 5)), 'type' => 'integer', 'description' => 'Số lần đăng nhập sai tối đa trước khi khóa tạm.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'security.lock_minutes', 'value' => json_encode((int) config('dth-account-management.security.lock_minutes', 30)), 'type' => 'integer', 'description' => 'Thời gian khóa tài khoản tạm thời (phút).', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_settings');
        Schema::dropIfExists('account_audit_logs');
        Schema::dropIfExists('account_invitation_group');
        Schema::dropIfExists('account_invitation_role');
        Schema::dropIfExists('account_invitations');
        Schema::dropIfExists('account_group_user');
        Schema::dropIfExists('account_groups');
        Schema::dropIfExists('account_user_permissions');
        Schema::dropIfExists('account_permission_role');
        Schema::dropIfExists('account_role_user');
        Schema::dropIfExists('account_permissions');
        Schema::dropIfExists('account_roles');
        // User columns intentionally remain on rollback to avoid destructive
        // changes when another host package has begun using them.
    }
};
