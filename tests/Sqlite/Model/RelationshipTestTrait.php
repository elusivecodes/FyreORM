<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use Fyre\ORM\ModelRegistry;

trait RelationshipTestTrait
{
    public function testRelationshipClassName(): void
    {
        $Items = ModelRegistry::use('Items');

        $relationship = $Items->hasOne('Alias', [
            'className' => 'Items',
        ]);

        $this->assertSame(
            $Items,
            $relationship->getTarget()
        );
    }

    public function testRelationshipConditions(): void
    {
        $Items = ModelRegistry::use('Items');

        $Items->hasOne('Alias', [
            'className' => 'Items',
            'conditions' => [
                'Alias.name' => 'Test',
            ],
        ]);

        $this->assertSame(
            'SELECT Items.id AS Items__id FROM items AS Items LEFT JOIN items AS Alias ON item_id = Items.id AND Alias.name = \'Test\'',
            $Items->find()
                ->enableAutoFields(false)
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipKeys(): void
    {
        $Items = ModelRegistry::use('Items');

        $Items->hasOne('Alias', [
            'className' => 'Items',
            'foreignKey' => 'name',
            'bindingKey' => 'name',
        ]);

        $this->assertSame(
            'SELECT Items.id AS Items__id FROM items AS Items LEFT JOIN items AS Alias ON Alias.name = Items.name',
            $Items->find()
                ->enableAutoFields(false)
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipPropertyName(): void
    {
        $Items = ModelRegistry::use('Items');

        $relationship = $Items->hasOne('Alias', [
            'className' => 'Items',
            'propertyName' => 'alias',
        ]);

        $this->assertSame(
            'alias',
            $relationship->getProperty()
        );
    }

    public function testRelationshipThrough(): void
    {
        $Items = ModelRegistry::use('Items');
        $ItemsAlias = ModelRegistry::use('ItemsAlias');

        $relationship = $Items->manyToMany('Alias', [
            'className' => 'Items',
            'through' => 'ItemsAlias',
        ]);

        $ItemsAlias->setTable('items');

        $this->assertSame(
            $ItemsAlias,
            $relationship->getJoinModel()
        );
    }
}
