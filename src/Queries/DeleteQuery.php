<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

/**
 * DeleteQuery
 */
class DeleteQuery extends \Fyre\DB\Queries\DeleteQuery
{
    use ModelTrait;

    /**
     * New DeleteQuery constructor.
     *
     * @param Model $model The Model.
     * @param array $options The DeleteQuery options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $options['alias'] ??= $this->model->getAlias();

        parent::__construct($this->model->getConnection(), $options['alias']);

        $this->from([
            $options['alias'] => $this->model->getTable(),
        ]);
    }
}
