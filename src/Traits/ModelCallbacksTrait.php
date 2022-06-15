<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    Fyre\Entity\Entity,
    Fyre\ORM\Query,
    Fyre\ORM\Result;

/**
 * ModelCallbacksTrait
 */
trait ModelCallbacksTrait
{

    /**
     * After delete callback.
     * @param Entity $entity The Entity.
     */
    public function afterDelete(Entity $entity)
    {
        return true;
    }

    /**
     * After find callback.
     * @param Result $result The Result.
     * @return Result The Result.
     */
    public function afterFind(Result $result): Result
    {
        return $result;
    }

    /**
     * After rules callback.
     * @param Entity $entity The Entity.
     */
    public function afterRules(Entity $entity)
    {
        return true;
    }

    /**
     * After save callback.
     * @param Entity $entity The Entity.
     */
    public function afterSave(Entity $entity)
    {
        return true;
    }

    /**
     * Before delete callback.
     * @param Entity $entity The Entity.
     */
    public function beforeDelete(Entity $entity)
    {
        return true;
    }

    /**
     * Before find callback.
     * @param Query $query The Query.
     * @return Query The Query.
     */
    public function beforeFind(Query $query): Query
    {
        return $query;
    }

    /**
     * Before rules callback.
     * @param Entity $entity The Entity.
     */
    public function beforeRules(Entity $entity)
    {
        return true;
    }

    /**
     * Before save callback.
     * @param Entity $entity The Entity.
     */
    public function beforeSave(Entity $entity)
    {
        return true;
    }

}
