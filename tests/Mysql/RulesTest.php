<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\RuleSet;
use Fyre\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testExistsIn(): void
    {
        $Users = $this->modelRegistry->use('Users');
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
        $Users = $this->modelRegistry->use('Users');
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
                    'The user_id must exist in the Users table.',
                ],
            ],
            $post->getErrors()
        );
    }

    public function testExistsInNull(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
                    'The user_id must exist in the Users table.',
                ],
            ],
            $post->getErrors()
        );
    }

    public function testExistsInNullNullable(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
        $Users = $this->modelRegistry->use('Users');
        $Posts = $this->modelRegistry->use('Posts');

        $rules = $this->container->build(RuleSet::class, ['model' => $Posts]);

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
        $Items = $this->modelRegistry->use('Items');

        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
                    'The name cannot be modified.',
                ],
            ],
            $item->getErrors()
        );
    }

    public function testIsUnique(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
                    'The name must be unique.',
                ],
            ],
            $item2->getErrors()
        );
    }

    public function testIsUniqueCallback(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
        $Items = $this->modelRegistry->use('Items');

        $validator = $this->container->build(Validator::class);
        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
                    'The name must be unique.',
                ],
            ],
            $item2->getErrors()
        );
    }

    public function testIsUniqueNullMultiple(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $validator = $this->container->build(Validator::class);
        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
        $Items = $this->modelRegistry->use('Items');

        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
                    'The name must be unique.',
                ],
            ],
            $items[1]->getErrors()
        );
    }

    public function testIsUniqueSaveManyNull(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $validator = $this->container->build(Validator::class);
        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

        $rules->add($rules->isUnique(['name']));

        $Items->setValidator($validator);
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
                    'The name must be unique.',
                ],
            ],
            $items[1]->getErrors()
        );
    }

    public function testIsUniqueUpdate(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $rules = $this->container->build(RuleSet::class, ['model' => $Items]);

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
