<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create (or update) the initial Super Admin account and grant it the
     * "super-admin" account role so it has full access from the first login.
     */
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL', 'admin@dth.local');
        $password = (string) env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $roleId = DB::table('account_roles')->where('key', 'super-admin')->value('id');
        if (! $roleId) {
            return;
        }

        DB::table('account_role_user')->updateOrInsert(
            ['role_id' => $roleId, 'user_id' => $user->id],
            ['updated_at' => now(), 'created_at' => now()],
        );
    }
}
