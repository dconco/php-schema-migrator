<?php

namespace Dconco\SchemaMigrator;

use Illuminate\Database\Migrations\Migration;
use RuntimeException;

class MigrationManager
{
    public static function getMigrationFiles(): array
    {
        $migrationsPath = ConfigManager::getMigrationsPath();
        
        if (!is_dir($migrationsPath)) {
            return [];
        }

        $files = glob($migrationsPath . '/*.php');
        sort($files);
        
        return $files;
    }

    public static function getExecutedMigrations(): array
    {
        $connection = DatabaseManager::getConnection();
        $tableName = ConfigManager::getMigrationsTable();
        
        return $connection->table($tableName)
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('migration')
            ->toArray();
    }

    public static function getPendingMigrations(): array
    {
        $allFiles = self::getMigrationFiles();
        $executed = self::getExecutedMigrations();
        
        $pending = [];
        foreach ($allFiles as $file) {
            $filename = basename($file, '.php');
            if (!in_array($filename, $executed)) {
                $pending[] = $file;
            }
        }
        
        return $pending;
    }

    public static function createMigration(string $name): string
    {
        ConfigManager::ensureMigrationsDirectory();
        
        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';
        $path = ConfigManager::getMigrationsPath() . '/' . $filename;
        
        $template = self::getMigrationTemplate($name);
        file_put_contents($path, $template);
        
        return $path;
    }

    public static function runMigrations(): array
    {
        $pending = self::getPendingMigrations();
        $connection = DatabaseManager::getConnection();
        $tableName = ConfigManager::getMigrationsTable();
        
        if (empty($pending)) {
            return [];
        }

        $batch = self::getNextBatchNumber();
        $executed = [];
        
        foreach ($pending as $file) {
            $migration = require $file;
            
            if (!$migration instanceof Migration) {
                throw new RuntimeException("Migration file {$file} must return a Migration instance");
            }

            $filename = basename($file, '.php');
            $conn = self::getMigrationConnection($migration);

            $run = function () use ($migration, $conn, $tableName, $filename, $batch) {
                $migration->up();

                $conn->table($tableName)->insert([
                    'migration' => $filename,
                    'batch' => $batch,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            };

            if (self::supportsSchemaTransactions($migration, $conn)) {
                $conn->transaction($run);
            } else {
                $run();
            }
            
            $executed[] = $filename;
        }
        
        return $executed;
    }

    public static function rollbackMigrations(int $steps = 1): array
    {
        $connection = DatabaseManager::getConnection();
        $tableName = ConfigManager::getMigrationsTable();
        
        $lastBatch = $connection->table($tableName)->max('batch');
        
        if (!$lastBatch) {
            return [];
        }

        $migrations = $connection->table($tableName)
            ->where('batch', $lastBatch)
            ->orderBy('migration', 'desc')
            ->get();

        $rolledBack = [];
        
        foreach ($migrations as $migrationRecord) {
            $file = ConfigManager::getMigrationsPath() . '/' . $migrationRecord->migration . '.php';
            
            $migration = null;
            if (file_exists($file)) {
                $migration = require $file;
            }

            $conn = ($migration instanceof Migration)
                ? self::getMigrationConnection($migration)
                : $connection;

            $rollback = function () use ($migration, $conn, $tableName, $migrationRecord) {
                if ($migration instanceof Migration) {
                    $migration->down();
                }

                $conn->table($tableName)
                    ->where('migration', $migrationRecord->migration)
                    ->delete();
            };

            if ($migration instanceof Migration && self::supportsSchemaTransactions($migration, $conn)) {
                $conn->transaction($rollback);
            } else {
                $rollback();
            }
                
            $rolledBack[] = $migrationRecord->migration;
        }
        
        return $rolledBack;
    }

    public static function supportsSchemaTransactions(Migration $migration, $connection = null): bool
    {
        $connection = $connection ?? DatabaseManager::getConnection();

        if (is_null($connection->getSchemaGrammar())) {
            $connection->useDefaultSchemaGrammar();
        }

        return $connection->getSchemaGrammar()->supportsSchemaTransactions()
            && ($migration->withinTransaction ?? true);
    }

    public static function getMigrationConnection(Migration $migration)
    {
        $connectionName = method_exists($migration, 'getConnection') ? $migration->getConnection() : null;

        return $connectionName
            ? DatabaseManager::getCapsule()->getConnection($connectionName)
            : DatabaseManager::getConnection();
    }

    private static function getNextBatchNumber(): int
    {
        $connection = DatabaseManager::getConnection();
        $tableName = ConfigManager::getMigrationsTable();
        return ($connection->table($tableName)->max('batch') ?? 0) + 1;
    }

    private static function getMigrationTemplate(string $name): string
    {
        $className = 'Create' . str_replace('_', '', ucwords($name, '_')) . 'Table';
        
        // Clean table name: remove "create_" prefix if present
        $tableName = $name;
        if (str_starts_with($tableName, 'create_')) {
            $tableName = substr($tableName, 7);
        }
        
        return <<<PHP
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    public function up(): void
    {
        Capsule::schema()->create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('{$tableName}');
    }
};
PHP;
    }
}
