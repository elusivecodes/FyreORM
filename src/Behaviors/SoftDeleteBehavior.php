<?php
declare(strict_types=1);

namespace Fyre\ORM\Behaviors;

use Fyre\DateTime\DateTime;
use Fyre\Entity\Entity;
use Fyre\Event\Event;
use Fyre\ORM\Behavior;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\Relationship;
use Fyre\ORM\Relationships\HasMany;
use Fyre\ORM\Relationships\HasOne;
use Traversable;

use function array_filter;

/**
 * SoftDeleteBehavior
 */
class SoftDeleteBehavior extends Behavior
{
    protected static array $defaults = [
        'field' => 'deleted',
    ];

    /**
     * Before delete callback.
     *
     * @param Event $event The Event.
     * @param Entity $entity The entity.
     * @param array $options The options for deleting.
     * @return bool TRUE if the callback ran successfully.
     */
    public function beforeDelete(Event $event, Entity $entity, array $options = []): bool
    {
        $options['events'] ??= true;
        $options['cascade'] ??= true;
        $options['purge'] ??= false;

        if ($options['purge']) {
            return true;
        }

        $event->preventDefault();
        $event->stopImmediatePropagation();

        if ($options['cascade']) {
            $relationships = $this->getDependentRelationships();

            foreach ($relationships as $relationship) {
                if (!$relationship->unlinkAll([$entity], $options)) {
                    return false;
                }
            }
        }

        $entity->set($this->config['field'], DateTime::now(), ['temporary' => true]);

        if (!$this->model->save($entity)) {
            return false;
        }

        if ($options['events']) {
            $event = $this->model->dispatchEvent('Orm.afterDelete', ['entity' => $entity, 'options' => $options]);

            if ($event->isDefaultPrevented()) {
                return (bool) $event->getResult();
            }
        }

        return true;
    }

    /**
     * Before find callback.
     *
     * @param Event $event The Event.
     * @param SelectQuery $query The query.
     * @param array $options The find options.
     * @return SelectQuery The query.
     */
    public function beforeFind(Event $event, SelectQuery $query, array $options = []): SelectQuery
    {
        $options['deleted'] ??= false;

        if ($options['deleted']) {
            return $query;
        }

        $query->where([
            $this->model->aliasField($this->config['field'], $query->getAlias()).' IS NULL',
        ]);

        return $query;
    }

    /**
     * Find only soft deleted records.
     *
     * @param array $options The find options.
     * @return SelectQuery The query.
     */
    public function findOnlyDeleted(array $options = []): SelectQuery
    {
        $options['deleted'] ??= true;

        $query = $this->model->find($options);

        $query->where([
            $this->model->aliasField($this->config['field']).' IS NOT NULL',
        ]);

        return $query;
    }

    /**
     * Find all records including soft deleted.
     *
     * @param array $options The find options.
     * @return SelectQuery The query.
     */
    public function findWithDeleted(array $options = []): SelectQuery
    {
        $options['deleted'] ??= true;

        return $this->model->find($options);
    }

    /**
     * Delete an Entity (permanently).
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for deleting.
     * @return bool TRUE if the purge was successful, otherwise FALSE.
     */
    public function purge(Entity $entity, array $options = []): bool
    {
        $options['purge'] ??= true;

        return $this->model->delete($entity, $options);
    }

    /**
     * Delete multiple entities (permanently).
     *
     * @param array|Traversable $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the purge was successful, otherwise FALSE.
     */
    public function purgeMany(array|Traversable $entities, array $options = []): bool
    {
        $options['purge'] ??= true;

        return $this->model->deleteMany($entities, $options);
    }

    /**
     * Restore an Entity.
     *
     * @param Entity $entity The Entity.
     * @param array $options The options for saving.
     * @return bool TRUE if the restore was successful, otherwise FALSE.
     */
    public function restore(Entity $entity, array $options = []): bool
    {
        return $this->restoreMany([$entity], $options);
    }

    /**
     * Restore entities.
     *
     * @param array|Traversable $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the restore was successful, otherwise FALSE.
     */
    public function restoreMany(array|Traversable $entities, array $options = []): bool
    {
        $options['dependents'] ??= true;

        $connection = $this->model->getConnection();

        $connection->begin();

        if ($options['dependents']) {
            $relationships = $this->getDependentRelationships();

            foreach ($relationships as $relationship) {
                $target = $relationship->getTarget();
                $children = $relationship->findRelated($entities, [
                    'conditions' => [
                        $target->aliasField($this->config['field']).' IS NOT NULL',
                    ],
                    'deleted' => true,
                ]);

                foreach ($children as $child) {
                    if (!$target->getBehavior('SoftDelete')->restore($child, $options)) {
                        $connection->rollback();

                        return false;
                    }
                }
            }
        }

        foreach ($entities as $entity) {
            $entity->set($this->config['field'], null, ['temporary' => true]);
        }

        if (!$this->model->saveMany($entities, $options)) {
            $connection->rollback();

            return false;
        }

        $connection->commit();

        return true;
    }

    /**
     * Get dependent relationships.
     *
     * @return array The dependent relationships.
     */
    protected function getDependentRelationships(): array
    {
        return array_filter(
            $this->model->getRelationships(),
            fn(Relationship $relationship): bool => ($relationship instanceof HasOne || $relationship instanceof HasMany) &&
                $relationship->isDependent() &&
                $relationship->getTarget()->hasBehavior('SoftDelete')
        );
    }
}
