<?php

namespace Dconco\SchemaMigrator;

use Symfony\Component\Yaml\Yaml;
use RuntimeException;

class ConfigManager
{
    private static ?string $customConfigPath = null;
    private static ?array $cachedConfig = null;

    public static function setConfigPath(?string $path): void
    {
        if ($path !== null) {
            if (!str_starts_with($path, '/') && !str_starts_with($path, '\\') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)) {
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            }
        }
        self::$customConfigPath = $path;
        self::$cachedConfig = null;
    }

    public static function getConfigPath(): string
    {
        if (self::$customConfigPath !== null) {
            return self::$customConfigPath;
        }

        return getcwd() . '/schema-migrator.yml';
    }

    public static function getMigrationsPath(): string
    {
        try {
            $config = self::loadConfig();
            if (!empty($config['migrations']['path'])) {
                $path = $config['migrations']['path'];
                if (!str_starts_with($path, '/') && !str_starts_with($path, '\\') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)) {
                    return getcwd() . DIRECTORY_SEPARATOR . $path;
                }
                return $path;
            }
        } catch (\Throwable $e) {
            // Fallback when config cannot be read or not yet created
        }

        return getcwd() . '/database/migrations';
    }

    public static function getMigrationsTable(): string
    {
        try {
            $config = self::loadConfig();
            if (!empty($config['migrations']['table'])) {
                return $config['migrations']['table'];
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return 'migrations';
    }

    public static function loadConfig(): array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        $configPath = self::getConfigPath();
        
        if (!file_exists($configPath)) {
            throw new RuntimeException(
                "Configuration file not found at {$configPath}. Run 'schema-migrator init' to create it or pass --config <path>."
            );
        }

        self::$cachedConfig = Yaml::parseFile($configPath);
        return self::$cachedConfig;
    }

    public static function saveConfig(array $config): void
    {
        $configPath = self::getConfigPath();
        $yaml = Yaml::dump($config, 4, 2);
        
        file_put_contents($configPath, $yaml);
        self::$cachedConfig = $config;
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
