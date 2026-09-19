<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dth_notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 160)->unique();
            $table->string('name');
            $table->string('source_module', 80)->nullable()->index();
            $table->string('type', 40)->default('system')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->json('channels')->nullable();
            $table->string('title_template');
            $table->text('body_template');
            $table->string('email_subject_template')->nullable();
            $table->longText('email_body_template')->nullable();
            $table->string('action_label_template')->nullable();
            $table->boolean('is_mandatory')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('dth_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 40)->default('system')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('source_module', 80)->nullable()->index();
            $table->string('source_event', 160)->nullable()->index();
            $table->string('source_type')->nullable()->index();
            $table->string('source_id', 100)->nullable()->index();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('title');
            $table->text('body');
            $table->longText('detail_body')->nullable();
            $table->string('action_label', 120)->nullable();
            $table->text('action_url')->nullable();
            $table->json('channels');
            $table->boolean('is_mandatory')->default(false)->index();
            $table->boolean('is_manual')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['source_module', 'source_event']);
        });

        Schema::create('dth_notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->constrained('dth_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('in_app_delivered_at')->nullable()->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->string('email_status', 30)->default('not_requested')->index();
            $table->unsignedSmallInteger('email_attempts')->default(0);
            $table->timestamp('email_queued_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('email_failed_at')->nullable();
            $table->text('email_last_error')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'user_id'], 'dth_notification_recipient_unique');
            $table->index(['user_id', 'read_at', 'deleted_at'], 'dth_notification_inbox_idx');
        });

        Schema::create('dth_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->string('email_digest', 20)->default('immediate');
            $table->boolean('content_preview')->default(true);
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_from')->nullable();
            $table->time('quiet_to')->nullable();
            $table->json('muted_modules')->nullable();
            $table->json('type_preferences')->nullable();
            $table->timestamps();
        });

        $now = now();
        $templates = [
            [
                'code' => 'hr.account_request_submitted',
                'name' => 'HR yêu cầu xử lý tài khoản',
                'source_module' => 'human-resource',
                'type' => 'action_required',
                'priority' => 'high',
                'channels' => json_encode(['in_app', 'email']),
                'title_template' => 'Yêu cầu tài khoản mới từ Nhân sự',
                'body_template' => '{{employee_code}} · {{employee_name}} cần được xử lý tài khoản hệ thống.',
                'email_subject_template' => '[DTH] Yêu cầu tài khoản mới từ Nhân sự',
                'email_body_template' => '{{requester_name}} đã gửi yêu cầu xử lý tài khoản cho {{employee_code}} · {{employee_name}}.',
                'action_label_template' => 'Xử lý yêu cầu',
                'is_mandatory' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'hr.account_request_resolved',
                'name' => 'Yêu cầu tài khoản HR đã được xử lý',
                'source_module' => 'accounts',
                'type' => 'status_update',
                'priority' => 'normal',
                'channels' => json_encode(['in_app', 'email']),
                'title_template' => 'Yêu cầu tài khoản đã được xử lý',
                'body_template' => '{{employee_code}} · {{employee_name}} đã được quản trị viên xử lý yêu cầu tài khoản.',
                'email_subject_template' => '[DTH] Yêu cầu tài khoản đã được xử lý',
                'email_body_template' => 'Yêu cầu tài khoản của {{employee_code}} · {{employee_name}} đã được hoàn tất.',
                'action_label_template' => 'Xem nhân viên',
                'is_mandatory' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        DB::table('dth_notification_templates')->insert($templates);

        if (Schema::hasTable('account_permissions')) {
            foreach ((array) config('dth-notification-center.permissions', []) as $key => $definition) {
                DB::table('account_permissions')->updateOrInsert(
                    ['key' => $key],
                    [
                        'module' => $definition['module'] ?? 'notifications',
                        'name' => $definition['name'] ?? $key,
                        'description' => $definition['description'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            if (Schema::hasTable('account_roles') && Schema::hasTable('account_permission_role')) {
                $roleIds = DB::table('account_roles')
                    ->whereIn('key', ['super-admin', 'super_admin', 'administrator', 'system_admin'])
                    ->pluck('id');
                $permissionIds = DB::table('account_permissions')
                    ->whereIn('key', array_keys((array) config('dth-notification-center.permissions', [])))
                    ->pluck('id');

                foreach ($roleIds as $roleId) {
                    foreach ($permissionIds as $permissionId) {
                        DB::table('account_permission_role')->insertOrIgnore([
                            'permission_id' => $permissionId,
                            'role_id' => $roleId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dth_notification_preferences');
        Schema::dropIfExists('dth_notification_recipients');
        Schema::dropIfExists('dth_notifications');
        Schema::dropIfExists('dth_notification_templates');

        if (Schema::hasTable('account_permissions')) {
            DB::table('account_permissions')
                ->whereIn('key', array_keys((array) config('dth-notification-center.permissions', [])))
                ->delete();
        }
    }
};
