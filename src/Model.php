<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Fyre\DB\Connection,
    Fyre\DB\ConnectionManager,
    Fyre\ORM\Traits\BehaviorTrait,
    Fyre\ORM\Traits\EntityTrait,
    Fyre\ORM\Traits\HelperTrait,
    Fyre\ORM\Traits\ParserTrait,
    Fyre\ORM\Traits\QueryTrait,
    Fyre\ORM\Traits\RelationshipsTrait,
    Fyre\ORM\Traits\SchemaTrait,
    Fyre\ORM\Traits\ValidationTrait;

use function
    array_key_exists,
    call_user_func_array,
    method_exists;

/**
 * Model
 */
class Model
{

    public const QUERY_METHODS = [
        'fields' => 'select',
        'contain' => 'contain',
        'join' => 'join',
        'conditions' => 'where',
        'order' => 'orderBy',
        'group' => 'groupBy',
        'having' => 'having',
        'limit' => 'limit',
        'offset' => 'offset',
        'epilog' => 'epilog',
        'autoFields' => 'enableAutoFields'
    ];

    public const WRITE = 'write';
    public const READ = 'read';

    protected array $connectionKeys = [
        self::WRITE => 'default'
    ];

    protected array $connections = [];

    use
        BehaviorTrait,
        EntityTrait,
        HelperTrait,
        ParserTrait,
        QueryTrait,
        RelationshipsTrait,
        SchemaTrait,
        ValidationTrait;

    /**
     * Get the Connection.
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
     * @param string $event The event name.
     * @return bool TRUE if the callbacks processed successfully, otherwise FALSE.
     */
    public function handleEvent(string $event, ...$arguments): bool
    {
        if (method_exists($this, $event) && call_user_func_array([$this, $event], $arguments) === false) {
            return false;
        }

        foreach ($this->behaviors AS $behavior) {
            if (method_exists($behavior, $event) && call_user_func_array([$behavior, $event], $arguments) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a new Query.
     * @param array $options The option for the query.
     * @return Query The Query.
     */
    public function query(array $options = []): Query
    {
        return new Query($this, $options);
    }

    /**
     * Set the Connection.
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
     * Create a new subquery Query.
     * @param array $options The option for the query.
     * @return Query The Query.
     */
    public function subquery(array $options = []): Query
    {
        $options['alias'] ??= $this->getAlias();

        return $this->query($options + ['subquery' => true]);
    }

}
