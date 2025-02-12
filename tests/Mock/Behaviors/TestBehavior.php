<?php
declare(strict_types=1);

namespace Tests\Mock\Behaviors;

use ArrayObject;
use Fyre\Entity\Entity;
use Fyre\Event\Event;
use Fyre\ORM\Behavior;

use function is_string;
use function trim;

class TestBehavior extends Behavior
{
    protected static array $defaults = [
        'testField' => 'name',
    ];

    public function afterDelete(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failAfterDelete') {
            return false;
        }

        return true;
    }

    public function afterParse(Event $event, Entity $entity): void
    {
        if ($entity->get($this->config['testField']) === 'afterParse') {
            $entity->test = 1;
        }
    }

    public function afterRules(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failAfterRules') {
            return false;
        }

        return true;
    }

    public function afterSave(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failAfterSave') {
            return false;
        }

        return true;
    }

    public function beforeDelete(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failBeforeDelete') {
            return false;
        }

        return true;
    }

    public function beforeParse(Event $event, ArrayObject $data): void
    {
        $testField = $this->config['testField'];

        if ($data->offsetExists($testField) && is_string($data[$testField])) {
            $data[$testField] = trim($data[$testField]);
        }
    }

    public function beforeRules(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failBeforeRules') {
            return false;
        }

        return true;
    }

    public function beforeSave(Event $event, Entity $entity): bool
    {
        if ($entity->get($this->config['testField']) === 'failBeforeSave') {
            return false;
        }

        return true;
    }
}
