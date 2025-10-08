<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sources', 'adapter_class')) {
            Schema::table('sources', function (Blueprint $table) {
                $table->string('adapter_class')->nullable()->after('api_key_env');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sources', 'adapter_class')) {
            Schema::table('sources', function (Blueprint $table) {
                $table->dropColumn('adapter_class');
            });
        }
    }
};
