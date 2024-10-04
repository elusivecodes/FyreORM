# FyreORM

**FyreORM** is a free, open-source database ORM for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Methods](#methods)
- [Models](#models)
    - [Schema](#schema)
    - [Entities](#entities)
    - [Query Methods](#query-methods)
    - [Relationship Methods](#relationship-methods)
    - [Behavior Methods](#behavior-methods)
    - [Validation](#validation)
    - [Callbacks](#callbacks)
- [Queries](#queries)
    - [Delete](#delete)
    - [Insert](#insert)
    - [Replace](#replace)
    - [Select](#select)
    - [Update](#update)
    - [Update Batch](#update-batch)
- [Results](#results)
- [Relationships](#relationships)
    - [Belongs To](#belongs-to)
    - [Has Many](#has-many)
    - [Has One](#has-one)
    - [Many To Many](#many-to-many)
- [Behavior Registry](#behavior-registry)
- [Behaviors](#behaviors)
    - [Timestamp](#timestamp)
- [Rules](#rules)



## Installation

**Using Composer**

```
composer require fyre/orm
```

In PHP:

```php
use Fyre\ORM\ModelRegistry;
```


## Methods

**Add Namespace**

Add a namespace for loading models.

- `$namespace` is a string representing the namespace.

```php
ModelRegistry::addNamespace($namespace);
```

**Clear**

Clear all namespaces and models.

```php
ModelRegistry::clear();
```

**Create Default Model**

Create a default [*Model*](#models).

```php
$model = ModelRegistry::createDefaultModel();
```

**Get Default Model Class**

Get the default model class name.

```php
$defaultModelClass = ModelRegistry::getDefaultModelClass();
```

**Get Namespaces**

Get the namespaces.

```php
$namespaces = ModelRegistry::getNamespaces();
```

**Has Namespace**

Check if a namespace exists.

- `$namespace` is a string representing the namespace.

```php
$hasNamespace = ModelRegistry::hasNamespace($namespace);
```

**Is Loaded**

Check if a model is loaded.

- `$alias` is a string representing the model alias.

```php
$isLoaded = ModelRegistry::isLoaded($alias);
```

**Load**

Load a [*Model*](#models).

- `$alias` is a string representing the model alias.

```php
$model = ModelRegistry::load($alias);
```

**Remove Namespace**

Remove a namespace.

- `$namespace` is a string representing the namespace.

```php
$removed = ModelRegistry::removeNamespace($namespace);
```

**Set Default Model Class**

Set the default model class name.

- `$defaultModelClass` is a string representing the default model class name.

```php
ModelRegistry::setDefaultModelClass($defaultModelClass);
```

**Unload**

Unload a model.

- `$alias` is a string representing the model alias.

```php
$unloaded = ModelRegistry::unload($alias);
```

**Use**

Load a shared [*Model*](#models) instance.

- `$alias` is a string representing the model alias.

```php
$model = ModelRegistry::use($alias);
```


## Models

Custom models can be created by extending the `\Fyre\ORM\Model` class, suffixing the class name with "*Model*".

To allow autoloading an instance of your model, add the the namespace to the *ModelRegistry*.

**Delete Query**

Create a new [*DeleteQuery*](#delete).

- `$options` is an array containing options for the query.
    - `alias` is a string representing the table alias, and will default to the model alias.

```php
$query = $model->deleteQuery($options);
```

**Get Connection**

Get the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

- `$type` is a string representing the connection type, and will default to `self::WRITE`.

```php
$connection = $model->getConnection($type);
```

Models use [*ConnectionManager*](https://github.com/elusivecodes/FyreDB) for database connections, and you can specify the connection to use by setting the `connectionKeys` property of your models, or using the `setConnection` method.

```php
protected array $connectionKeys = [
    self::WRITE => 'default',
    self::READ => 'read_replica'
];
```

If the `self::READ` key is omitted, it will fallback to the `self::WRITE` connection for database reads.

**Insert Query**

Create a new [*InsertQuery*](#insert).

```php
$query = $model->insertQuery();
```

**Replace Query**

Create a new [*ReplaceQuery*](#replace).

```php
$query = $model->replaceQuery();
```

**Select Query**

Create a new [*SelectQuery*](#select).

- `$options` is an array containing options for the query.
    - `alias` is a string representing the table alias, and will default to the model alias.
    - `connectionType` is a string representing the connection type, and will default to `self::READ`.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.

```php
$query = $model->selectQuery($options);
```

**Set Connection**

Set the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

- `$connection` is a [*Connection*](https://github.com/elusivecodes/FyreDB#connections).
- `$type` is a string representing the connection type, and will default to `self::WRITE`.

```php
$model->setConnection($connection, $type);
```

**Subquery**

Create a new subquery [*SelectQuery*](#select).

- `$options` is an array containing options for the query.
    - `alias` is a string representing the table alias, and will default to the model alias.
    - `connectionType` is a string representing the connection type, and will default to `self::READ`.

```php
$query = $model->subquery($options);
```

**Update Query**

Create a new [*UpdateQuery*](#update).

- `$options` is an array containing options for the query.
    - `alias` is a string representing the table alias, and will default to the model alias.

```php
$query = $model->updateQuery($options);
```

**Update Batch Query**

Create a new [*UpdateBatchQuery*](#update-batch).

- `$options` is an array containing options for the query.
    - `alias` is a string representing the table alias, and will default to the model alias.

```php
$query = $model->updateBatchQuery($options);
```


### Schema

**Alias Field**

Alias a field name.

- `$field` is a string representing the field name.
- `$alias` is a string representing the alias, and will default to the model alias.

```php
$aliasField = $model->aliasField($field, $alias);
```

**Get Alias**

Get the model alias.

```php
$alias = $model->getAlias();
```

By default, the alias will be the class name or the alias used when loading the model with *ModelRegistry*.

**Get Auto Increment Key**

Get the table auto increment column.

```php
$autoIncrementKey = $model->getAutoIncrementKey();
```

**Get Display Name**

Get the display name.

```php
$displayName = $model->getDisplayName();
```

By default, the display name will be the first column in the schema with the name of either *"name"*, *"title"* or *"label"*, or you can specify a column using the `displayName` property in your models.

```php
protected string $displayName = 'display_name';
```

**Get Primary Key**

Get the primary key(s).

```php
$primaryKeys = $model->getPrimaryKey();
```

**Get Schema**

Get the [*TableSchema*](https://github.com/elusivecodes/FyreSchema#table-schemas).

```php
$tableSchema = $model->getSchema();
```

**Get Table**

Get the table name.

```php
$table = $model->getTable();
```

By default, the table name will be the snake case form of the model alias, or you can specify a table name using the `table` property in your models.

```php
protected string $table = 'my_table';
```

**Set Alias**

Set the model alias.

- `$alias` is a string representing the model alias.

```php
$model->setAlias($alias);
```

**Set Display Name**

Set the display name.

- `$displayName` is a string representing the display name.

```php
$model->setDisplayName($displayName);
```

**Set Table**

Set the table name.

- `$table` is a string representing the table name.

```php
$model->setTable($table);
```


### Entities

**Get Entity Class**

Get the entity class.

```php
$entityClass = $model->getEntityClass();
```

Models will use the [*EntityLocator*](https://github.com/elusivecodes/FyreEntity#entity-locator) to find an entity using the model alias, or you can specify a class using the `entityClass` property in your models.

```php
protected string $entityClass = MyEntity::class;
```

**Load Into**

Load contained data into entity.

- `$entity` is an [*Entity*](https://github.com/elusivecodes/FyreEntity).
- `$contain` is an array containing the relationships to contain.

```php
$model->loadInto($entity, $contain);
```

**New Empty Entity**

Build a new empty [*Entity*](https://github.com/elusivecodes/FyreEntity).

```php
$entity = $model->newEmptyEntity();
```

**New Entity**

Build a new [*Entity*](https://github.com/elusivecodes/FyreEntity) using data.

- `$data` is an array containing the data.
- `$options` is an array containing entity options.
    - `associated` is an array containing the relationships to parse, and will default to *null*.
    - `parse` is a boolean indicating whether to parse user data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `validate` is a boolean indicating whether to validate user data, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity, and will default to *false*.
    - `new` is a boolean indicating whether to mark the entity as new, and will default to *null*.

```php
$entity = $model->newEntity($data, $options);
```

**New Entities**

Build multiple new entities using user data.

- `$data` is an array containing the data.
- `$options` is an array containing entity options.
    - `associated` is an array containing the relationships to parse, and will default to *null*.
    - `parse` is a boolean indicating whether to parse user data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `validate` is a boolean indicating whether to validate user data, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity, and will default to *false*.
    - `new` is a boolean indicating whether to mark the entity as new, and will default to *null*.

```php
$entities = $model->newEntities($data, $options);
```

**Patch Entity**

Update an Entity using user data.

- `$entity` is an [*Entity*](https://github.com/elusivecodes/FyreEntity).
- `$data` is an array containing the data.
- `$options` is an array containing entity options.
    - `associated` is an array containing the relationships to parse, and will default to *null*.
    - `parse` is a boolean indicating whether to parse user data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `validate` is a boolean indicating whether to validate user data, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity, and will default to *false*.
    - `new` is a boolean indicating whether to mark the entity as new, and will default to *null*.

```php
$model->patchEntity($entity, $data, $options);
```

**Patch Entities**

Update multiple entities using user data.

- `$entities` is an array containing the entities.
- `$data` is an array containing the data.
- `$options` is an array containing entity options.
    - `associated` is an array containing the relationships to parse, and will default to *null*.
    - `parse` is a boolean indicating whether to parse user data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `validate` is a boolean indicating whether to validate user data, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity, and will default to *false*.
    - `new` is a boolean indicating whether to mark the entity as new, and will default to *null*.

```php
$model->patchEntities($entities, $data, $options);
```


### Query Methods

**Delete**

Delete an [*Entity*](https://github.com/elusivecodes/FyreEntity).

- `$entity` is an [*Entity*](https://github.com/elusivecodes/FyreEntity).
- `$options` is an array containing delete options.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `cascade` is a boolean indicating whether to cascade deletes, and will default to *true*.

```php
$result = $model->delete($entity, $options);
```

**Delete All**

Delete all rows matching conditions.

- `$conditions` is an array or string representing the where conditions.

```php
$affectedRows = $model->deleteAll($conditions);
```

This method will not use callbacks or cascade to related data.

**Delete Many**

Delete multiple entities.

- `$entities` is an array containing the entities.
- `$options` is an array containing delete options.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `cascade` is a boolean indicating whether to cascade deletes, and will default to *true*.

```php
$result = $model->deleteMany($entities, $options);
```

**Exists**

Determine if matching rows exist.

- `$conditions` is an array or string representing the where conditions.

```php
$exists = $model->exists($conditions);
```

**Find**

Create a new [*SelectQuery*](#select).

- `$data` is an array containing the query data.
    - `connectionType` is a string representing the connection type, and will default to `self::READ`.
    - `fields` is an array or string representing the fields to select.
    - `contain` is a string or array containing the relationships to contain.
    - `join` is an array containing the tables to join.
    - `conditions` is an array or string representing the where conditions.
    - `orderBy` is an array or string representing the fields to order by.
    - `groupBy` is an array or string representing the fields to group by.
    - `having` is an array or string representing the having conditions.
    - `limit` is a number indicating the query limit.
    - `offset` is a number indicating the query offset.
    - `epilog` is a string representing the epilog for the query.
    - `autoFields` is a boolean indicating whether to enable auto fields.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.

```php
$query = $model->find($data);
```

**Get**

Retrieve a single entity.

- `$primaryValues` is a string, integer or array containing the primary key value(s).
- `$data` is an array containing the query data.
    - `connectionType` is a string representing the connection type, and will default to `self::READ`.
    - `fields` is an array or string representing the fields to select.
    - `contain` is a string or array containing the relationships to contain.
    - `join` is an array containing the tables to join.
    - `epilog` is a string representing the epilog for the query.
    - `autoFields` is a boolean indicating whether to enable auto fields.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.

```php
$entity = $model->get($primaryValues, $data);
```

**Save**

Save an [*Entity*](https://github.com/elusivecodes/FyreEntity).

- `$entity` is an [*Entity*](https://github.com/elusivecodes/FyreEntity).
- `$options` is an array containing save options.
    - `checkExists` is a boolean indicating whether to check if new entities exist, and will default to *true*.
    - `checkRules` is a boolean indicating whether to validate the [*RuleSet*](#rules), and will default to *true*.
    - `saveRelated` is a boolean indicating whether to save related data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity after saving, and will default to *true*.

```php
$result = $model->save($entity, $options);
```

**Save Many**

Save multiple entities.

- `$entities` is an array containing the entities.
- `$options` is an array containing save options.
    - `checkExists` is a boolean indicating whether to check if new entities exist, and will default to *true*.
    - `checkRules` is a boolean indicating whether to validate the [*RuleSet*](#rules), and will default to *true*.
    - `saveRelated` is a boolean indicating whether to save related data, and will default to *true*.
    - `events` is a boolean indicating whether to trigger model/behavior events, and will default to *true*.
    - `clean` is a boolean indicating whether to clean the entity after saving, and will default to *true*.

```php
$result = $model->saveMany($entities, $options);
```

**Update All**

Update all rows matching conditions.

- `$data` is an array of values to update.
- `$conditions` is an array or string representing the where conditions.

```php
$affectedRows = $model->updateAll($data, $conditions);
```

This method will not use callbacks.


### Relationship Methods

**Add Relationship**

Add a [*Relationship*](#relationships).

- `$relationship` is a [*Relationship*](#relationships).

```php
$model->addRelationship($relationship);
```

**Get Relationship**

Get a [*Relationship*](#relationships).

- `$name` is a string representing the relationship name.

```php
$relationship = $model->getRelationship($name);
```

**Get Relationships**

Get all relationships.

```php
$relationships = $model->getRelationships();
```

**Has Relationship**

Determine if a [*Relationship*](#relationships) exists.

- `$name` is a string representing the relationship name.

```php
$hasRelationship = $model->hasRelationship($name);
```

**Remove Relationship**

Remove an existing [*Relationship*](#relationships).

- `$name` is a string representing the relationship name.

```php
$model->removeRelationship($name);
```


### Behavior Methods

**Add Behavior**

Add a [*Behavior*](#behaviors) to the *Model*.

- `$name` is a string representing the behavior name.
- `$options` is an array containing behavior options.

```php
$model->addBehavior($name, $options);
```

**Get Behavior**

Get a loaded [*Behavior*](#behaviors).

- `$name` is a string representing the behavior name.

```php
$behavior = $model->getBehavior($name);
```

**Has Behavior**

Determine if the *Model* has a [*Behavior*](#behaviors).

- `$name` is a string representing the behavior name.

```php
$hasBehavior = $model->hasBehavior($name);
```

**Remove Behavior**

Remove a [*Behavior*](#behaviors) from the *Model*.

- `$name` is a string representing the behavior name.

```php
$model->removeBehavior($name);
```


### Validation

**Get Rules**

Get the model [*RuleSet*](#rules).

```php
$rules = $model->getRules();
```

You can build custom rules by specifying a `buildRules` [callback](#callbacks) in your models.

**Get Validator**

Get the model [*Validator*](https://github.com/elusivecodes/FyreValidation).

```php
$validator = $model->getValidator();
```

You can build a custom validator by specifying a `buildValidation` [callback](#callbacks) in your models.

**Set Rules**

Set the model [*RuleSet*](#rules).

- `$rules` is a [*RuleSet*](#rules).

```php
$model->setRules($rules);
```

**Set Validator**

Set the model [*Validator*](https://github.com/elusivecodes/FyreValidation).

- `$validator` is a [*Validator*](https://github.com/elusivecodes/FyreValidation).

```php
$model->setValidator($validator);
```


### Callbacks

Callbacks can be defined in your models, allowing custom code to run or revert changes at various points during model operations.

**After Delete**

Execute a callback after entities are deleted.

```php
use Fyre\Entity\Entity;

public function afterDelete(Entity $entity) {}
```

If this method returns *false* the delete will not be performed and the transaction will be rolled back.

**After Delete Commit**

Execute a callback after entities are deleted and transaction is committed.

```php
use Fyre\Entity\Entity;

public function afterDeleteCommit(Entity $entity) {}
```

**After Find**

Execute a callback after performing a find query.

```php
use Fyre\ORM\Result;

public function afterFind(Result $result): Result {}
```

**After Rules**

Execute a callback after model rules are processed.

```php
use Fyre\Entity\Entity;

public function afterRules(Entity $entity) {}
```

If this method returns *false* the save will not be performed.

**After Parse**

Execute a callback after parsing user data into an entity.

```php
use Fyre\Entity\Entity;

public function afterParse(Entity $entity) {}
```

**After Save**

Execute a callback after entities are saved to the database.

```php
use Fyre\Entity\Entity;

public function afterSave(Entity $entity) {}
```

If this method returns *false* the save will not be performed and the transaction will be rolled back.

**After Save Commit**

Execute a callback after entities are saved to the database and transaction is committed.

```php
use Fyre\Entity\Entity;

public function afterSaveCommit(Entity $entity) {}
```

**Before Delete**

Execute a callback before entities are deleted.

```php
use Fyre\Entity\Entity;

public function beforeDelete(Entity $entity) {}
```

If this method returns *false* the delete will not be performed.

**Before Find**

Execute a callback before performing a find query.

```php
use Fyre\ORM\Query;

public function beforeFind(Query $query): Query {}
```

**Before Parse**

Execute a callback before parsing user data into an entity.

```php
use ArrayObject;

public function beforeParse(ArrayObject $data) {}
```

**Before Rules**

Before rules callback.

```php
use Fyre\Entity\Entity;

public function beforeRules(Entity $entity) {}
```

If this method returns *false* the save will not be performed.

**Before Save**

Execute a callback before entities are saved to the database.

```php
use Fyre\Entity\Entity;

public function beforeSave(Entity $entity) {}
```

If this method returns *false* the save will not be performed and the transaction will be rolled back.

**Build Rules**

```php
use Fyre\ORM\RuleSet;

public function buildRules(RuleSet $rules): RuleSet
{
    return $rules;
}
```

**Build Validation**

```php
use Fyre\Validation\Validator;

public function buildValidation(Validator $validator): Validator
{
    return $validator;
}
```


## Queries

**Get Model**

Get the [*Model*](#models).

```php
$model = $query->getModel();
```

### Delete

The `\Fyre\ORM\Queries\DeleteQuery` class extends the [*DeleteQuery*](https://github.com/elusivecodes/FyreDB#delete) class. The table and alias will be automatically set from the *Model*.

```php
$model->deleteQuery()
    ->where($conditions)
    ->execute();
```

### Insert

The `\Fyre\ORM\Queries\InsertQuery` class extends the [*InsertQuery*](https://github.com/elusivecodes/FyreDB#insert) class. The table will be automatically set from the *Model*.

```php
$model->insertQuery()
    ->values($values)
    ->execute();
```

### Replace

The `\Fyre\ORM\Queries\ReplaceQuery` class extends the [*ReplaceQuery*](https://github.com/elusivecodes/FyreDB#replace) class. The table will be automatically set from the *Model*.

```php
$model->replaceQuery()
    ->values($values)
    ->execute();
```

### Select

The `\Fyre\ORM\Queries\SelectQuery` class extends the [*SelectQuery*](https://github.com/elusivecodes/FyreDB#select) class, while providing several additional methods and wrappers for relationship and entity mapping. The table and alias will be automatically set from the *Model*, and field names will be automatically aliased.

```php
$model->selectQuery()
    ->select($fields)
    ->where($conditions)
    ->execute();
```

**All**

Get the [*Result*](#results).

```php
$results = $query->all();
```

**Clear Result**

Clear the buffered result.

```php
$query->clearResult();
```

**Contain**

Set the contain relationships.

- `$contain` is a string or array containing the relationships to contain.
- `$overwrite` is a boolean indicating whether to overwrite existing contains, and will default to *false*.

```php
$query->contain($contain, $overwrite);
```

**Count**

Get the result count.

```php
$count = $query->count();
```

**Disable Auto Fields**

Disable auto fields.

```php
$query->disableAutoFields();
```

**Disable Buffering**

Disable result buffering.

```php
$query->disableBuffering();
```

**Enable Auto Fields**

Enable auto fields.

```php
$query->enableAutoFields();
```

**Enable Buffering**

Enable result buffering.

```php
$query->enableBuffering();
```

**First**

Get the first result.

```php
$entity = $query->first();
```

**Get Alias**

Get the alias.

```php
$alias = $query->getAlias();
```

**Get Connection Type**

Get the connection type.

```php
$connectionType = $query->getConnectionType();
```

**Get Contain**

Get the contain array.

```php
$contain = $query->getContain();
```

**Get Matching**

Get the matching array.

```php
$matching = $query->getMatching();
```

**Get Result**

Get the [*Result*](#results).

```php
$result = $query->getResult();
```

**Inner Join With**

INNER JOIN a relationship table.

- `$contain` is a string representing the relationships to contain.
- `$conditions` is an array containing additional join conditions.

```php
$query->innerJoinWith($contain, $conditions);
```

**Left Join With**

LEFT JOIN a relationship table.

- `$contain` is a string representing the relationships to contain.
- `$conditions` is an array containing additional join conditions.

```php
$query->leftJoinWith($contain, $conditions);
```

**Matching**

INNER JOIN a relationship table and load matching data.

- `$contain` is a string representing the relationships to contain.
- `$conditions` is an array containing additional join conditions.

```php
$query->matching($contain, $conditions);
```

The matching data will be accessible via the `_matchingData` property.

**Not Matching**

LEFT JOIN a relationship table and exclude matching rows.

- `$contain` is a string representing the relationships to contain.
- `$conditions` is an array containing additional join conditions.

```php
$query->notMatching($contain, $conditions);
```
**To Array**

Get the results as an array.

```php
$array = $query->toArray();
```

### Update

The `\Fyre\ORM\Queries\UpdateQuery` class extends the [*UpdateQuery*](https://github.com/elusivecodes/FyreDB#update) class. The table will be automatically set from the *Model*.

```php
$model->updateQuery()
    ->set($values)
    ->where($conditions)
    ->execute();
```

**Get Alias**

Get the alias.

```php
$alias = $query->getAlias();
```

### Update Batch

The `\Fyre\ORM\Queries\UpdateBatchQuery` class extends the [*UpdateBatchQuery*](https://github.com/elusivecodes/FyreDB#update-batch) class. The table and alias will be automatically set from the *Model*, and field names will be automatically aliased.

```php
$model->updateBatchQuery()
    ->set($values, $keys)
    ->execute();
```

**Get Alias**

Get the alias.

```php
$alias = $query->getAlias();
```


## Results

The `\Fyre\ORM\Result` class wraps the [*ResultSet*](https://github.com/elusivecodes/FyreDB#results) class, and acts as a proxy for the [*Collection*](https://github.com/elusivecodes/FyreCollection) class, providing additional handling for entity mapping and eager loading contained results.


## Relationships

### Belongs To

- `$name` is a string representing the relationship name.
- `$data` is an array containing relationship data.
    - `className` is a string representing the target alias, and will default to the relationship name.
    - `propertyName` is a string representing the entity property name, and will default to the snake case form of the singular relationship name.
    - `foreignKey` is a string representing the foreign key column in the current table, and will default to the snake case singular name of the target alias (suffixed with *"_id"*).
    - `bindingKey` is a string representing the matching column in the target table, and will default to the primary key.
    - `strategy` is a string representing the select strategy, and will default to "*join*".
    - `conditions` is an array containing additional conditions.

```php
$model->belongsTo($name, $data);
```

### Has Many

- `$name` is a string representing the relationship name.
- `$data` is an array containing relationship data.
    - `className` is a string representing the target alias, and will default to the relationship name.
    - `propertyName` is a string representing the entity property name, and will default to the snake case form of the relationship name.
    - `foreignKey` is a string representing the foreign key column in the target table, and will default to the snake case singular name of the current alias (suffixed with *"_id"*).
    - `bindingKey` is a string representing the matching column in the current table, and will default to the primary key.
    - `strategy` is a string representing the select strategy, and will default to "*select*".
    - `conditions` is an array containing additional conditions.
    - `dependent` is a boolean indicating whether to recursively delete related data, and will default to *false*.

```php
$model->hasMany($name, $data);
```

### Has One

- `$name` is a string representing the relationship name.
- `$data` is an array containing relationship data.
    - `className` is a string representing the target alias, and will default to the relationship name.
    - `propertyName` is a string representing the entity property name, and will default to the snake case form of the singular relationship name.
    - `foreignKey` is a string representing the foreign key column in the target table, and will default to the snake case singular name of the current alias (suffixed with *"_id"*).
    - `bindingKey` is a string representing the matching column in the current table, and will default to the primary key.
    - `strategy` is a string representing the select strategy, and will default to "*join*".
    - `conditions` is an array containing additional conditions.
    - `dependent` is a boolean indicating whether to recursively delete related data, and will default to *false*.

```php
$model->hasOne($name, $data);
```

### Many To Many

- `$name` is a string representing the relationship name.
- `$data` is an array containing relationship data.
    - `className` is a string representing the target alias, and will default to the relationship name.
    - `through` is a string representing the join alias, and will default to the concatenated form of the current alias and relationship name (sorted alphabetically).
    - `propertyName` is a string representing the entity property name, and will default to the snake case form of the relationship name.
    - `foreignKey` is a string representing the foreign key column in the join table, and will default to the snake case singular name of the current alias (suffixed with *"_id"*).
    - `foreignKey` is a string representing the target foreign key column in the join table, and will default to the snake case singular name of the relationship name (suffixed with *"_id"*).
    - `bindingKey` is a string representing the matching column in the current table, and will default to the primary key.
    - `strategy` is a string representing the select strategy, and will default to "*select*".
    - `conditions` is an array containing additional conditions.

```php
$model->manyToMany($name, $data);
```

When loading results, the join table data will be accessible via the `_joinData` property.


## Behavior Registry

```php
use Fyre\ORM\BehaviorRegistry;
```

**Add Namespace**

Add a namespace for automatically loading behaviors.

- `$namespace` is a string representing the namespace.

```php
BehaviorRegistry::addNamespace($namespace);
```

**Clear**

Clear all namespaces and behaviors.

```php
BehaviorRegistry::clear();
```

**Find**

Find a behavior class.

- `$name` is a string representing the behavior name.

```php
$className = BehaviorRegistry::find($name);
```

**Get Namespaces**

Get the namespaces.

```php
$namespaces = BehaviorRegistry::getNamespaces();
```

**Has Namespace**

Check if a namespace exists.

- `$namespace` is a string representing the namespace.

```php
$hasNamespace = BehaviorRegistry::hasNamespace($namespace);
```

**Load**

Load a behavior.

- `$name` is a string representing the behavior name.
- `$model` is a *Model*.
- `$options` is an array containing the behavior options, and will default to *[]*.

```php
$behavior = BehaviorRegistry::load($name, $model, $options);
```

**Remove Namespace**

Remove a namespace.

- `$namespace` is a string representing the namespace.

```php
$removed = BehaviorRegistry::removeNamespace($namespace);
```


## Behaviors

Behaviors must be attached to a [*Model*](#models) using the `addBehavior` method. Loaded behaviors can be accessed inside a [*Model*](#models) using the class name as a property of `$this`.

```php
$this->addBehavior('MyBehavior');

$behavior = $this->MyBehavior;
```

Custom behaviors can be created by extending `\Fyre\ORM\Behavior`, suffixing the class name with "*Behavior*", and ensuring the `__construct` method accepts [*Model*](#models) as the argument (and optionally an `$options` array as the second parameter).

Behaviors can also include [callbacks](#callbacks) that will be executed during model operations.

**Get Config**

Get the configuration options.

```php
$config = $behavior->getConfig();
```

**Get Model**

Get the [*Model*](#models).

```php
$model = $behavior->getModel();
```

### Timestamp

The timestamp behavior provided a simple way to automatically update created/modified timestamps when saving data via models.

- `$options` is an array containing behavior options.
    - `createdField` is a string representing the created field name, and will default to "*created*".
    - `modifiedField` is a string representing the modified field name, and will default to "*modified*".

```php
$model->addBehavior('Timestamp', $options);
```


## Rules

**Add**

Add a rule.

- `$rule` is a *Closure*, that accepts an [*Entity*](https://github.com/elusivecodes/FyreEntity) as the first argument, and should return *false* if the validation failed.

```php
$rules->add($rule);
```

**Exists In**

Create an "exists in" rule.

- `$fields` is an array containing the fields.
- `$name` is the name of the relationship.
- `$options` is an array containing the rule options.
    - `targetFields` is an array containing fields to match in the target table, and will default to the primary key(s).
    - `callback` is a *Closure*, that accepts the [*SelectQuery*](#select) as an argument.
    - `allowNullableNulls` is a boolean indicating whether to allow nullable nulls, and will default to *false*.
    - `message` is a string representing the error message, and will default to `Lang::get('RuleSet.existsIn')`.

```php
$rules->existsIn($fields, $name, $options);
```

**Is Clean**

Create an "is clean" rule.

- `$options` is an array containing the rule options.
- `$fields` is an array containing the fields.
    - `message` is a string representing the error message, and will default to `Lang::get('RuleSet.isClean')`.

```php
$rules->isClean($fields, $options);
```

**Is Unique**

Create an "is unique" rule.

- `$fields` is an array containing the fields.
- `$options` is an array containing the rule options.
    - `callback` is a *Closure*, that accepts the [*SelectQuery*](#select) as an argument.
    - `allowMultipleNulls` is a boolean indicating whether to allow multiple nulls, and will default to *false*.
    - `message` is a string representing the error message, and will default to `Lang::get('RuleSet.isUnique')`.

```php
$rules->isUnique($fields, $options);
```

**Validate**

Validate an [*Entity*](https://github.com/elusivecodes/FyreEntity).

- `$entity` is an [*Entity*](https://github.com/elusivecodes/FyreEntity).

```php
$rules->validate($entity);
```