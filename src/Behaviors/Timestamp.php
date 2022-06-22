<?php
declare(strict_types=1);

namespace Fyre\ORM\Behaviors;

use
    Fyre\DateTime\DateTime,
    Fyre\ORM\Behavior;

/**
 * Timestamp
 */
class Timestamp extends Behavior
{

    protected static array $defaults = [
        'createdField' => 'created',
        'modifiedField' => 'modified'
    ];

    /**
     * Before save callback.
     * @param array $entities The entities.
     * @return bool TRUE if the callback ran successfully.
     */
    public function beforeSave(array $entities): bool
    {
        $createdField = $this->config['createdField'];
        $modifiedField = $this->config['modifiedField'];

        $schema = $this->model->getSchema();

        foreach ($entities AS $entity) {
            if ($entity->isNew() && $schema->hasColumn($createdField)) {
                $entity->set($createdField, DateTime::now());
            }

            if ($schema->hasColumn($modifiedField)) {
                $entity->set($modifiedField, DateTime::now());
            }
        }

        return true;
    }

}

