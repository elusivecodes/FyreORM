<?php
declare(strict_types=1);

namespace Tests;

use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\RuleSet;
use Fyre\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{
    use ConnectionTrait;

    public function testExistsIn(): void
    {
        $Users = ModelRegistry::use('Users');
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users'));

        $Posts->setRules($rules);

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );
    }

    public function testExistsInCallback(): void
    {
        $Users = ModelRegistry::use('Users');
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users', [
            'callback' => fn(SelectQuery $q): SelectQuery => $q->where(['id !=' => 1]),
        ]));

        $Posts->setRules($rules);

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );
    }

    public function testExistsInInvalid(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users'));

        $Posts->setRules($rules);

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertSame(
            [
                'user_id' => [
                    'invalid',
                ],
            ],
            $post->getErrors()
        );
    }

    public function testExistsInNull(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users'));

        $Posts->setRules($rules);

        $post = $Posts->newEntity([
            'user_id' => null,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertSame(
            [
                'user_id' => [
                    'invalid',
                ],
            ],
            $post->getErrors()
        );
    }

    public function testExistsInNullNullable(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users', ['allowNullableNulls' => true]));

        $Posts->setRules($rules);

        $post = $Posts->newEntity([
            'user_id' => null,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );
    }

    public function testExistsInTargetFields(): void
    {
        $Users = ModelRegistry::use('Users');
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['title'], 'Users', [
            'targetFields' => ['name'],
        ]));

        $Posts->setRules($rules);

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );
    }

    public function testIsClean(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isClean(['name']));

        $Items->setRules($rules);

        $item = $Items->newEntity([
            'name' => 'Test 1',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $item->name = 'Test 2';

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid',
                ],
            ],
            $item->getErrors()
        );
    }

    public function testIsUnique(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name']));

        $Items->setRules($rules);

        $item1 = $Items->newEntity([
            'name' => 'Test',
        ]);

        $item2 = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item1)
        );

        $this->assertFalse(
            $Items->save($item2)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid',
                ],
            ],
            $item2->getErrors()
        );
    }

    public function testIsUniqueCallback(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name'], [
            'callback' => fn(SelectQuery $q): SelectQuery => $q->where(['name !=' => 'Test']),
        ]));

        $Items->setRules($rules);

        $item1 = $Items->newEntity([
            'name' => 'Test',
        ]);

        $item2 = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item1)
        );

        $this->assertTrue(
            $Items->save($item2)
        );
    }

    public function testIsUniqueNull(): void
    {
        $Items = ModelRegistry::use('Items');

        $validator = new Validator();
        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name']));

        $Items->setValidator($validator);
        $Items->setRules($rules);

        $item1 = $Items->newEntity([
            'name' => null,
        ]);

        $item2 = $Items->newEntity([
            'name' => null,
        ]);

        $this->assertTrue(
            $Items->save($item1)
        );

        $this->assertFalse(
            $Items->save($item2)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid',
                ],
            ],
            $item2->getErrors()
        );
    }

    public function testIsUniqueNullMultiple(): void
    {
        $Items = ModelRegistry::use('Items');

        $validator = new Validator();
        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name'], ['allowMultipleNulls' => true]));

        $Items->setValidator($validator);
        $Items->setRules($rules);

        $item1 = $Items->newEntity([
            'name' => null,
        ]);

        $item2 = $Items->newEntity([
            'name' => null,
        ]);

        $this->assertTrue(
            $Items->save($item1)
        );

        $this->assertTrue(
            $Items->save($item2)
        );
    }

    public function testIsUniqueSaveMany(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name']));

        $Items->setRules($rules);

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'Test',
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid',
                ],
            ],
            $items[1]->getErrors()
        );
    }

    public function testIsUniqueSaveManyNull(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name']));

        $Items->setRules($rules);

        $items = $Items->newEntities([
            [
                'name' => null,
            ],
            [
                'name' => null,
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid',
                ],
            ],
            $items[1]->getErrors()
        );
    }

    public function testIsUniqueUpdate(): void
    {
        $Items = ModelRegistry::use('Items');

        $rules = new RuleSet($Items);

        $rules->add($rules->isUnique(['name']));

        $Items->setRules($rules);

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $item->setDirty('name', true);

        $this->assertTrue(
            $Items->save($item)
        );
    }
}
