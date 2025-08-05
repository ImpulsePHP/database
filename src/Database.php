<?php

declare(strict_types=1);

namespace Impulse\Database;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseInterface as CycleDatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Cycle\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Impulse\Core\Support\Config;
use Impulse\Database\Exceptions\DatabaseException;

final class Database implements DatabaseInterface
{
    private DatabaseManager $databaseManager;
    private ORMInterface $orm;
    private array $config;

    /**
     * @throws DatabaseException
     * @throws \JsonException
     */
    public function __construct()
    {
        $this->config = Config::get('database', []);

        $this->validateConfiguration();
        $this->initializeDatabase();
        $this->initializeORM();
        $this->registerDefaultConfiguration();
    }

    public function getDatabase(?string $name = null): CycleDatabaseInterface
    {
        return $this->databaseManager->database($name);
    }

    public function getORM(): ORMInterface
    {
        return $this->orm;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @throws DatabaseException
     */
    private function initializeDatabase(): void
    {
        if (empty($this->config)) {
            throw new DatabaseException(
                'Configuration de base de données requise. Veuillez configurer la section "database".'
            );
        }

        $cycleConfig = $this->transformToCycleConfig($this->config);
        $databaseConfig = new DatabaseConfig($cycleConfig);

        $this->databaseManager = new DatabaseManager($databaseConfig);
    }

    /**
     * @throws \JsonException
     */
    private function initializeORM(): void
    {
        $schemaConfig = Config::get('orm.schema', []);
        $schema = new Schema($schemaConfig);
        $factory = new Factory($this->databaseManager);

        $this->orm = new ORM($factory, $schema);
    }

    /**
     * @throws DatabaseException
     */
    private function transformToCycleConfig(array $config): array
    {
        return [
            'default' => 'default',
            'databases' => [
                'default' => [
                    'connection' => $config['driver'],
                ]
            ],
            'connections' => [
                $config['driver'] => $this->createDriverConfig($config),
                'options' => [
                    'connection' => $this->createConnectionConfig($config),
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'queryCache' => true,
                ]
            ]
        ];
    }

    /**
     * @throws DatabaseException
     */
    private function createDriverConfig(array $connectionConfig): string
    {
        $driver = $connectionConfig['driver'];

        return match ($driver) {
            'pgsql' => PostgresDriver::class,
            'mysql' => MySQLDriver::class,
            'sqlite' => SQLiteDriver::class,
            default => throw new DatabaseException("Driver '{$driver}' non supporté."),
        };
    }

    /**
     * @throws DatabaseException
     */
    private function createConnectionConfig(array $connectionConfig): string
    {
        $driver = $connectionConfig['driver'];

        return match ($driver) {
            'pgsql' => sprintf('pgsql:host=%s;dbname=%s', $connectionConfig['host'], $connectionConfig['database']),
            'mysql' => sprintf('mysql:host=%s;dbname=%s', $connectionConfig['host'], $connectionConfig['database']),
            'sqlite' => sprintf('sqlite:%s', $connectionConfig['database']),
            default => throw new DatabaseException("Driver '{$driver}' non supporté."),
        };
    }

    /**
     * @throws DatabaseException
     */
    private function validateConfiguration(): void
    {
        if (empty($this->config)) {
            throw new DatabaseException(
                'Configuration de base de données manquante. Ajoutez une section "database".'
            );
        }

        $this->validateConnectionConfig();
    }

    /**
     * @throws DatabaseException
     */
    private function validateConnectionConfig(): void
    {
        if (empty($this->config['driver'])) {
            throw new DatabaseException("Le champ 'driver' est requis pour la connexion.");
        }

        $driver = $this->config['driver'];
        $requiredFields = match ($driver) {
            'pgsql', 'mysql' => ['host', 'database', 'username'],
            'sqlite' => ['database'],
            default => throw new DatabaseException("Driver '{$driver}' non supporté."),
        };

        foreach ($requiredFields as $field) {
            if (empty($connectionConfig[$field])) {
                throw new DatabaseException("Le champ '{$field}' est requis pour la connexion {$driver}.");
            }
        }
    }

    /**
     * @throws \JsonException
     */
    private function registerDefaultConfiguration(): void
    {
        if (Config::has('orm')) {
            Config::set('orm', [
                'schema' => [],
                'proxies' => ['directory' => getcwd() . '/storage/cycle/proxies'],
                'migrations' => [
                    'directory' => getcwd() . '/database/migrations',
                    'table' => 'cycle_migrations',
                ],
                'cache' => [
                    'enable' => false,
                    'directory' => getcwd() . '/storage/cycle/cache',
                ],
            ]);
        }
    }
}
