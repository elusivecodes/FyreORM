<?php
declare(strict_types=1);

namespace Tests\Mock\Behaviors;

use
    ArrayObject,
    Fyre\Entity\Entity,
    Fyre\ORM\Behavior;

use function
    is_string,
    trim;

class TestBehavior extends Behavior
{

    protected static array $defaults = [
        'testField' => 'name'
    ];

    public function afterDelete(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failAfterDelete') {
                return false;
            }
        }
    }

    public function afterParse(Entity $entity)
    {
        if ($entity->get($this->config['testField']) === 'afterParse') {
            $entity->test = 1;
        }
    }

    public function afterRules(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failAfterRules') {
                return false;
            }
        }
    }

    public function afterSave(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failAfterSave') {
                return false;
            }
        }
    }

    public function beforeDelete(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failBeforeDelete') {
                return false;
            }
        }
    }

    public function beforeParse(ArrayObject $data)
    {
        $testField = $this->config['testField'];

        if ($data->offsetExists($testField) && is_string($data[$testField])) {
            $data[$testField] = trim($data[$testField]);
        }
    }

    public function beforeRules(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failBeforeRules') {
                return false;
            }
        }
    }

    public function beforeSave(array $entities)
    {
        foreach ($entities AS $entity) {
            if ($entity->get($this->config['testField']) === 'failBeforeSave') {
                return false;
            }
        }
    }

}
