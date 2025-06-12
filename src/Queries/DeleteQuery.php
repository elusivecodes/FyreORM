<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\DB\DbFeature;
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
    public function __construct(
        protected Model $model,
        array $options = []
    ) {
        $options['alias'] ??= $this->model->getAlias();

        $connection = $this->model->getConnection();
        $alias = $connection->supports(DbFeature::DeleteAlias) ? $options['alias'] : null;

        parent::__construct($connection, $alias);

        $this->from([
            $options['alias'] => $this->model->getTable(),
        ]);
    }
}
