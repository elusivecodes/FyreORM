<?php
declare(strict_types=1);

namespace Tests\Postgres;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Postgres\PostgresConnection;
use Fyre\DB\TypeParser;
use Fyre\Entity\EntityLocator;
use Fyre\Event\EventManager;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;
use Fyre\Schema\SchemaRegistry;
use Fyre\Utility\Inflector;

use function getenv;

trait PostgresConnectionTrait
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
                    'className' => PostgresConnection::class,
                    'host' => getenv('POSTGRES_HOST'),
                    'username' => getenv('POSTGRES_USERNAME'),
                    'password' => getenv('POSTGRES_PASSWORD'),
                    'database' => getenv('POSTGRES_DATABASE'),
                    'port' => getenv('POSTGRES_PORT'),
                    'charset' => 'utf8',
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
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                name VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE contains (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                item_id INTEGER NOT NULL,
                contained_item_id INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE others (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                value INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE timestamps (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                created TIMESTAMP NOT NULL,
                modified TIMESTAMP NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE users (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                name VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE addresses (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                user_id INTEGER NOT NULL,
                address_1 VARCHAR(255) NULL DEFAULT NULL,
                address_2 VARCHAR(255) NULL DEFAULT NULL,
                suburb VARCHAR(255) NULL DEFAULT NULL,
                state VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE posts (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                user_id INTEGER NULL DEFAULT NULL,
                title VARCHAR(255) NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE comments (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                user_id INTEGER NULL DEFAULT NULL,
                post_id INTEGER NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE tags (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                tag VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $this->db->query(<<<'EOT'
            CREATE TABLE posts_tags (
                id INTEGER GENERATED BY DEFAULT AS IDENTITY,
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                value INTEGER NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
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
