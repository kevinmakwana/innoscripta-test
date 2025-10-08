<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            if (! Schema::hasColumn('sources', 'consecutive_failures')) {
                $table->integer('consecutive_failures')->default(0)->after('enabled');
            }

            if (! Schema::hasColumn('sources', 'last_failed_at')) {
                $table->timestamp('last_failed_at')->nullable()->after('consecutive_failures');
            }

            if (! Schema::hasColumn('sources', 'auto_disable')) {
                $table->boolean('auto_disable')->default(false)->after('last_failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            if (Schema::hasColumn('sources', 'auto_disable')) {
                $table->dropColumn('auto_disable');
            }

            if (Schema::hasColumn('sources', 'last_failed_at')) {
                $table->dropColumn('last_failed_at');
            }

            if (Schema::hasColumn('sources', 'consecutive_failures')) {
                $table->dropColumn('consecutive_failures');
            }
        });
    }
};
