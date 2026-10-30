<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 700 characters keeps the index under MySQL's 3072-byte key limit. Safe to re-run if an earlier attempt added only the column.
        if (Schema::hasColumn('documents', 'legacy_path')) {
            Schema::table('documents', fn (Blueprint $table) => $table->string('legacy_path', 700)->nullable()->change());
        } else {
            Schema::table('documents', fn (Blueprint $table) => $table->string('legacy_path', 700)->nullable());
        }
        if (! Schema::hasIndex('documents', 'documents_legacy_path_index')) {
            Schema::table('documents', fn (Blueprint $table) => $table->index('legacy_path'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('legacy_path');
        });
    }
};
