<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use Fyre\ORM\Model;
use Fyre\ORM\RuleSet;
use Fyre\Validation\Rule;
use Fyre\Validation\Validator;

class TagsModel extends Model
{

    public function __construct()
    {
        $this->addBehavior('Test', [
            'testField' => 'tag'
        ]);

        $this->manyToMany('Posts');
    }

    public function buildRules(RuleSet $rules): RuleSet
    {
        $rules->add(function(array $entities) {
            foreach ($entities AS $entity) {
                if ($entity->get('tag') === 'failRules') {
                    return false;
                }
            }
        });

        return $rules;
    }

    public function buildValidation(Validator $validator): Validator
    {
        $validator->add('tag', Rule::required(), ['on' => 'create']);

        return $validator;
    }

}
