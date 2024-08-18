<?php
declare(strict_types=1);

namespace Tests\Sqlite;

use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Sqlite\SqliteConnection;
use Fyre\Entity\EntityLocator;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;

trait SqliteConnectionTrait
{
    public static function setUpBeforeClass(): void
    {
        ConnectionManager::clear();

        ConnectionManager::setConfig([
            'default' => [
                'className' => SqliteConnection::class,
                'persist' => false,
            ],
        ]);

        $connection = ConnectionManager::use();

        $connection->query('DROP TABLE IF EXISTS contains');
        $connection->query('DROP TABLE IF EXISTS items');
        $connection->query('DROP TABLE IF EXISTS others');
        $connection->query('DROP TABLE IF EXISTS timestamps');
        $connection->query('DROP TABLE IF EXISTS users');
        $connection->query('DROP TABLE IF EXISTS addresses');
        $connection->query('DROP TABLE IF EXISTS posts');
        $connection->query('DROP TABLE IF EXISTS comments');
        $connection->query('DROP TABLE IF EXISTS tags');
        $connection->query('DROP TABLE IF EXISTS posts_tags');

        $connection->query(<<<'EOT'
            CREATE TABLE items (
                id INTEGER NOT NULL,
                name VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE contains (
                id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                contained_item_id INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE others (
                id INTEGER NOT NULL,
                value INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE timestamps (
                id INTEGER NOT NULL,
                created DATETIME NOT NULL,
                modified DATETIME NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE users (
                id INTEGER NOT NULL,
                name VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE addresses (
                id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                address_1 VARCHAR(255) NULL DEFAULT NULL,
                address_2 VARCHAR(255) NULL DEFAULT NULL,
                suburb VARCHAR(255) NULL DEFAULT NULL,
                state VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE posts (
                id INTEGER NOT NULL,
                user_id INTEGER NULL DEFAULT NULL,
                title VARCHAR(255) NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE comments (
                id INTEGER NOT NULL,
                user_id INTEGER NULL DEFAULT NULL,
                post_id INTEGER NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE tags (
                id INTEGER NOT NULL,
                tag VARCHAR(255) NULL DEFAULT NULL,
                PRIMARY KEY (id)
            )
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE posts_tags (
                id INTEGER NOT NULL,
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
        EOT);
    }

    public static function tearDownAfterClass(): void
    {
        $connection = ConnectionManager::use();
        $connection->query('DROP TABLE IF EXISTS contains');
        $connection->query('DROP TABLE IF EXISTS items');
        $connection->query('DROP TABLE IF EXISTS others');
        $connection->query('DROP TABLE IF EXISTS timestamps');
        $connection->query('DROP TABLE IF EXISTS users');
        $connection->query('DROP TABLE IF EXISTS addresses');
        $connection->query('DROP TABLE IF EXISTS posts');
        $connection->query('DROP TABLE IF EXISTS comments');
        $connection->query('DROP TABLE IF EXISTS tags');
        $connection->query('DROP TABLE IF EXISTS posts_tags');
    }

    protected function setUp(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');

        EntityLocator::clear();
        EntityLocator::addNamespace('Tests\Mock\Entity');

        ModelRegistry::clear();
        ModelRegistry::addNamespace('Tests\Mock\Model');
    }

    protected function tearDown(): void
    {
        $connection = ConnectionManager::use();
        $connection->query('DELETE FROM items');
        $connection->query('DELETE FROM others');
        $connection->query('DELETE FROM timestamps');
        $connection->query('DELETE FROM users');
        $connection->query('DELETE FROM addresses');
        $connection->query('DELETE FROM posts');
        $connection->query('DELETE FROM comments');
        $connection->query('DELETE FROM tags');
        $connection->query('DELETE FROM posts_tags');
    }
}
