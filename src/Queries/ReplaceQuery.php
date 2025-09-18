<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;
use Fyre\Utility\Traits\MacroTrait;

/**
 * ReplaceQuery
 */
class ReplaceQuery extends \Fyre\DB\Queries\ReplaceQuery
{
    use MacroTrait;
    use ModelTrait;

    /**
     * New ReplaceQuery constructor.
     *
     * @param Model $model The Model.
     */
    public function __construct(
        protected Model $model
    ) {
        parent::__construct($this->model->getConnection());

        $this->into($this->model->getTable());
    }
}
