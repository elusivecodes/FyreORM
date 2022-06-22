<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model,
    Fyre\ORM\RuleSet,
    Fyre\Validation\Rule,
    Fyre\Validation\Validator;

class Posts extends Model
{

    public function __construct()
    {
        $this->addBehavior('Test', [
            'testField' => 'title'
        ]);

        $this->belongsTo('Users');
        $this->hasMany('Comments');
        $this->manyToMany('Tags');
    }

    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(array $entities) {
            foreach ($entities AS $entity) {
                if ($entity->get('title') === 'failRules') {
                    return false;
                }
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add('title', Rule::required(), ['on' => 'create']);

        return $validator;
    }

}
