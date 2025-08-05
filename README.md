# Documentation complète - Provider CycleORM pour Impulse
## Table des matières
1. [Installation](#installation)
2. [Configuration minimale](#configuration-minimale)
3. [Configuration complète](#configuration-compl%C3%A8te)
4. [Structure des répertoires](#structure-des-r%C3%A9pertoires)
5. [Première utilisation](#premi%C3%A8re-utilisation)
6. [Dépannage](#d%C3%A9pannage)

## Installation
### 1. Installation via Composer
``` bash
composer require impulsephp/database
```
### 2. Vérification des extensions PHP requises
Le provider nécessite les extensions PHP suivantes :
``` bash
# Vérifier les extensions
php -m | grep -E "(pdo|pdo_mysql|pdo_sqlite|pdo_pgsql)"
```
**Extensions requises :**
- `pdo` (obligatoire)
- `pdo_mysql` (pour MySQL/MariaDB)
- `pdo_sqlite` (pour SQLite)
- `pdo_pgsql` (pour PostgreSQL)

### 3. Installation des extensions manquantes
**Ubuntu/Debian :**
``` bash
sudo apt-get install php-pdo php-mysql php-sqlite3 php-pgsql
```
**CentOS/RHEL :**
``` bash
sudo yum install php-pdo php-mysql php-sqlite php-pgsql
```
**macOS (Homebrew) :**
``` bash
brew install php
# Les extensions sont généralement incluses
```
## Configuration minimale
### 1. Ajout du provider
``` php
<?php

return [
    // ... autres configurations
    
    'providers' => [
        'Impulse\\Database\\DatabaseProvider', // AJOUTEZ CETTE LIGNE
    ],
    
    // Configuration MINIMALE de base de données
    'database' => [
        'default' => 'sqlite',
        'databases' => [
            'default' => [
                'connection' => 'sqlite'
            ]
        ],
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../database.sqlite',
            ],
        ],
    ],
];
```
### 2. Création des répertoires nécessaires
``` bash
# À la racine de votre projet
mkdir -p storage/cycle/proxies
mkdir -p database/migrations
touch database.sqlite
```
### 3. Test de fonctionnement
``` php
<?php

require_once 'vendor/autoload.php';

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Database\DatabaseProvider;
use Cycle\ORM\ORMInterface;

try {
    // Initialisation du conteneur
    $container = new ImpulseContainer();
    
    // Enregistrement du provider
    $provider = new DatabaseProvider();
    $provider->register($container);
    $provider->boot($container);
    
    // Test de récupération de l'ORM
    $orm = $container->get(ORMInterface::class);
    
    echo "✓ Provider CycleORM installé et fonctionnel !\n";
    echo "Type d'ORM : " . get_class($orm) . "\n";
    
} catch (\Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
}
```
## Configuration complète
### 1. Configuration avec MySQL
``` php
<?php

return [
    // ... autres configurations
    
    'providers' => [
        'Impulse\\Database\\DatabaseProvider',
    ],
    
    // ===== CONFIGURATION BASE DE DONNÉES =====
    'database' => [
        'default' => 'mysql',
        'databases' => [
            'default' => [
                'connection' => 'mysql'
            ]
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'impulse_app',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../database.sqlite',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            ],
        ],
        'profiling' => true,
        'log_queries' => true,
    ],
    
    // ===== CONFIGURATION ORM =====
    'orm' => [
        'schema' => [],
        'proxies' => [
            'directory' => __DIR__ . '/../storage/cycle/proxies',
            'auto_generate' => true,
        ],
        'migrations' => [
            'directory' => __DIR__ . '/../database/migrations',
            'table' => 'cycle_migrations',
        ],
        'cache' => [
            'enable' => false,
            'directory' => __DIR__ . '/../storage/cycle/cache',
        ],
    ],
];
```
### 2. Configuration avec PostgreSQL
``` php
<?php

return [
    // ... autres configurations identiques
    
    'database' => [
        'default' => 'pgsql',
        'databases' => [
            'default' => [
                'connection' => 'pgsql'
            ]
        ],
        'connections' => [
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'impulse_app',
                'username' => 'postgres',
                'password' => 'password',
                'charset' => 'utf8',
                'schema' => 'public',
                'sslmode' => 'prefer',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ],
        'profiling' => true,
        'log_queries' => true,
    ],
];
```
### 3. Configuration multi-environnements
``` php
<?php

// Fonction helper pour les variables d'environnement
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) return $default;
        
        return match (strtolower($value)) {
            'true', '1', 'on', 'yes' => true,
            'false', '0', 'off', 'no' => false,
            'null', '' => null,
            default => $value,
        };
    }
}

return [
    // ... autres configurations
    
    'database' => [
        'default' => env('DB_CONNECTION', 'sqlite'),
        'databases' => [
            'default' => [
                'connection' => env('DB_CONNECTION', 'sqlite')
            ]
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', ''),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', 5432),
                'database' => env('DB_DATABASE', ''),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', __DIR__ . '/../database.sqlite'),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            ],
        ],
        'profiling' => env('DB_PROFILING', true),
        'log_queries' => env('DB_LOG_QUERIES', true),
    ],
];
```
### 4. Fichier .env
``` bash
# Base de données
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=impulse_app
DB_USERNAME=root
DB_PASSWORD=

# Pour PostgreSQL, utilisez :
# DB_CONNECTION=pgsql
# DB_PORT=5432
# DB_USERNAME=postgres

# Pour SQLite, utilisez :
# DB_CONNECTION=sqlite
# DB_DATABASE=/chemin/vers/database.sqlite

# Debug
DB_PROFILING=true
DB_LOG_QUERIES=true
```

## Première utilisation
### 1. Création d'une première entité
``` php
<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: 'users')]
class User
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTime();
    }
}
```
### 2. Première utilisation dans un composant
``` php
<?php

declare(strict_types=1);

namespace App\Component;

use App\Entity\User;
use Cycle\ORM\ORMInterface;
use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Container\ImpulseContainer;

class UserListComponent extends AbstractComponent
{
    private ORMInterface $orm;

    public function boot(ImpulseContainer $container)
    {
        $this->orm = $container->get(ORMInterface::class);
    }

    public function render(): string
    {
        // Créer un utilisateur de test
        $user = new User('Jean Dupont', 'jean@example.com');
        
        $entityManager = $this->orm->getEntityManager();
        $entityManager->persist($user);
        $entityManager->run();

        // Récupérer tous les utilisateurs
        $users = $this->orm->getRepository(User::class)->findAll();

        $html = '<div class="users">';
        $html .= '<h2>Utilisateurs (' . count($users) . ')</h2>';
        
        foreach ($users as $user) {
            $html .= sprintf(
                '<p><strong>%s</strong> - %s (ID: %d)</p>',
                htmlspecialchars($user->name),
                htmlspecialchars($user->email),
                $user->id
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
```
### 3. Test complet du système
``` php
<?php

require_once 'vendor/autoload.php';

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Database\DatabaseProvider;
use Cycle\ORM\ORMInterface;
use App\Entity\User;

try {
    echo "🧪 Test complet du provider CycleORM...\n\n";

    // 1. Initialisation
    $container = new ImpulseContainer();
    $provider = new DatabaseProvider();
    $provider->register($container);
    $provider->boot($container);
    echo "✓ Provider initialisé\n";

    // 2. Récupération de l'ORM
    $orm = $container->get(ORMInterface::class);
    echo "✓ ORM récupéré\n";

    // 3. Test de création d'entité
    $user = new User('Test User', 'test@example.com');
    $entityManager = $orm->getEntityManager();
    $entityManager->persist($user);
    $entityManager->run();
    echo "✓ Utilisateur créé (ID: {$user->id})\n";

    // 4. Test de récupération
    $repository = $orm->getRepository(User::class);
    $foundUser = $repository->findByPK($user->id);
    
    if ($foundUser && $foundUser->email === 'test@example.com') {
        echo "✓ Utilisateur récupéré correctement\n";
    } else {
        echo "✗ Erreur lors de la récupération\n";
    }

    // 5. Test de requête
    $allUsers = $repository->findAll();
    echo "✓ Total utilisateurs : " . count($allUsers) . "\n";

    echo "\n🎉 Tous les tests sont passés !\n";

} catch (\Exception $e) {
    echo "\n✗ Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace :\n" . $e->getTraceAsString() . "\n";
}
```
## Dépannage
### 1. Erreurs courantes et solutions
#### **Erreur : "Configuration de base de données requise"**
``` php
// ❌ Configuration manquante
'providers' => [
    'Impulse\\Database\\DatabaseProvider',
],
// Manque la section 'database'

// ✅ Configuration correcte
'providers' => [
    'Impulse\\Database\\DatabaseProvider',
],
'database' => [
    'default' => 'sqlite',
    // ... reste de la configuration
],
```
#### **Erreur : "Impossible de se connecter à la base de données"**
**Solutions :**
1. **Vérifier les informations de connexion :**
``` php
'connections' => [
    'mysql' => [
        'host' => 'localhost',     // ✓ Host correct ?
        'database' => 'test_db',   // ✓ Base existe ?
        'username' => 'root',      // ✓ Utilisateur correct ?
        'password' => 'password',  // ✓ Mot de passe correct ?
    ],
],
```
1. **Tester la connexion manuellement :**
``` bash
# MySQL
mysql -h localhost -u root -p

# PostgreSQL  
psql -h localhost -U postgres -d impulse_app
```
#### **Erreur : "Class 'App\Entity\User' not found"**
**Solutions :**
1. **Vérifier l'autoloader :**
``` json
// composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

``` bash
composer dump-autoload
```
1. **Vérifier le namespace :**
``` php
<?php
// ✓ Fichier : app/Entity/User.php
namespace App\Entity;  // ✓ Namespace correct

class User
{
    // ...
}
```
#### **Erreur : "Directory not writable"**
``` bash
# Corriger les permissions
chmod -R 755 storage/
chmod -R 755 database/

# Ou plus permissif si nécessaire
chmod -R 777 storage/
chmod -R 777 database/
```

### 2. Debug et logs
#### **Activer le debug des requêtes :**
``` php
'database' => [
    'profiling' => true,
    'log_queries' => true,
],
```
#### **Script de diagnostic :**
``` php
<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

echo "🔍 Diagnostic CycleORM Provider\n";
echo str_repeat("=", 40) . "\n\n";

// 1. Vérification des extensions PHP
echo "📋 Extensions PHP :\n";
$extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("  %s %s\n", $loaded ? '✓' : '✗', $ext);
}
echo "\n";

// 2. Vérification des répertoires
echo "📁 Répertoires :\n";
$directories = [
    'storage/cycle/proxies',
    'storage/cycle/cache', 
    'database/migrations',
];
foreach ($directories as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    echo sprintf("  %s %s %s\n", 
        $exists ? '✓' : '✗', 
        $dir,
        $writable ? '(writable)' : '(not writable)'
    );
}
echo "\n";

// 3. Test de configuration
echo "⚙️  Configuration :\n";
try {
    $config = require 'config/app.php';
    
    $hasProvider = in_array('Impulse\\Database\\DatabaseProvider', $config['providers'] ?? []);
    echo sprintf("  %s Provider enregistré\n", $hasProvider ? '✓' : '✗');
    
    $hasDatabase = isset($config['database']);
    echo sprintf("  %s Section database\n", $hasDatabase ? '✓' : '✗');
    
    if ($hasDatabase) {
        $default = $config['database']['default'] ?? null;
        echo sprintf("  %s Connexion par défaut : %s\n", $default ? '✓' : '✗', $default);
    }
    
} catch (\Exception $e) {
    echo "  ✗ Erreur de configuration : " . $e->getMessage() . "\n";
}
echo "\n";

echo "🏁 Diagnostic terminé.\n";
```
### 3. FAQ
**Q : Puis-je utiliser plusieurs bases de données ?**
R : Oui, configurez plusieurs connexions :
``` php
'connections' => [
    'main' => [...],      // Base principale
    'analytics' => [...], // Base analytics
    'cache' => [...],     // Base cache
],
```
**Q : Comment changer de base de données en production ?**
R : Modifiez simplement la configuration et les variables d'environnement :
``` php
'default' => env('DB_CONNECTION', 'mysql'),
```
**Q : Les migrations sont-elles supportées ?**
R : Oui, configurez le répertoire :
``` php
'migrations' => [
    'directory' => __DIR__ . '/../database/migrations',
    'table' => 'cycle_migrations',
],
```
**Q : Comment optimiser les performances ?**
R : Activez le cache en production :
``` php
'cache' => [
    'enable' => !env('APP_DEBUG', false),
    'directory' => __DIR__ . '/../storage/cycle/cache',
],
```
Cette documentation vous permet d'installer et configurer le provider CycleORM de manière progressive, depuis une configuration minimale jusqu'à une utilisation complète en production.
