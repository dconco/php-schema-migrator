<?php

namespace Dconco\SchemaMigrator;

use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

class DatabaseManager
{
    private static ?Capsule $capsule = null;
    private static array $config = [];

    public static function initialize(array $config): void
    {
        self::$config = $config;
        self::$capsule = new Capsule();
        
        self::$capsule->addConnection([
            'driver' => $config['driver'] ?? 'mysql',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
            'database' => $config['database'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'charset' => $config['charset'] ?? 'utf8mb4',
            'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => $config['prefix'] ?? '',
        ]);

        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();
        
        self::createMigrationsTable();
    }

    public static function getCapsule(): Capsule
    {
        if (!self::$capsule) {
            throw new RuntimeException('Database not initialized. Run "schema-migrator init" first.');
        }
        
        return self::$capsule;
    }

    public static function getConnection()
    {
        return self::getCapsule()->getConnection();
    }

    private static function createMigrationsTable(): void
    {
        $schema = self::getCapsule()->schema();
        
        if (!$schema->hasTable('migrations')) {
            $schema->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public static function isConfigured(): bool
    {
        return file_exists(ConfigManager::getConfigPath());
    }
}