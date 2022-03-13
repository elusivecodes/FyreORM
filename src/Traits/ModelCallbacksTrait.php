<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    Fyre\Entity\Entity;

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
