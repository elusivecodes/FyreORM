<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

trait SchemaTestTrait
{
    public function testAliasField(): void
    {
        $this->assertSame(
            'Items.name',
            $this->modelRegistry->use('Items')->aliasField('name')
        );
    }

    public function testGetAlias(): void
    {
        $this->assertSame(
            'TestItems',
            $this->modelRegistry->use('TestItems')->getAlias()
        );
    }

    public function testGetDisplayName(): void
    {
        $this->assertSame(
            'name',
            $this->modelRegistry->use('Items')->getDisplayName()
        );
    }

    public function testGetPrimaryKey(): void
    {
        $this->assertSame(
            ['id'],
            $this->modelRegistry->use('Items')->getPrimaryKey()
        );
    }

    public function testGetRouteKey(): void
    {
        $this->assertSame(
            'id',
            $this->modelRegistry->use('Items')->getRouteKey()
        );
    }

    public function testGetSchema(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $this->assertSame(
            $this->schemaRegistry->use($Items->getConnection())->table('items'),
            $Items->getSchema()
        );
    }

    public function testGetTable(): void
    {
        $this->assertSame(
            'test_items',
            $this->modelRegistry->use('TestItems')->getTable()
        );
    }

    public function testSetAlias(): void
    {
        $TestItems = $this->modelRegistry->use('TestItems');

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
        $Items = $this->modelRegistry->use('Items');

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
        $Items = $this->modelRegistry->use('Items');

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
