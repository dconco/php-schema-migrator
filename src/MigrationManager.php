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
        
        return $connection->table('migrations')
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
            
            $migration->up();
            
            $filename = basename($file, '.php');
            $connection->table('migrations')->insert([
                'migration' => $filename,
                'batch' => $batch,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            $executed[] = $filename;
        }
        
        return $executed;
    }

    public static function rollbackMigrations(int $steps = 1): array
    {
        $connection = DatabaseManager::getConnection();
        
        $lastBatch = $connection->table('migrations')->max('batch');
        
        if (!$lastBatch) {
            return [];
        }

        $migrations = $connection->table('migrations')
            ->where('batch', $lastBatch)
            ->orderBy('migration', 'desc')
            ->get();

        $rolledBack = [];
        
        foreach ($migrations as $migrationRecord) {
            $file = ConfigManager::getMigrationsPath() . '/' . $migrationRecord->migration . '.php';
            
            if (file_exists($file)) {
                $migration = require $file;
                $migration->down();
            }
            
            $connection->table('migrations')
                ->where('migration', $migrationRecord->migration)
                ->delete();
                
            $rolledBack[] = $migrationRecord->migration;
        }
        
        return $rolledBack;
    }

    private static function getNextBatchNumber(): int
    {
        $connection = DatabaseManager::getConnection();
        return ($connection->table('migrations')->max('batch') ?? 0) + 1;
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