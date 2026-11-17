<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addPath = ! Schema::hasColumn('chat_messages', 'attachment_path');
        $addName = ! Schema::hasColumn('chat_messages', 'attachment_name');

        Schema::table('chat_messages', function (Blueprint $table) use ($addPath, $addName) {
            $table->text('body')->nullable()->change();
            if ($addPath) $table->string('attachment_path')->nullable();
            if ($addName) $table->string('attachment_name', 180)->nullable();
        });
    }

    public function down(): void
    {
        DB::table('chat_messages')->whereNull('body')->update(['body' => '']);

        $dropColumns = array_values(array_filter(['attachment_path', 'attachment_name'], fn ($column) => Schema::hasColumn('chat_messages', $column)));

        Schema::table('chat_messages', function (Blueprint $table) use ($dropColumns) {
            if ($dropColumns) $table->dropColumn($dropColumns);
            $table->text('body')->nullable(false)->change();
        });
    }
};
