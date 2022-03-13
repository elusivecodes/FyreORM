<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

use function
    array_map;

trait RelationshipTest
{

    public function testRelationshipClassName(): void
    {
        $Test = ModelRegistry::use('Test');

        $relationship = $Test->hasOne('Alias', [
            'className' => 'Test'
        ]);

        $this->assertSame(
            $Test,
            $relationship->getTarget()
        );
    }

    public function testRelationshipPropertyName(): void
    {
        $Test = ModelRegistry::use('Test');

        $relationship = $Test->hasOne('Alias', [
            'className' => 'Test',
            'propertyName' => 'alias'
        ]);

        $this->assertSame(
            'alias',
            $relationship->getProperty()
        );
    }

    public function testRelationshipKeys(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->hasOne('Alias', [
            'className' => 'Test',
            'foreignKey' => 'name',
            'bindingKey' => 'name'
        ]);

        $this->assertSame(
            'SELECT Test.id AS Test__id FROM test AS Test LEFT JOIN test AS Alias ON Alias.name = Test.name',
            $Test->find()
                ->enableAutoFields(false)
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipConditions(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->hasOne('Alias', [
            'className' => 'Test',
            'conditions' => [
                'Alias.name' => 'Test'
            ]
        ]);

        $this->assertSame(
            'SELECT Test.id AS Test__id FROM test AS Test LEFT JOIN test AS Alias ON test_id = Test.id AND Alias.name = \'Test\'',
            $Test->find()
                ->enableAutoFields(false)
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipThrough(): void
    {
        $Test = ModelRegistry::use('Test');
        $TestAlias = ModelRegistry::use('TestAlias');

        $relationship = $Test->manyToMany('Alias', [
            'className' => 'Test',
            'through' => 'TestAlias'
        ]);

        $TestAlias->setTable('test');

        $this->assertSame(
            $TestAlias,
            $relationship->getJoinModel()
        );
    }

}
