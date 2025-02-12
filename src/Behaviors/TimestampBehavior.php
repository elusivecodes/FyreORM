<?php
declare(strict_types=1);

namespace Fyre\ORM\Behaviors;

use Fyre\DateTime\DateTime;
use Fyre\Entity\Entity;
use Fyre\Event\Event;
use Fyre\ORM\Behavior;

/**
 * TimestampBehavior
 */
class TimestampBehavior extends Behavior
{
    protected static array $defaults = [
        'createdField' => 'created',
        'modifiedField' => 'modified',
    ];

    /**
     * Before save callback.
     *
     * @param Event $event The Event.
     * @param Entity $entity The entity.
     * @return bool TRUE if the callback ran successfully.
     */
    public function beforeSave(Event $event, Entity $entity): bool
    {
        $createdField = $this->config['createdField'];
        $modifiedField = $this->config['modifiedField'];

        $schema = $this->model->getSchema();

        if ($entity->isNew() && $schema->hasColumn($createdField)) {
            $entity->set($createdField, DateTime::now());
        }

        if ($schema->hasColumn($modifiedField)) {
            $entity->set($modifiedField, DateTime::now());
        }

        return true;
    }
}
