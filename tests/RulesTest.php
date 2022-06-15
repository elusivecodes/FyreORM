<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\Validation\Validator,
    Fyre\ORM\ModelRegistry,
    Fyre\ORM\RuleSet,
    PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase
{

    use
        ConnectionTrait;

    public function testIsUnique(): void
    {
        $Test = ModelRegistry::use('Test');

        $rules = new RuleSet($Test);

        $rules->add($rules->isUnique(['name']));

        $Test->setRules($rules);

        $test1 = $Test->newEntity([
            'name' => 'Test'
        ]);

        $test2 = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test1)
        );

        $this->assertFalse(
            $Test->save($test2)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid'
                ]
            ],
            $test2->getErrors()
        );
    }

    public function testIsUniqueNull(): void
    {
        $Test = ModelRegistry::use('Test');

        $validator = new Validator();
        $rules = new RuleSet($Test);

        $rules->add($rules->isUnique(['name']));

        $Test->setValidator($validator);
        $Test->setRules($rules);

        $test1 = $Test->newEntity([
            'name' => null
        ]);

        $test2 = $Test->newEntity([
            'name' => null
        ]);

        $this->assertTrue(
            $Test->save($test1)
        );

        $this->assertFalse(
            $Test->save($test2)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid'
                ]
            ],
            $test2->getErrors()
        );
    }

    public function testIsUniqueNullMultiple(): void
    {
        $Test = ModelRegistry::use('Test');

        $validator = new Validator();
        $rules = new RuleSet($Test);

        $rules->add($rules->isUnique(['name'], ['allowMultipleNulls' => true]));

        $Test->setValidator($validator);
        $Test->setRules($rules);

        $test1 = $Test->newEntity([
            'name' => null
        ]);

        $test2 = $Test->newEntity([
            'name' => null
        ]);

        $this->assertTrue(
            $Test->save($test1)
        );

        $this->assertTrue(
            $Test->save($test2)
        );
    }

    public function testIsUniqueSaveMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $rules = new RuleSet($Test);

        $rules->add($rules->isUnique(['name']));

        $Test->setRules($rules);

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'Test'
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid'
                ]
            ],
            $tests[1]->getErrors()
        );
    }

    public function testIsUniqueSaveManyNull(): void
    {
        $Test = ModelRegistry::use('Test');

        $rules = new RuleSet($Test);

        $rules->add($rules->isUnique(['name']));

        $Test->setRules($rules);

        $tests = $Test->newEntities([
            [
                'name' => null
            ],
            [
                'name' => null
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            [
                'name' => [
                    'invalid'
                ]
            ],
            $tests[1]->getErrors()
        );
    }

    public function testExistsIn(): void
    {
        $Users = ModelRegistry::use('Users');
        $Posts = ModelRegistry::use('Posts');

        $rules = new RuleSet($Posts);

        $rules->add($rules->existsIn(['user_id'], 'Users'));

        $Posts->setRules($rules);

        $user = $Users->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.'
        ]);

        $this->assertTrue(
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
            'content' => 'This is the content.'
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertSame(
            [
                'user_id' => [
                    'invalid'
                ],
                'title' => [],
                'content' => []
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
            'content' => 'This is the content.'
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertSame(
            [
                'user_id' => [
                    'invalid'
                ],
                'title' => [],
                'content' => []
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
            'content' => 'This is the content.'
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );
    }

}
