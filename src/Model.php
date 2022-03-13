<?php
declare(strict_types=1);

namespace Fyre\ORM;

use
    Fyre\DB\Connection,
    Fyre\DB\ConnectionManager,
    Fyre\ORM\Traits\ModelCallbacksTrait,
    Fyre\ORM\Traits\ModelEntityTrait,
    Fyre\ORM\Traits\ModelHelperTrait,
    Fyre\ORM\Traits\ModelParserTrait,
    Fyre\ORM\Traits\ModelQueryTrait,
    Fyre\ORM\Traits\ModelRelationshipsTrait,
    Fyre\ORM\Traits\ModelSchemaTrait,
    Fyre\ORM\Traits\ModelValidationTrait;

use function
    array_key_exists;

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
        ModelCallbacksTrait,
        ModelEntityTrait,
        ModelHelperTrait,
        ModelParserTrait,
        ModelQueryTrait,
        ModelRelationshipsTrait,
        ModelSchemaTrait,
        ModelValidationTrait;

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
        $options['alias'] ??= $this->getTable();

        return $this->query($options + ['subquery' => true]);
    }

}
