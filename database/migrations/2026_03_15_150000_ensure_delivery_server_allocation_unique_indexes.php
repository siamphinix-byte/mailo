<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUniqueIndex(
            'customer_delivery_server',
            'cust_del_srv_unique',
            ['customer_id', 'delivery_server_id']
        );

        $this->ensureUniqueIndex(
            'customer_group_delivery_server',
            'cust_grp_del_srv_unique',
            ['customer_group_id', 'delivery_server_id']
        );
    }

    public function down(): void
    {
        $this->dropUniqueIndexIfExists('customer_group_delivery_server', 'cust_grp_del_srv_unique');
        $this->dropUniqueIndexIfExists('customer_delivery_server', 'cust_del_srv_unique');
    }

    private function ensureUniqueIndex(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->unique($columns, $indexName);
        });
    }

    private function dropUniqueIndexIfExists(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropUnique($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes($table);
            return array_key_exists($indexName, $indexes);
        } catch (\Throwable) {
            // Fallback for environments where Doctrine is not available
            return false;
        }
    }
};
