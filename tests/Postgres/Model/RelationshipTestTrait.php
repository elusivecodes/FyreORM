<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

trait RelationshipTestTrait
{
    public function testRelationshipAliasModel(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->manyToMany('ChildItems', [
            'classAlias' => 'Items',
            'through' => 'Contains',
            'foreignKey' => 'item_id',
            'targetForeignKey' => 'contained_item_id',
        ]);

        $this->assertSame(
            $Items->getRelationship('ChildItems'),
            $Items->ChildItems
        );

        $Items->ChildItems->manyToMany('ParentItems', [
            'classAlias' => 'Items',
            'through' => 'Contains',
            'foreignKey' => 'contained_item_id',
            'targetForeignKey' => 'item_id',
        ]);

        $this->assertSame(
            'SELECT ChildItems.id AS "ChildItems__id" FROM items AS ChildItems INNER JOIN contains AS Contains ON Contains.contained_item_id = ChildItems.id INNER JOIN items AS ParentItems ON ParentItems.id = Contains.item_id',
            $Items->ChildItems->find()
                ->innerJoinWith('ParentItems')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testRelationshipClassAlias(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $relationship = $Items->hasOne('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            'Items',
            $relationship->getTarget()->getClassAlias()
        );
    }

    public function testRelationshipConditions(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasOne('Alias', [
            'classAlias' => 'Items',
            'conditions' => [
                'Alias.name' => 'Test',
            ],
        ]);

        $this->assertSame(
            'SELECT Items.id AS "Items__id" FROM items AS Items LEFT JOIN items AS Alias ON item_id = Items.id AND Alias.name = \'Test\'',
            $Items->find()
                ->disableAutoFields()
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipKeys(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasOne('Alias', [
            'classAlias' => 'Items',
            'foreignKey' => 'name',
            'bindingKey' => 'name',
        ]);

        $this->assertSame(
            'SELECT Items.id AS "Items__id" FROM items AS Items LEFT JOIN items AS Alias ON Alias.name = Items.name',
            $Items->find()
                ->disableAutoFields()
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipPropertyName(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $relationship = $Items->hasOne('Alias', [
            'classAlias' => 'Items',
            'propertyName' => 'alias',
        ]);

        $this->assertSame(
            'alias',
            $relationship->getProperty()
        );
    }

    public function testRelationshipThrough(): void
    {
        $Items = $this->modelRegistry->use('Items');
        $ItemsAlias = $this->modelRegistry->use('ItemsAlias');

        $relationship = $Items->manyToMany('Alias', [
            'classAlias' => 'Items',
            'through' => 'ItemsAlias',
        ]);

        $ItemsAlias->setTable('items');

        $this->assertSame(
            $ItemsAlias,
            $relationship->getJoinModel()
        );
    }
}
