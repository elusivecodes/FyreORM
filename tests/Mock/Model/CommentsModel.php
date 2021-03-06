<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model,
    Fyre\ORM\RuleSet,
    Fyre\Validation\Rule,
    Fyre\Validation\Validator;

class CommentsModel extends Model
{

    public function __construct()
    {
        $this->addBehavior('Test', [
            'testField' => 'content'
        ]);

        $this->belongsTo('Users');
        $this->belongsTo('Posts');
    }

    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(array $entities) {
            foreach ($entities AS $entity) {
                if ($entity->get('content') === 'failRules') {
                    return false;
                }
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add('content', Rule::required(), ['on' => 'create']);

        return $validator;
    }

}
