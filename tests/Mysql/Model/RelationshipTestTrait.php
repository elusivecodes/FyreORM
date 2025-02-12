<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

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
            'SELECT ChildItems.id AS ChildItems__id FROM items AS ChildItems INNER JOIN contains AS Contains ON Contains.contained_item_id = ChildItems.id INNER JOIN items AS ParentItems ON ParentItems.id = Contains.item_id',
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
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setConditions([
                'Alias.name' => 'Test',
            ])
        );

        $this->assertSame(
            [
                'Alias.name' => 'Test',
            ],
            $Items->Alias->getConditions()
        );

        $this->assertSame(
            'SELECT Items.id AS Items__id FROM items AS Items LEFT JOIN items AS Alias ON item_id = Items.id AND Alias.name = \'Test\'',
            $Items->find()
                ->disableAutoFields()
                ->leftJoinWith('Alias')
                ->sql()
        );
    }

    public function testRelationshipJoinType(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasOne('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setJoinType('inner')
        );

        $this->assertSame('inner', $Items->Alias->getJoinType());
    }

    public function testRelationshipKeys(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasOne('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setBindingKey('name')
        );

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setForeignKey('name')
        );

        $this->assertSame(
            'name',
            $Items->Alias->getBindingKey()
        );

        $this->assertSame(
            'name',
            $Items->Alias->getForeignKey()
        );

        $this->assertSame(
            'SELECT Items.id AS Items__id FROM items AS Items LEFT JOIN items AS Alias ON Alias.name = Items.name',
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
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setProperty('alias')
        );

        $this->assertSame(
            'alias',
            $relationship->getProperty()
        );
    }

    public function testRelationshipSort(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasMany('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setSort('Alias.sort')
        );

        $this->assertSame(
            'Alias.sort',
            $Items->Alias->getSort()
        );
    }

    public function testRelationshipSource(): void
    {
        $Items = $this->modelRegistry->use('Items');
        $Others = $this->modelRegistry->use('Others');

        $Items->hasMany('Alias', [
            'className' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setSource($Others)
        );

        $this->assertSame(
            $Others,
            $Items->Alias->getSource()
        );
    }

    public function testRelationshipStrategy(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasMany('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setStrategy('subquery')
        );

        $this->assertSame(
            'subquery',
            $Items->Alias->getStrategy()
        );
    }

    public function testRelationshipTarget(): void
    {
        $Items = $this->modelRegistry->use('Items');
        $Others = $this->modelRegistry->use('Others');

        $Items->hasMany('Alias', [
            'className' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setTarget($Others)
        );

        $this->assertSame(
            $Others,
            $Items->Alias->getTarget()
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
            $relationship->getJunction()
        );
    }

    public function testSetRelationshipDependent(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->hasOne('Alias', [
            'classAlias' => 'Items',
        ]);

        $this->assertSame(
            $Items->Alias,
            $Items->Alias->setDependent(true)
        );

        $this->assertTrue($Items->Alias->isDependent());
    }
}
