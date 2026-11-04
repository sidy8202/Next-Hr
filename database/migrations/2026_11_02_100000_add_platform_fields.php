<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('sector', 30)->nullable()->after('country');
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('platform_role', 20)->nullable();
            $table->json('platform_permissions')->nullable();
        });

        // Les administrateurs de plateforme existants deviennent propriétaires.
        DB::table('users')->where('is_platform_admin', true)->whereNull('platform_role')->update(['platform_role' => 'owner']);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['platform_role', 'platform_permissions']));
        Schema::table('companies', fn (Blueprint $t) => $t->dropColumn(['sector', 'suspended_at', 'suspension_reason']));
    }
};
