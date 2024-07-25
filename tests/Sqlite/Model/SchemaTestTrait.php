<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use Fyre\ORM\ModelRegistry;
use Fyre\Schema\SchemaRegistry;

trait SchemaTestTrait
{
    public function testAliasField(): void
    {
        $this->assertSame(
            'Items.name',
            ModelRegistry::use('Items')->aliasField('name')
        );
    }

    public function testGetAlias(): void
    {
        $this->assertSame(
            'TestItems',
            ModelRegistry::use('TestItems')->getAlias()
        );
    }

    public function testGetDisplayName(): void
    {
        $this->assertSame(
            'name',
            ModelRegistry::use('Items')->getDisplayName()
        );
    }

    public function testGetPrimaryKey(): void
    {
        $this->assertSame(
            ['id'],
            ModelRegistry::use('Items')->getPrimaryKey()
        );
    }

    public function testGetSchema(): void
    {
        $Items = ModelRegistry::use('Items');

        $this->assertSame(
            SchemaRegistry::getSchema($Items->getConnection())->describe('items'),
            $Items->getSchema()
        );
    }

    public function testGetTable(): void
    {
        $this->assertSame(
            'test_items',
            ModelRegistry::use('TestItems')->getTable()
        );
    }

    public function testSetAlias(): void
    {
        $TestItems = ModelRegistry::use('TestItems');

        $this->assertSame(
            $TestItems,
            $TestItems->setAlias('TestAlias')
        );

        $this->assertSame(
            'TestAlias',
            $TestItems->getAlias()
        );
    }

    public function testSetDisplayName(): void
    {
        $Items = ModelRegistry::use('Items');

        $this->assertSame(
            $Items,
            $Items->setDisplayName('value')
        );

        $this->assertSame(
            'value',
            $Items->getDisplayName()
        );
    }

    public function testSetTable(): void
    {
        $Items = ModelRegistry::use('Items');

        $this->assertSame(
            $Items,
            $Items->setTable('test_table')
        );

        $this->assertSame(
            'test_table',
            $Items->getTable()
        );
    }
}
