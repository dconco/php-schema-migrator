<?php

namespace Dconco\SchemaMigrator;

use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class ConfigManager
{
    public static function getConfigPath(): string
    {
        return getcwd() . '/schema-migrator.yml';
    }

    public static function getMigrationsPath(): string
    {
        return getcwd() . '/database/migrations';
    }

    public static function loadConfig(): array
    {
        $configPath = self::getConfigPath();
        
        if (!file_exists($configPath)) {
            throw new RuntimeException(
                "Configuration file not found at {$configPath}. Run 'schema-migrator init' to create it."
            );
        }

        return Yaml::parseFile($configPath);
    }

    public static function saveConfig(array $config): void
    {
        $configPath = self::getConfigPath();
        $yaml = Yaml::dump($config, 4, 2);
        
        file_put_contents($configPath, $yaml);
    }

    public static function getDefaultConfig(): array
    {
        return [
            'database' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'your_database',
                'username' => 'your_username',
                'password' => 'your_password',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ],
            'migrations' => [
                'table' => 'migrations',
                'path' => 'database/migrations',
            ]
        ];
    }

    public static function ensureMigrationsDirectory(): void
    {
        $migrationsPath = self::getMigrationsPath();
        
        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }
    }
}