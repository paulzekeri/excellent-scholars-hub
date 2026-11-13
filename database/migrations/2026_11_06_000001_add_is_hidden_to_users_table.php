<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A maintenance/super-user flag. Hidden accounts keep the CEO role (so they can
 * see and do everything) but are filtered out of every user list, dropdown,
 * contact picker, broadcast/notification fan-out and count, and they never
 * receive password-reset mail. See MAINTENANCE-USER.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_active')
                ->comment('Maintenance account: full access, invisible to everyone else');
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_hidden']);
            $table->dropColumn('is_hidden');
        });
    }
};
