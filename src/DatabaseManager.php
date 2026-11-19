<?php

namespace Dconco\SchemaMigrator;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use RuntimeException;

class DatabaseManager
{
    private static ?Capsule $capsule = null;
    private static array $config = [];

    public static function initialize(array $config): void
    {
        self::$config = $config;
        self::$capsule = new Capsule();
        
        $driver = $config['driver'] ?? 'mysql';
        $defaultPort = match ($driver) {
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            default => 3306,
        };
        $defaultCharset = match ($driver) {
            'pgsql' => 'utf8',
            default => 'utf8mb4',
        };

        $connectionConfig = array_merge([
            'driver' => $driver,
            'host' => '127.0.0.1',
            'port' => $defaultPort,
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => $defaultCharset,
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ], $config);

        self::$capsule->addConnection($connectionConfig);
        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();

        // Register blueprint binding to apply collation to columns when driver does not support table-level collation (like pgsql)
        self::registerBlueprintCollationHandler($config);
        
        self::createMigrationsTable();
    }

    private static function registerBlueprintCollationHandler(array $config): void
    {
        $collation = $config['collation'] ?? null;
        $driver = $config['driver'] ?? 'mysql';

        if ($collation && $driver === 'pgsql') {
            $blueprintResolver = function ($app, $parameters) use ($collation) {
                return new class($parameters['connection'], $parameters['table'], $parameters['callback'] ?? null, $collation) extends Blueprint {
                    protected ?string $defaultCollation;

                    public function __construct($connection, $table, $callback = null, ?string $collation = null)
                    {
                        $this->defaultCollation = $collation;
                        parent::__construct($connection, $table, $callback);
                    }

                    public function toSql()
                    {
                        if ($this->defaultCollation) {
                            $stringTypes = ['string', 'text', 'char', 'mediumText', 'longText', 'tinyText'];
                            foreach ($this->getColumns() as $col) {
                                if (in_array($col->type, $stringTypes, true) && is_null($col->collation)) {
                                    $col->collation = $this->defaultCollation;
                                }
                            }
                        }
                        return parent::toSql();
                    }
                };
            };

            $containers = array_filter([Container::getInstance(), self::$capsule?->getContainer()]);
            foreach ($containers as $container) {
                $container->bind(Blueprint::class, $blueprintResolver);
            }
        }
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
        $tableName = ConfigManager::getMigrationsTable();
        
        if (!$schema->hasTable($tableName)) {
            $schema->create($tableName, function ($table) {
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
