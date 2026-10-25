<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCeoRoleToRolesTable extends Migration
{
    /**
     * Introduce the CEO role. The CEO has full administrative access plus
     * exclusive access to the payments module.
     */
    public function up()
    {
        $now = now();

        if (!DB::table('roles')->where('slug', 'ceo')->exists()) {
            DB::table('roles')->insert([
                'name' => 'CEO',
                'slug' => 'ceo',
                'description' => 'Chief Executive Officer — full admin access plus CEO-only payments',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ceoRoleId = DB::table('roles')->where('slug', 'ceo')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        // Promote the primary (oldest) administrator to CEO so the platform owner
        // keeps access to payments after this change.
        if ($ceoRoleId && $adminRoleId && !DB::table('users')->where('role_id', $ceoRoleId)->exists()) {
            $primaryAdminId = DB::table('users')
                ->where('role_id', $adminRoleId)
                ->orderBy('id')
                ->value('id');

            if ($primaryAdminId) {
                DB::table('users')->where('id', $primaryAdminId)->update([
                    'role_id' => $ceoRoleId,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $ceoRoleId = DB::table('roles')->where('slug', 'ceo')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if ($ceoRoleId && $adminRoleId) {
            DB::table('users')->where('role_id', $ceoRoleId)->update(['role_id' => $adminRoleId]);
        }

        DB::table('roles')->where('slug', 'ceo')->delete();
    }
}
