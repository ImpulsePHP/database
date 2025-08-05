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
use Impulse\Database\Contrats\DatabaseInterface;
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

    /**
     * @throws DatabaseException
     */
    public function testConnection(?string $database = null): bool
    {
        try {
            $db = $this->getDatabase($database);
            $result = $db->select('1 as test')->run();
            $result->fetch();
            return true;
        } catch (\Throwable $e) {
            throw new DatabaseException(
                'Impossible de se connecter à la base de données : ' . $e->getMessage(),
                0,
                $e
            );
        }
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
                'Configuration de base de données requise. ' .
                'Veuillez configurer la section "database" dans votre configuration.'
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

        $factory = new Factory($this->databaseManager, null, null);

        $this->orm = new ORM($factory, $schema);
    }

    /**
     * @throws DatabaseException
     */
    private function transformToCycleConfig(array $config): array
    {
        $cycleConfig = [
            'databases' => $config['databases'] ?? [],
            'connections' => []
        ];

        foreach ($config['connections'] as $name => $connectionConfig) {
            $cycleConfig['connections'][$name] = $this->createDriverConfig($connectionConfig);
        }

        return $cycleConfig;
    }

    /**
     * @throws DatabaseException
     */
    private function createDriverConfig(array $connectionConfig): array
    {
        $driver = $connectionConfig['driver'];
        $options = $connectionConfig['options'] ?? [];

        switch ($driver) {
            case 'pgsql':
                return [
                    'driver' => PostgresDriver::class,
                    'connection' => sprintf(
                        'pgsql:host=%s;port=%s;dbname=%s;charset=%s',
                        $connectionConfig['host'],
                        $connectionConfig['port'] ?? 5432,
                        $connectionConfig['database'],
                        $connectionConfig['charset'] ?? 'utf8'
                    ),
                    'username' => $connectionConfig['username'],
                    'password' => $connectionConfig['password'],
                    'options' => $options
                ];

            case 'mysql':
                return [
                    'driver' => MySQLDriver::class,
                    'connection' => sprintf(
                        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                        $connectionConfig['host'],
                        $connectionConfig['port'] ?? 3306,
                        $connectionConfig['database'],
                        $connectionConfig['charset'] ?? 'utf8mb4'
                    ),
                    'username' => $connectionConfig['username'],
                    'password' => $connectionConfig['password'],
                    'options' => $options
                ];

            case 'sqlite':
                return [
                    'driver' => SQLiteDriver::class,
                    'connection' => 'sqlite:' . $connectionConfig['database'],
                    'username' => '',
                    'password' => '',
                    'options' => $options
                ];

            default:
                throw new DatabaseException("Driver '{$driver}' non supporté");
        }
    }

    /**
     * @throws DatabaseException
     */
    private function validateConfiguration(): void
    {
        if (empty($this->config)) {
            throw new DatabaseException(
                'Configuration de base de données manquante. ' .
                'Ajoutez une section "database" à votre configuration.'
            );
        }

        if (empty($this->config['databases']) || !is_array($this->config['databases'])) {
            throw new DatabaseException('Section "databases" manquante ou invalide.');
        }

        if (empty($this->config['connections']) || !is_array($this->config['connections'])) {
            throw new DatabaseException('Section "connections" manquante ou invalide.');
        }

        foreach ($this->config['databases'] as $dbName => $dbConfig) {
            if (isset($dbConfig['driver'])) {
                $connectionName = $dbConfig['driver'];
                if (!isset($this->config['connections'][$connectionName])) {
                    throw new DatabaseException(
                        "La base de données '{$dbName}' référence une connexion '{$connectionName}' qui n'existe pas."
                    );
                }

                $this->validateConnectionConfig($this->config['connections'][$connectionName], $connectionName);
            }
        }
    }

    /**
     * @throws DatabaseException
     */
    private function validateConnectionConfig(array $connectionConfig, string $connectionName): void
    {
        $requiredFields = ['driver'];

        foreach ($requiredFields as $field) {
            if (!isset($connectionConfig[$field])) {
                throw new DatabaseException(
                    "Le champ '{$field}' est requis pour la connexion '{$connectionName}'"
                );
            }
        }

        $driver = $connectionConfig['driver'];

        switch ($driver) {
            case 'pgsql':
            case 'mysql':
                $requiredDbFields = ['host', 'database', 'username'];
                foreach ($requiredDbFields as $field) {
                    if (!isset($connectionConfig[$field])) {
                        throw new DatabaseException(
                            "Le champ '{$field}' est requis pour la connexion {$driver} '{$connectionName}'"
                        );
                    }
                }
                break;

            case 'sqlite':
                if (!isset($connectionConfig['database'])) {
                    throw new DatabaseException(
                        "Le champ 'database' est requis pour la connexion SQLite '{$connectionName}'"
                    );
                }
                break;

            default:
                throw new DatabaseException("Driver '{$driver}' non supporté");
        }
    }

    /**
     * @throws \JsonException
     */
    private function registerDefaultConfiguration(): void
    {
        if (!Config::has('orm')) {
            $defaultOrmConfig = [
                'schema' => [],
                'proxies' => [
                    'directory' => getcwd() . '/storage/cycle/proxies',
                ],
                'migrations' => [
                    'directory' => getcwd() . '/database/migrations',
                    'table' => 'cycle_migrations',
                ],
                'cache' => [
                    'enable' => false,
                    'directory' => getcwd() . '/storage/cycle/cache',
                ],
            ];

            Config::set('orm', $defaultOrmConfig);
        }
    }
}
