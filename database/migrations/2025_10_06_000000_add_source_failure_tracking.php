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
        Schema::table('sources', function (Blueprint $table) {
            if (! Schema::hasColumn('sources', 'failure_count')) {
                $table->unsignedInteger('failure_count')->default(0)->after('enabled');
            }

            if (! Schema::hasColumn('sources', 'last_failed_at')) {
                $table->timestamp('last_failed_at')->nullable()->after('failure_count');
            }

            if (! Schema::hasColumn('sources', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable()->after('last_failed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            if (Schema::hasColumn('sources', 'failure_count')) {
                $table->dropColumn('failure_count');
            }
            if (Schema::hasColumn('sources', 'last_failed_at')) {
                $table->dropColumn('last_failed_at');
            }
            if (Schema::hasColumn('sources', 'disabled_at')) {
                $table->dropColumn('disabled_at');
            }
        });
    }
};
