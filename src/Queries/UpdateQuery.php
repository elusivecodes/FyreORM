<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;
use Fyre\Utility\Traits\MacroTrait;

/**
 * UpdateQuery
 */
class UpdateQuery extends \Fyre\DB\Queries\UpdateQuery
{
    use MacroTrait;
    use ModelTrait;

    /**
     * New Query constructor.
     *
     * @param Model $model The Model.
     */
    public function __construct(
        protected Model $model
    ) {
        parent::__construct($this->model->getConnection(), $this->model->getTable());
    }
}
