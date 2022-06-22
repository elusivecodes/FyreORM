<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model,
    Fyre\ORM\RuleSet,
    Fyre\Validation\Rule,
    Fyre\Validation\Validator;

class Users extends Model
{

    public function __construct()
    {
        $this->addBehavior('Test', [
            'testField' => 'name'
        ]);

        $this->hasOne('Addresses');
        $this->hasMany('Comments');
        $this->hasMany('Posts', [
            'dependent' => true
        ]);
    }

    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(array $entities) {
            foreach ($entities AS $entity) {
                if ($entity->get('name') === 'failRules') {
                    return false;
                }
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add('name', Rule::required(), ['on' => 'create']);

        return $validator;
    }

}
