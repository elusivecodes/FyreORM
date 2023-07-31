<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use Fyre\ORM\RuleSet;
use Fyre\Validation\Validator;

/**
 * ValidationTrait
 */
trait ValidationTrait
{

    protected Validator $validator;

    protected RuleSet $rules;

    /**
     * Build the model RuleSet.
     * @param RuleSet $rules The RuleSet.
     * @return RuleSet The RuleSet.
     */
    public function buildRules(RuleSet $rules): RuleSet
    {
        return $rules;
    }

    /**
     * Build the model Validator.
     * @param Validator $validator The Validator.
     * @return Validator The Validator.
     */
    public function buildValidation(Validator $validator): Validator
    {
        return $validator;
    }

    /**
     * Get the model RuleSet.
     * @return RuleSet The RuleSet.
     */
    public function getRules(): RuleSet
    {
        return $this->rules ?? $this->buildRules(new RuleSet($this));
    }

    /**
     * Get the model Validator.
     * @return Validator The Validator.
     */
    public function getValidator(): Validator
    {
        return $this->validator ?? $this->buildValidation(new Validator);
    }

    /**
     * Set the model RuleSet.
     * @param RuleSet $rules The RuleSet.
     * @return Model The Model.
     */
    public function setRules(RuleSet $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Set the model Validator.
     * @param Validator $validator The Validator.
     * @return Model The Model.
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

}
