<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use PHPUnit\Framework\TestCase;
use Tests\Mysql\MysqlConnectionTrait;

final class ModelTest extends TestCase
{
    use BehaviorTestTrait;
    use BelongsToCallbacksTestTrait;
    use BelongsToTestTrait;
    use CallbacksBelongsToTestTrait;
    use CallbacksHasManyTestTrait;
    use CallbacksHasOneTestTrait;
    use CallbacksManyToManyTestTrait;
    use CallbacksTestTrait;
    use ContainTestTrait;
    use HasManyCallbacksTestTrait;
    use HasManyTestTrait;
    use HasOneCallbacksTestTrait;
    use HasOneTestTrait;
    use JoinTestTrait;
    use LoadIntoTestTrait;
    use ManyToManyCallbacksTestTrait;
    use ManyToManyTestTrait;
    use MatchingTestTrait;
    use MysqlConnectionTrait;
    use NewEntityTestTrait;
    use PatchEntityTestTrait;
    use QueryTestTrait;
    use RelationshipTestTrait;
    use SchemaTestTrait;

    public function testConnection(): void
    {
        $this->assertSame(
            $this->db,
            $this->modelRegistry->use('Test')->getConnection()
        );
    }

    public function testGetNamespaces(): void
    {
        $this->assertSame(
            [
                'Tests\Mock\Model\\',
            ],
            $this->modelRegistry->getNamespaces()
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            $this->modelRegistry->hasNamespace('Tests\Mock\Model')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            $this->modelRegistry->hasNamespace('Tests\Invalid\Model')
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertSame(
            $this->modelRegistry,
            $this->modelRegistry->removeNamespace('Tests\Mock\Model')
        );

        $this->assertFalse(
            $this->modelRegistry->hasNamespace('Tests\Mock\Model')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertSame(
            $this->modelRegistry,
            $this->modelRegistry->removeNamespace('Tests\Invalid\Model')
        );
    }
}
