<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\DB\ConnectionManager;
use Fyre\DB\Handlers\Mysql\MysqlConnection;
use Fyre\Entity\EntityLocator;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;

use function getenv;

trait MysqlConnectionTrait
{
    public static function setUpBeforeClass(): void
    {
        ConnectionManager::clear();
        ConnectionManager::setConfig('default', [
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
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE contains (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                item_id INT(10) UNSIGNED NOT NULL,
                contained_item_id INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE others (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                value INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE timestamps (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                created DATETIME NOT NULL,
                modified DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE users (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE addresses (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NOT NULL,
                address_1 VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                address_2 VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                suburb VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                state VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE posts (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NULL DEFAULT NULL,
                title VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                content TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE comments (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT(10) UNSIGNED NULL DEFAULT NULL,
                post_id INT(10) UNSIGNED NULL DEFAULT NULL,
                content TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE tags (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                tag VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
        EOT);

        $connection->query(<<<'EOT'
            CREATE TABLE posts_tags (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id INT(10) UNSIGNED NOT NULL,
                tag_id INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB
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

    public function setUp(): void
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
        $connection->query('TRUNCATE items');
        $connection->query('TRUNCATE others');
        $connection->query('TRUNCATE timestamps');
        $connection->query('TRUNCATE users');
        $connection->query('TRUNCATE addresses');
        $connection->query('TRUNCATE posts');
        $connection->query('TRUNCATE comments');
        $connection->query('TRUNCATE tags');
        $connection->query('TRUNCATE posts_tags');
    }
}
