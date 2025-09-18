<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use Fyre\ORM\Model;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Relationships\BelongsTo;
use Fyre\ORM\Relationships\HasMany;
use Fyre\ORM\Relationships\HasOne;
use Fyre\ORM\Relationships\ManyToMany;
use Fyre\Utility\Traits\MacroTrait;
use PHPUnit\Framework\TestCase;
use Tests\Postgres\PostgresConnectionTrait;

use function class_uses;

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
    use NewEntityTestTrait;
    use PatchEntityTestTrait;
    use PostgresConnectionTrait;
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

    public function testMacroable(): void
    {
        $this->assertContains(
            MacroTrait::class,
            class_uses(ModelRegistry::class)
        );

        $this->assertContains(
            MacroTrait::class,
            class_uses(Model::class)
        );

        $this->assertContains(
            MacroTrait::class,
            class_uses(BelongsTo::class)
        );

        $this->assertContains(
            MacroTrait::class,
            class_uses(HasMany::class)
        );

        $this->assertContains(
            MacroTrait::class,
            class_uses(HasOne::class)
        );

        $this->assertContains(
            MacroTrait::class,
            class_uses(ManyToMany::class)
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
