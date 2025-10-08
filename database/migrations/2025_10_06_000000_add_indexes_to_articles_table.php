<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Add indexes if they do not already exist
            if (! Schema::hasColumn('articles', 'source_id')) {
                return;
            }

            $indexes = [
                'articles_source_id_index' => 'source_id',
                'articles_category_id_index' => 'category_id',
                'articles_author_id_index' => 'author_id',
            ];

            foreach ($indexes as $indexName => $column) {
                // SHOW INDEX is MySQL-specific. Only attempt it when using MySQL;
                // otherwise fall back to adding the index (Schema will avoid dupes
                // in most drivers or throw which we catch below).
                $found = [];
                try {
                    $driver = Schema::getConnection()->getDriverName();
                    if ($driver === 'mysql') {
                        $found = DB::select("SHOW INDEX FROM `articles` WHERE Key_name = ?", [$indexName]);
                    }
                } catch (\Throwable $e) {
                    // ignore and fall back to creating index
                    $found = [];
                }

                if (empty($found)) {
                    $table->index($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['source_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['author_id']);
        });
    }
};
