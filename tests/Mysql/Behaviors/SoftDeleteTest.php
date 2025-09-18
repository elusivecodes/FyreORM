<?php
declare(strict_types=1);

namespace Tests\Mysql\Behaviors;

use Fyre\DateTime\DateTime;
use Fyre\Entity\Entity;
use PHPUnit\Framework\TestCase;
use Tests\Mysql\MysqlConnectionTrait;

final class SoftDeleteTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testDelete(): void
    {
        $Users = $this->modelRegistry->use('Users');
        $Addresses = $this->modelRegistry->use('Addresses');
        $Posts = $this->modelRegistry->use('Posts');
        $Comments = $this->modelRegistry->use('Comments');

        $Users->addBehavior('SoftDelete');
        $Addresses->addBehavior('SoftDelete');
        $Posts->addBehavior('SoftDelete');
        $Comments->addBehavior('SoftDelete');

        $Users->Addresses->setDependent(true);
        $Posts->Comments->setDependent(true);

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                    'comments' => [
                        [
                            'content' => 'This is a comment',
                            'user' => [
                                'name' => 'Test 2',
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.',
                    'comments' => [
                        [
                            'content' => 'This is a comment',
                            'user' => [
                                'name' => 'Test 3',
                            ],
                        ],
                    ],
                ],
            ],
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertInstanceOf(
            DateTime::class,
            $user->deleted
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            3,
            $Users->find(['deleted' => true])->count()
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            2,
            $Posts->find(['deleted' => true])->count()
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            1,
            $Addresses->find(['deleted' => true])->count()
        );

        $this->assertSame(
            0,
            $Comments->find()->count()
        );

        $this->assertSame(
            2,
            $Comments->find(['deleted' => true])->count()
        );
    }

    public function testFindOnlyDeleted(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->delete($users[0])
        );

        $this->assertSame(
            [1],
            $Users->findOnlyDeleted()
                ->all()
                ->map(fn(Entity $item): int => $item->id)
                ->toArray()
        );
    }

    public function testFindWithDeleted(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->delete($users[0])
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            [1, 2],
            $Users->findWithDeleted()
                ->orderBy(['id' => 'ASC'])
                ->all()
                ->map(fn(Entity $item): int => $item->id)
                ->toArray()
        );
    }

    public function testPurge(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->purge($users[0])
        );

        $this->assertSame(
            [2],
            $Users->find(['deleted' => true])
                ->all()
                ->map(fn(Entity $item): int => $item->id)
                ->toArray()
        );
    }

    public function testPurgeMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->purgeMany($users)
        );

        $this->assertSame(
            0,
            $Users->find(['deleted' => true])->count()
        );
    }

    public function testRestore(): void
    {

        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->delete($users[0])
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            $Users->find(['deleted' => true])->count()
        );

        $this->assertTrue(
            $Users->restore($users[0])
        );

        $this->assertNull(
            $users[0]->deleted
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            [1, 2],
            $Users->find()
                ->orderBy(['id' => 'ASC'])
                ->all()
                ->map(fn(Entity $item): int => $item->id)
                ->toArray()
        );
    }

    public function testRestoreMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->addBehavior('SoftDelete');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            $Users->find(['deleted' => true])->count()
        );

        $this->assertTrue(
            $Users->restoreMany($users)
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            [1, 2],
            $Users->find()
                ->orderBy(['id' => 'ASC'])
                ->all()
                ->map(fn(Entity $item): int => $item->id)
                ->toArray()
        );
    }
}
