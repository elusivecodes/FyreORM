<?php
declare(strict_types=1);

namespace Tests\Model;

use Fyre\DB\ConnectionManager;
use Fyre\ORM\ModelRegistry;
use PHPUnit\Framework\TestCase;
use Tests\ConnectionTrait;

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
    use ConnectionTrait;
    use ContainTestTrait;
    use NewEntityTestTrait;
    use HasManyCallbacksTestTrait;
    use HasManyTestTrait;
    use HasOneCallbacksTestTrait;
    use HasOneTestTrait;
    use JoinTestTrait;
    use LoadIntoTestTrait;
    use ManyToManyCallbacksTestTrait;
    use ManyToManyTestTrait;
    use MatchingTestTrait;
    use PatchEntityTestTrait;
    use QueryTestTrait;
    use RelationshipTestTrait;
    use SchemaTestTrait;

    public function testConnection(): void
    {
        $this->assertSame(
            ConnectionManager::use(),
            ModelRegistry::use('Test')->getConnection()
        );
    }

    public function testGetNamespaces(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\Model\\'
            ],
            ModelRegistry::getNamespaces()
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            ModelRegistry::hasNamespace('Tests\Mock\Model')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            ModelRegistry::hasNamespace('Tests\Invalid\Model')
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertTrue(
            ModelRegistry::removeNamespace('Tests\Mock\Model')
        );

        $this->assertFalse(
            ModelRegistry::hasNamespace('Tests\Mock\Model')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertFalse(
            ModelRegistry::removeNamespace('Tests\Invalid\Model')
        );
    }

}
