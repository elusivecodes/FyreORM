<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Mysql\MysqlConnection;
use Fyre\DB\TypeParser;
use Fyre\Entity\EntityLocator;
use Fyre\Event\EventManager;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;
use Fyre\Schema\SchemaRegistry;
use Fyre\Utility\Inflector;

use function getenv;

trait MysqlConnectionTrait
{
    protected BehaviorRegistry $behaviorRegistry;

    protected Container $container;

    protected Connection $db;

    protected ModelRegistry $modelRegistry;

    protected SchemaRegistry $schemaRegistry;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->singleton(TypeParser::class);
        $this->container->singleton(Config::class);
        $this->container->singleton(Inflector::class);
        $this->container->singleton(ConnectionManager::class);
        $this->container->singleton(SchemaRegistry::class);
        $this->container->singleton(ModelRegistry::class);
        $this->container->singleton(BehaviorRegistry::class);
        $this->container->singleton(EntityLocator::class);
        $this->container->singleton(EventManager::class);
        $this->container->use(Config::class)
            ->set('App.locale', 'en')
            ->set('Database', [
                'default' => [
                    'className' => MysqlConnection::class,
                    'host' => getenv('MYSQL_HOST'),
                    'username' => getenv('MYSQL_USERNAME'),
                    'password' => getenv('MYSQL_PASSWORD'),
                    'database' => getenv('MYSQL_DATABASE'),
                    'port' => getenv('MYSQL_PORT'),
                    'collation' => 'utf8mb4_unicode_ci',
                    'charset' => 'utf8mb4',
                    'compress' => true,
                    'persist' => false,
                ],
            ]);

        $this->schemaRegistry = $this->container->use(SchemaRegistry::class);
        $this->modelRegistry = $this->container->use(ModelRegistry::class);
        $this->behaviorRegistry = $this->container->use(BehaviorRegistry::class);

        $this->modelRegistry->addNamespace('Tests\Mock\Model');
        $this->behaviorRegistry->addNamespace('Tests\Mock\Behaviors');

        $this->container->use(EntityLocator::class)->addNamespace('Tests\Mock\Entity');

        $this->db = $this->container->use(ConnectionManager::class)->use();

        $this->db->query('DROP TABLE IF EXISTS contains');
        $this->db->query('DROP TABLE IF EXISTS items');
        $this->db->query('DROP TABLE IF EXISTS others');
        $this->db->query('DROP TABLE IF EXISTS timestamps');
        $this->db->query('DROP TABLE IF EXISTS users');
        $this->db->query('DROP TABLE IF EXISTS addresses');
        $this->db->query('DROP TABLE IF EXISTS posts');
        $this->db->query('DROP TABLE IF EXISTS comments');
        $this->db->query('DROP TABLE IF EXISTS tags');
        $this->db->query('DROP TABLE IF EXISTS posts_tags');

        $this->db->query(<<<'EOT'
            CREATE TABLE items (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE contains (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                item_id INT(10) UNSIGNED NOT NULL,
                contained_item_id INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE others (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                value INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE timestamps (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                created DATETIME NOT NULL,
                modified DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE users (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                deleted DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE addresses (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NOT NULL,
                address_1 VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                address_2 VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                suburb VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                state VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                deleted DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE posts (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NULL DEFAULT NULL,
                title VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                content TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                deleted DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE comments (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NULL DEFAULT NULL,
                post_id INT(10) UNSIGNED NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                deleted DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE tags (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                tag VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE posts_tags (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id INT(10) UNSIGNED NOT NULL,
                tag_id INT(10) UNSIGNED NOT NULL,
                value INT(10) UNSIGNED NULL DEFAULT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE IF EXISTS contains');
        $this->db->query('DROP TABLE IF EXISTS items');
        $this->db->query('DROP TABLE IF EXISTS others');
        $this->db->query('DROP TABLE IF EXISTS timestamps');
        $this->db->query('DROP TABLE IF EXISTS users');
        $this->db->query('DROP TABLE IF EXISTS addresses');
        $this->db->query('DROP TABLE IF EXISTS posts');
        $this->db->query('DROP TABLE IF EXISTS comments');
        $this->db->query('DROP TABLE IF EXISTS tags');
        $this->db->query('DROP TABLE IF EXISTS posts_tags');

        $this->db->disconnect();
    }
}
