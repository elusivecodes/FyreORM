<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries\Traits;

use Fyre\ORM\Model;

/**
 * ModelTrait
 */
trait ModelTrait
{
    protected Model $model;

    /**
     * Get the Model.
     *
     * @return Model The Model.
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
