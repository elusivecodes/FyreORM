<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry,
    Fyre\Schema\SchemaRegistry;

trait SchemaTest
{

    public function testAliasField(): void
    {
        $this->assertSame(
            'Test.name',
            ModelRegistry::use('Test')->aliasField('name')
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
            ModelRegistry::use('Test')->getDisplayName()
        );
    }

    public function testGetPrimaryKey(): void
    {
        $this->assertSame(
            ['id'],
            ModelRegistry::use('Test')->getPrimaryKey()
        );
    }

    public function testGetSchema(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertSame(
            SchemaRegistry::getSchema($Test->getConnection())->describe('test'),
            $Test->getSchema()
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
        $Test = ModelRegistry::use('Test');

        $this->assertSame(
            $Test,
            $Test->setDisplayName('value')
        );

        $this->assertSame(
            'value',
            $Test->getDisplayName()
        );
    }

    public function testSetTable(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertSame(
            $Test,
            $Test->setTable('test_table')
        );

        $this->assertSame(
            'test_table',
            $Test->getTable()
        );
    }

}
