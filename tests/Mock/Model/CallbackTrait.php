<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\Entity\Entity,
    Fyre\ORM\RuleSet,
    Fyre\Validation\Rule,
    Fyre\Validation\Validator;

trait CallbackTrait
{

    public function afterDelete(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failAfterDelete') {
            return false;
        }
    }

    public function afterRules(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failAfterRules') {
            return false;
        }
    }

    public function afterSave(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failAfterSave') {
            return false;
        }
    }

    public function beforeDelete(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failBeforeDelete') {
            return false;
        }
    }

    public function beforeRules(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failBeforeRules') {
            return false;
        }
    }

    public function beforeSave(Entity $entity)
    {
        if ($entity->get($this->testProperty) === 'failBeforeSave') {
            return false;
        }
    }

    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(Entity $entity) {
            if ($entity->get($this->testProperty) === 'failRules') {
                return false;
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add($this->testProperty, Rule::required(), ['on' => 'create']);

        return $validator;
    }

}
