<?php
declare(strict_types=1);

namespace Fyre\ORM;

use Fyre\DB\Connection;
use Fyre\DB\ConnectionManager;
use Fyre\ORM\Queries\DeleteQuery;
use Fyre\ORM\Queries\InsertQuery;
use Fyre\ORM\Queries\ReplaceQuery;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\Queries\UpdateBatchQuery;
use Fyre\ORM\Queries\UpdateQuery;
use Fyre\ORM\Traits\BehaviorTrait;
use Fyre\ORM\Traits\EntityTrait;
use Fyre\ORM\Traits\HelperTrait;
use Fyre\ORM\Traits\ParserTrait;
use Fyre\ORM\Traits\QueryTrait;
use Fyre\ORM\Traits\RelationshipsTrait;
use Fyre\ORM\Traits\SchemaTrait;
use Fyre\ORM\Traits\ValidationTrait;

use function array_key_exists;
use function call_user_func_array;
use function method_exists;

/**
 * Model
 */
class Model
{
    use BehaviorTrait;
    use EntityTrait;
    use HelperTrait;
    use ParserTrait;
    use QueryTrait;
    use RelationshipsTrait;
    use SchemaTrait;
    use ValidationTrait;

    public const READ = 'read';

    public const WRITE = 'write';

    protected array $connectionKeys = [
        self::WRITE => 'default',
    ];

    protected array $connections = [];

    /**
     * Create a new DeleteQuery.
     *
     * @param array $options The option for the query.
     * @return DeleteQuery The DeleteQuery.
     */
    public function deleteQuery(array $options = []): DeleteQuery
    {
        return new DeleteQuery($this, $options);
    }

    /**
     * Get the Connection.
     *
     * @param string|null $type The connection type.
     * @return Connection The Connection.
     */
    public function getConnection(string|null $type = null): Connection
    {
        if (!array_key_exists($type, $this->connections) && !array_key_exists($type, $this->connectionKeys)) {
            $type = static::WRITE;
        }

        return $this->connections[$type] ??= ConnectionManager::use($this->connectionKeys[$type] ?? $this->connectionKeys[static::WRITE]);
    }

    /**
     * Handle an event callbacks.
     *
     * @param string $event The event name.
     * @param array $arguments
     * @return bool TRUE if the callbacks processed successfully, otherwise FALSE.
     */
    public function handleEvent(string $event, ...$arguments): bool
    {
        if (method_exists($this, $event) && call_user_func_array([$this, $event], $arguments) === false) {
            return false;
        }

        foreach ($this->behaviors as $behavior) {
            if (method_exists($behavior, $event) && call_user_func_array([$behavior, $event], $arguments) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new InsertQuery.
     *
     * @return InsertQuery The InsertQuery.
     */
    public function insertQuery(): InsertQuery
    {
        return new InsertQuery($this);
    }

    /**
     * Create a new ReplaceQuery.
     *
     * @return ReplaceQuery The ReplaceQuery.
     */
    public function ReplaceQuery(): ReplaceQuery
    {
        return new ReplaceQuery($this);
    }

    /**
     * Create a new SelectQuery.
     *
     * @param array $options The option for the query.
     * @return SelectQuery The SelectQuery.
     */
    public function selectQuery(array $options = []): SelectQuery
    {
        return new SelectQuery($this, $options);
    }

    /**
     * Set the Connection.
     *
     * @param Connection $connection The Connection.
     * @param string $type The connection type.
     * @return Model The Model.
     */
    public function setConnection(Connection $connection, string $type = self::WRITE): static
    {
        $this->connections[$type] = $connection;

        return $this;
    }

    /**
     * Create a new subquery SelectQuery.
     *
     * @param array $options The option for the query.
     * @return SelectQuery The SelectQuery.
     */
    public function subquery(array $options = []): SelectQuery
    {
        return $this->selectQuery($options + ['subquery' => true]);
    }

    /**
     * Create a new UpdateBatchQuery.
     *
     * @param array $options The option for the query.
     * @return UpdateBatchQuery The UpdateBatchQuery.
     */
    public function updateBatchQuery(array $options = []): UpdateBatchQuery
    {
        return new UpdateBatchQuery($this, $options);
    }

    /**
     * Create a new UpdateQuery.
     *
     * @param array $options The option for the query.
     * @return UpdateQuery The UpdateQuery.
     */
    public function updateQuery(array $options = []): UpdateQuery
    {
        return new UpdateQuery($this, $options);
    }
}
