<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\DB\ConnectionManager,
    Fyre\ORM\ModelRegistry,
    PHPUnit\Framework\TestCase,
    Tests\ConnectionTrait;

final class ModelTest extends TestCase
{

    use
        BehaviorTest,
        BelongsToCallbacksTest,
        BelongsToTest,
        CallbacksBelongsToTest,
        CallbacksHasManyTest,
        CallbacksHasOneTest,
        CallbacksManyToManyTest,
        CallbacksTest,
        ConnectionTrait,
        ContainTest,
        NewEntityTest,
        HasManyCallbacksTest,
        HasManyTest,
        HasOneCallbacksTest,
        HasOneTest,
        JoinTest,
        LoadIntoTest,
        ManyToManyCallbacksTest,
        ManyToManyTest,
        MatchingTest,
        PatchEntityTest,
        QueryTest,
        RelationshipTest,
        SchemaTest;

    public function testConnection(): void
    {
        $this->assertSame(
            ConnectionManager::use(),
            ModelRegistry::use('Test')->getConnection()
        );
    }

}
