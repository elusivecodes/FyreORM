<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use Fyre\Entity\Entity;
use Fyre\ORM\Model;
use Fyre\ORM\RuleSet;
use Fyre\Validation\Rule;
use Fyre\Validation\Validator;

class AddressesModel extends Model
{
    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(Entity $entity) {
            if ($entity->get('suburb') === 'failRules') {
                return false;
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add('suburb', Rule::required(), ['on' => 'create']);

        return $validator;
    }

    public function initialize(): void
    {
        $this->addBehavior('Test', [
            'testField' => 'suburb',
        ]);

        $this->belongsTo('Users');
    }
}
