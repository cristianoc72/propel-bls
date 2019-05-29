<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\lang\Text;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\DescriptionPart;
use Propel\Generator\Model\Parts\ColumnsPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Model\Parts\PlatformAccessorPart;
use Propel\Generator\Model\Parts\SchemaNamePart;
use Propel\Generator\Model\Parts\ScopePart;
use Propel\Generator\Model\Parts\SqlPart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Runtime\Exception\RuntimeException;
use Propel\Common\Collection\Set;

/**
 * Data about a table used in an application.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Table
{
    use SuperordinatePart, PlatformAccessorPart, ScopePart, BehaviorPart, NamespacePart, SchemaNamePart, SqlPart,
        GeneratorPart, VendorPart, DatabasePart, DescriptionPart, ColumnsPart;

    //
    // Model properties
    // ------------------------------------------------------------
    /** @var Text */
    private $tableName;

    /** @var string */
    private $alias;

    /** @var Column */
    private $inheritanceColumn;

    //
    // Collections to other models
    // ------------------------------------------------------------

    /** @var Set */
    private $foreignKeys;

    /** @var Set */
    private $referrers;

    /** @var Set */
    private $foreignTableNames;

    /** @var Set */
    private $indices;

    /** @var Set */
    private $unices;

    //
    // Database related options/properties
    // ------------------------------------------------------------

    /** @var bool */
    private $allowPkInsert;

    /** @var bool */
    private $containsForeignPK = false;

    /** @var bool */
    private $needsTransactionInPostgres;

    /** @var bool */
    private $forReferenceOnly;

    /** @var bool */
    private $reloadOnInsert;

    /** @var bool */
    private $reloadOnUpdate;

    //
    // Generator options
    // ------------------------------------------------------------

    /** @var bool */
    private $readOnly;

    /** @var bool */
    private $isAbstract;

    /** @var bool */
    private $skipSql;

    /**
     * @TODO maybe move this to database related options/props section ;)
     *
     * @var bool
     */
    private $isCrossRef;

    /**
     * Constructs a table object with a name
     *
     * @param string $name table name
     */
    public function __construct(string $name = null)
    {
        if ($name) {
            $this->setName($name);
        }

        // init
        $this->tableName = new Text('');
        $this->foreignKeys = new Set([], ForeignKey::class);
        $this->foreignTableNames = new Set();
        $this->indices = new Set([], Index::class);
        $this->referrers = new Set([], ForeignKey::class);
        $this->unices = new Set([], Unique::class);
        $this->initColumns();
        $this->initBehaviors();
        $this->initSql();
        $this->initVendor();

        // default values
        $this->allowPkInsert = false;
        $this->isAbstract = false;
        $this->isCrossRef = false;
        $this->readOnly = false;
        $this->reloadOnInsert = false;
        $this->reloadOnUpdate = false;
        $this->skipSql = false;
        $this->forReferenceOnly = false;
    }

    public function __clone()
    {
        $this->columns = clone $this->columns;
        $this->behaviors = clone $this->behaviors;
        $this->idMethodParameters = clone $this->idMethodParameters;
        $this->vendor = clone $this->vendor;
        //Circular reference. Which strategy? Leave the reference to the original database
        //or set it to null?
        //$this->database = clone $this->database;
        $this->foreignKeys = clone $this->foreignKeys;
        $this->referrers = clone $this->referrers;
        $this->foreignTableNames = clone $this->foreignTableNames;
        $this->indices = clone $this->indices;
        $this->unices = clone $this->unices;
        foreach ($this->columns as $column) {
            if ($column->isInheritance()) {
                $this->inheritanceColumn = $column;
                break;
            }
        }
    }

    /**
     * @inheritdoc
     * @return Database
     */
    protected function getSuperordinate(): ?Database
    {
        return $this->database;
    }

    /**
     * Useful method for ColumnsPart
     *
     * @return Table
     */
    public function getTable(): Table
    {
        return $this;
    }

    //
    // Model properties
    // ------------------------------------------------------------

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = new Text($tableName);
    }

    /**
     * Returns the blank table name.
     *
     * @return Text
     */
    public function getTableName(): Text
    {
        if ($this->tableName->isEmpty()) {
            $this->tableName = $this->name->toSnakeCase();
        }
        $tableName = $this->tableName->isEmpty() ? $this->name->toSnakeCase() : $this->tableName;

        return $tableName;
    }

    /**
     * The table name with database scope.
     *
     * @return string
     */
    public function getScopedTableName(): string
    {
        if ($scope = $this->getScope()) {
            return $this->getTableName()->prepend($scope)->toString();
        }

        return $this->getTableName()->toString();
    }

    /**
     * Returns the scoped table name with possible schema.
     *
     * @return string
     */
    public function getFullTableName(): string
    {
        $fqTableName = $this->getScopedTableName();

        if (null !== $schemaName = $this->getSchemaName()) {
            $fqTableName = "$schemaName{$this->getPlatform()->getSchemaDelimiter()}$fqTableName";
        }

        return $fqTableName;
    }

    //
    // References to other models
    // ------------------------------------------------------------

    /**
     * Set the database that contains this table.
     *
     * @param Database $database
     * @return $this
     */
    public function setDatabase(Database $database): Table
    {
        if ($this->database !== null && $this->database !== $database) {
            $this->database->removeTable($this);
        }
        $this->database = $database;
        $this->database->addTable($this);

        return $this;
    }

    /**
     * Returns the Database platform.
     *
     * @return PlatformInterface
     */
    public function getPlatform(): ?PlatformInterface
    {
        return $this->database ? $this->database->getPlatform() : null;
    }

    /**
     * Returns the column that subclasses the class representing this
     * table can be produced from.
     *
     * @return null|Column
     */
    public function getChildrenColumn(): ?Column
    {
        return $this->inheritanceColumn;
    }

    /**
     * Returns the subclasses that can be created from this table.
     *
     * @return string[] Array of subclasses  names
     */
    public function getChildrenNames(): array
    {
        if (null === $this->inheritanceColumn || !$this->inheritanceColumn->isEnumeratedClasses()) {
            return [];
        }

        $names = [];
        foreach ($this->inheritanceColumn->getChildren() as $child) {
            $names[] = get_class($child);
        }

        return $names;
    }



    //
    // Collections to other models
    // ------------------------------------------------------------


    // behaviors
    // -----------------------------------------

    /**
     * @TODO can it be externalized?
     *
     * Executes behavior table modifiers.
     * This is only for testing purposes. Model\Database calls already `modifyTable` on each behavior.
     */
    public function applyBehaviors(): void
    {
        foreach ($this->behaviors as $behavior) {
            if (!$behavior->isTableModified()) {
                $behavior->getTableModifier()->modifyTable();
                $behavior->setTableModified(true);
            }
        }
    }

    protected function registerBehavior(Behavior $behavior): void
    {
        $behavior->setTable($this);
    }

    protected function unregisterBehavior(Behavior $behavior): void
    {
        $behavior->setTable(null);
    }


    // columns
    // -----------------------------------------

    /**
     * Adds a new column to the table.
     *
     * @param Column $column
     *
     * @throws EngineException
     * @return $this
     */
    public function addColumn(Column $column): Table
    {
        //The column must be unique
        if (null !== $this->getColumnByName($column->getName()->toString())) {
            throw new EngineException(sprintf('Column "%s" declared twice in table "%s"', $column->getName(), $this->getName()));
        }

        $column->setTable($this);
        $this->columns->add($column);

        $column->setPosition($this->columns->size());

        if ($column->requiresTransactionInPostgres()) {
            $this->needsTransactionInPostgres = true;
        }

        if ($column->isInheritance()) {
            $this->inheritanceColumn = $column;
        }

        return $this;
    }

    /**
     * @TODO check consistency with naming size/num/count methods
     *
     * Returns the number of columns in this table.
     *
     * @return int
     */
    public function getNumColumns(): int
    {
        return $this->columns->size();
    }

    /**
     * @TODO check consistency with naming size/num/count methods
     *
     * Returns the number of lazy loaded columns in this table.
     *
     * @return int
     */
    public function getNumLazyLoadColumns(): int
    {
        $count = 0;
        foreach ($this->columns as $col) {
            if ($col->isLazyLoad()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns whether or not one of the columns is of type ENUM.
     *
     * @return bool
     */
    public function hasEnumColumns(): bool
    {
        foreach ($this->columns as $col) {
            if ($col->isEnumType()) {
                return true;
            }
        }

        return false;
    }

//    Never used: remove?
//
//    private function getColumnPosition(Column $column): int
//    {
//        return $this->columns->indexOf($column);
//    }


    // foreignKeys
    // -----------------------------------------

    /**
     * Adds a new foreignKey to this table.
     *
     * @param ForeignKey $foreignKey The foreignKey
     *
     * @return $this
     */
    public function addForeignKey(ForeignKey $foreignKey): Table
    {
        $foreignKey->setTable($this);

        $this->foreignKeys->add($foreignKey);
        $this->foreignTableNames->add($foreignKey->getForeignTableName());

        return $this;
    }

    /**
     * Adds several foreign keys at once.
     *
     * @param ForeignKey[] $foreignKeys An array of ForeignKey objects
     */
    public function addForeignKeys(array $foreignKeys)
    {
        foreach ($foreignKeys as $foreignKey) {
            $this->addForeignKey($foreignKey);
        }
    }

    /**
     * Returns whether or not the table has foreign keys.
     *
     * @return bool
     */
    public function hasForeignKeys(): bool
    {
        return $this->foreignKeys->size() > 0;
    }

    /**
     * Returns whether the table has cross foreign keys or not.
     *
     * @return bool
     */
    public function hasCrossForeignKeys(): bool
    {
        return count($this->getCrossForeignKeys()) > 0;
    }

    /**
     * @param string $columnName
     *
     * @return ForeignKey
     */
    public function getForeignKey(string $columnName): ForeignKey
    {
        return $this->foreignKeys->find($columnName, function (ForeignKey $element, string $query): bool {
            return $element->getName() === $query;
        });
    }

    /**
     * Returns the list of all foreign keys.
     *
     * @return Set
     */
    public function getForeignKeys(): Set
    {
        return $this->foreignKeys;
    }

    /**
     * Returns all foreign keys from this table that reference the table passed
     * in argument.
     *
     * @param string $tableName
     *
     * @return Set
     */
    public function getForeignKeysReferencingTable(string $tableName): Set
    {
        return $this->foreignKeys->filter(function (ForeignKey $foreignKey) use ($tableName) {
            return $foreignKey->getForeignTableName() === $tableName;
        });
    }

    /**
     * Returns the foreign keys that include $column in it's list of local
     * columns.
     *
     * Eg. Foreign key (a, b, c) references tbl(x, y, z) will be returned of $column
     * is either a, b or c.
     *
     * @param string $columnName Name of the column
     *
     * @return Set
     */
    public function getColumnForeignKeys(string $columnName): Set
    {
        return $this->foreignKeys->filter(function (ForeignKey $foreignKey) use ($columnName) {
            return in_array($columnName, $foreignKey->getLocalColumns()->toArray());
        });
    }

    /**
     * Returns the list of cross foreignKeys.
     *
     * @return CrossForeignKey[]
     */
    public function getCrossForeignKeys()
    {
        $crossFks = [];
        foreach ($this->referrers as $refForeignKey) {
            if ($refForeignKey->getTable()->isCrossRef()) {
                $crossForeignKey = new CrossForeignKeys($refForeignKey, $this);
                /** @var ForeignKey $foreignKey */
                foreach ($refForeignKey->getOtherFks() as $foreignKey) {
                    if ($foreignKey->isAtLeastOneLocalPrimaryKeyIsRequired() &&
                        $crossForeignKey->isAtLeastOneLocalPrimaryKeyNotCovered($foreignKey)) {
                        $crossForeignKey->addForeignKey($foreignKey);
                    }
                }
                if ($crossForeignKey->hasForeignKeys()) {
                    $crossFks[] = $crossForeignKey;
                }
            }
        }

        return $crossFks;
    }

    /**
     * Returns the list of tables referenced by foreign keys in this table.
     *
     * @return Set
     */
    public function getForeignTableNames(): Set
    {
        return $this->foreignTableNames;
    }


    // referrer
    // -----------------------------------------

    /**
     * Adds the foreign key from another table that refers to this table.
     *
     * @param ForeignKey $foreignKey
     */
    public function addReferrer(ForeignKey $foreignKey): void
    {
        $this->referrers->add($foreignKey);
    }

    /**
     * Returns the list of references to this table.
     *
     * @return Set
     */
    public function getReferrers(): Set
    {
        return $this->referrers;
    }


    // indices
    // -----------------------------------------

    /**
     * Creates a new index.
     *
     * @param string $name The index name
     * @param array $columns The list of columns to index
     *
     * @return Index  $index   The created index
     */
    public function createIndex(string $name, array $columns): Index
    {
        $index = new Index($name);
        $index->addColumns($columns);

        $this->addIndex($index);

        return $index;
    }

    /**
     * Adds a new index to the indices list and set the
     * parent table of the column to the current table.
     *
     * @param  Index $index
     *
     * @throw  InvalidArgumentException If $index already exists or $index has no column
     */
    public function addIndex(Index $index): void
    {
        if ($this->hasIndex($index->getName()->toString())) {
            throw new \InvalidArgumentException(sprintf('Index "%s" already exist.', $index->getName()));
        }

        if ($index->getColumns()->size() === 0) {
            throw new \InvalidArgumentException(sprintf('Index "%s" has no columns.', $index->getName()));
        }

        $index->setTable($this);
        $this->indices->add($index);
    }

    /**
     * Checks if the table has a index by name.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function hasIndex(string $name): bool
    {
        return $this->indices->search($name, function(Index $element, string $query): bool {
            return $element->getName()->toString() === $query;
        });
    }

    /**
     * Checks if a index exists with the given $keys.
     *
     * @param array $keys
     * @return bool
     */
    public function isIndex(array $keys): bool
    {
        /** @var Index $index */
        foreach ($this->indices->toArray() as $index) {
            if (count($keys) === $index->getColumns()->size()) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$index->hasColumn($key instanceof Column ? $key->getName() : $key)) {
                        $allAvailable = false;
                        break;
                    }
                }
                if ($allAvailable) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the list of all indices of this table.
     *
     * @return Set
     */
    public function getIndices(): Set
    {
        return $this->indices;
    }

    /**
     * Removes an index off this table
     *
     * @param Index|string $index
     * @return $this
     */
    public function removeIndex($index): Table
    {
        if (is_string($index)) {
            $index = $this->indices->find($index, function (Index $index, string $query) {
                return $index->getName()->toString() === $query;
            });
        }

        if ($index instanceof Index && $index->getTable() === $this) {
            $index->setTable(null);
            $this->indices->remove($index);
        }

        return $this;
    }


    // unices
    // -----------------------------------------

    /**
     * Adds a new Unique index to the list of unique indices and set the
     * parent table of the column to the current table.
     *
     * @param Unique $unique
     */
    public function addUnique(Unique $unique): void
    {
        $unique->setTable($this);
        $unique->getName(); // we call this method so that the name is created now if it doesn't already exist.

        $this->unices->add($unique);
    }

    /**
     * Checks if $keys are a unique constraint in the table.
     * (through primaryKey, through a regular unices constraints or for single keys when it has isUnique=true)
     *
     * @param Column[]|string[] $keys
     * @return bool
     * @throws \InvalidArgumentException If a column is not associated to this table
     */
    public function isUnique(array $keys): bool
    {
        if (1 === count($keys)) {
            $column = $keys[0] instanceof Column ? $keys[0] : $this->getColumn($keys[0]);
            if ($column) {
                if ($column->isUnique()) {
                    return true;
                }

                if ($column->isPrimaryKey() && 1 === count($column->getTable()->getPrimaryKey())) {
                    return true;
                }
            }
        }

        // check if pk == $keys
        if (count($this->getPrimaryKey()) === count($keys)) {
            $allPk = true;
            $stringArray = is_string($keys[0]);
            foreach ($this->getPrimaryKey() as $pk) {
                if ($stringArray) {
                    if (!in_array($pk->getName(), $keys)) {
                        $allPk = false;
                        break;
                    }
                } else {
                    if (!in_array($pk, $keys)) {
                        $allPk = false;
                        break;
                    }
                }
            }

            if ($allPk) {
                return true;
            }
        }

        // check if there is a unique constrains that contains exactly the $keys
        /** @var Unique $unique */
        foreach ($this->unices->toArray() as $unique) {
            if (count($unique->getColumns()->toArray()) === count($keys)) {
                $allAvailable = true;
                foreach ($keys as $key) {
                    if (!$unique->hasColumn($key instanceof Column ? $key->getName() : $key)) {
                        $allAvailable = false;
                        break;
                    }
                }
                if ($allAvailable) {
                    return true;
                }
            } else {
                continue;
            }
        }

        return false;
    }

    /**
     * Returns the list of all unique indices of this table.
     *
     * @return Set
     */
    public function getUnices(): Set
    {
        return $this->unices;
    }

    /**
     * Removes an unique index off this table
     *
     * @param Unique|string $unique
     */
    public function removeUnique($unique): void
    {
        if (is_string($unique)) {
            $unique = $this->unices->find($unique, function (Unique $index, string $query) {
                return $index->getName()->toString() === $query;
            });
        }

        if ($unique instanceof Unique && $unique->getTable() === $this) {
            $unique->setTable(null);
            $this->unices->remove($unique);
        }
    }

    //
    // Database related options/properties
    // ------------------------------------------------------------

    /**
     * Returns a specified column by its php name.
     *
     * @param  string $phpName
     * @return Column
     */
    public function getColumnByPhpName(string $phpName): Column
    {
        return $this->columns->find($phpName, function(Column $element, string $query): bool {
            return $element->getPhpName()->toString() === $query;
        });
    }

    /**
     * Return true if the column requires a transaction in Postgres.
     *
     * @return bool
     */
    public function requiresTransactionInPostgres(): bool
    {
        return $this->needsTransactionInPostgres;
    }

    /**
     * @param bool $identifierQuoting
     */
    public function setIdentifierQuoting(bool $identifierQuoting): void
    {
        $this->identifierQuoting = $identifierQuoting;
    }

    /**
     * Checks if identifierQuoting is enabled. Looks up to its database->isIdentifierQuotingEnabled
     * if identifierQuoting is null hence undefined.
     *
     * Use getIdentifierQuoting() if you need the raw value.
     *
     * @return bool
     */
    public function isIdentifierQuotingEnabled(): bool
    {
        return (null !== $this->identifierQuoting || !$this->database)
            ? $this->identifierQuoting
            : $this->database->isIdentifierQuotingEnabled();
    }

    /**
     * Quotes a identifier depending on identifierQuotingEnabled.
     *
     * Needs a platform assigned to its database.
     *
     * @param string $text
     *
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if (!$this->getPlatform()) {
            throw new RuntimeException(
                'No platform specified. Can not quote without knowing which platform this table\'s database is using.'
               );
        }

        if ($this->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * @return bool|null
     */
    public function getIdentifierQuoting(): ?bool
    {
        return $this->identifierQuoting;
    }

    /**
     * Makes this database reload on insert statement.
     *
     * @param bool $flag True by default
     */
    public function setReloadOnInsert(bool $flag = true): void
    {
        $this->reloadOnInsert = $flag;
    }

    /**
     * Whether to force object to reload on INSERT.
     *
     * @return bool
     */
    public function isReloadOnInsert(): bool
    {
        return $this->reloadOnInsert;
    }

    /**
     * Makes this database reload on update statement.
     *
     * @param bool $flag True by default
     */
    public function setReloadOnUpdate(bool $flag = true): void
    {
        $this->reloadOnUpdate = $flag;
    }

    /**
     * Returns whether or not to force object to reload on UPDATE.
     *
     * @return bool
     */
    public function isReloadOnUpdate(): bool
    {
        return $this->reloadOnUpdate;
    }

    /**
     * Returns whether or not to determine if code/sql gets created for this table.
     * Table will be skipped, if set to true.
     *
     * @param bool $flag
     */
    public function setForReferenceOnly(bool $flag = true): void
    {
        $this->forReferenceOnly = $flag;
    }

    /**
     * Returns whether or not code and SQL must be created for this table.
     *
     * Table will be skipped, if return true.
     *
     * @return bool
     */
    public function isForReferenceOnly(): bool
    {
        return $this->forReferenceOnly;
    }

    /**
     * Returns whether we allow to insert primary keys on tables with
     * native id method.
     *
     * @return bool
     */
    public function isAllowPkInsert(): bool
    {
        return $this->allowPkInsert;
    }

    /**
     * Returns whether or not Propel has to skip DDL SQL generation for this
     * table (in the event it should not be created from scratch).
     *
     * @return bool
     */
    public function isSkipSql(): bool
    {
        return ($this->skipSql || $this->isAlias() || $this->isForReferenceOnly());
    }

    /**
     * Sets whether or not this table should have its SQL DDL code generated.
     *
     * @param bool $skip
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
    }


    // foreignKeys / key handling
    // -----------------------------------------

    /**
     * Returns the collection of Columns which make up the single primary
     * key for this table.
     *
     * @return Set
     */
    public function getPrimaryKey(): Set
    {
        return $this->columns->filter(function (Column $column) {
            return $column->isPrimaryKey();
        });
    }

    /**
     * Returns whether or not this table has a primary key.
     *
     * @return bool
     */
    public function hasPrimaryKey(): bool
    {
        return !$this->getPrimaryKey()->isEmpty();
    }

    /**
     * Returns whether or not this table has a composite primary key.
     *
     * @return bool
     */
    public function hasCompositePrimaryKey(): bool
    {
        return $this->getPrimaryKey()->size() > 1;
    }

    /**
     * Returns the first primary key column.
     *
     * Useful for tables with a PK using a single column.
     *
     * @return Column
     */
    public function getFirstPrimaryKeyColumn(): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->isPrimaryKey()) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Sets whether or not this table contains a foreign primary key.
     *
     * @param $containsForeignPK
     */
    public function setContainsForeignPK(bool $containsForeignPK): void
    {
        $this->containsForeignPK = $containsForeignPK;
    }

    /**
     * Returns whether or not this table contains a foreign primary key.
     *
     * @return bool
     */
    public function getContainsForeignPK(): bool
    {
        return $this->containsForeignPK;
    }

    /**
     * @todo never used: remove?
     *
     * Returns all required(notNull && no defaultValue) primary keys which are not in $primaryKeys.
     *
     * @param Column[] $primaryKeys
     * @return Column[]
     *//*
    public function getOtherRequiredPrimaryKeys(array $primaryKeys): array
    {
        $pks = [];
        foreach ($this->getPrimaryKey() as $primaryKey) {
            if ($primaryKey->isNotNull() && !$primaryKey->hasDefaultValue()
                && !in_array($primaryKey, $primaryKeys, true)) {
                $pks = $primaryKey;
            }
        }

        return $pks;
    }*/

    /**
     * Returns whether or not this table has any auto-increment primary keys.
     *
     * @return bool
     */
    public function hasAutoIncrementPrimaryKey(): bool
    {
        return null !== $this->getAutoIncrementPrimaryKey();
    }

    /**
     * @return string[]
     */
    public function getAutoIncrementColumnNames(): array
    {
        return $this->columns->findAll(function(Column $element): string {
            if ($element->isAutoIncrement()) {
                return $element->getName()->toString();
            }
        });
    }

    /**
     * Returns the auto incremented primary key.
     *
     * @return Column
     */
    public function getAutoIncrementPrimaryKey(): ?Column
    {
        if (Model::ID_METHOD_NONE !== $this->getIdMethod()) {
            return $this->getPrimaryKey()->find(function (Column $element): bool {
                return $element->isAutoIncrement();
            });
        }

        return null;
    }

    /**
     * Returns whether or not this table has at least one auto increment column.
     *
     * @return bool
     */
    public function hasAutoIncrement(): bool
    {
        return $this->columns->search(function(Column $element): bool {
            return $element->isAutoIncrement();
        });
    }


    //
    // Generator options
    // ------------------------------------------------------------

    /**
     * Returns whether or not this table is read-only. If yes, only only
     * accessors and relationship accessors and mutators will be generated.
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Makes this database in read-only mode.
     *
     * @param bool $flag True by default
     */
    public function setReadOnly(bool $flag = true): void
    {
        $this->readOnly = $flag;
    }


    /**
     * Returns whether or not a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a table called
     * "FOO", then the Foo business object class will be declared abstract. This
     * helps support class hierarchies
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * Sets whether or not a table is abstract, it marks the business object
     * class that is generated as being abstract. If you have a
     * table called "FOO", then the Foo business object class will be
     * declared abstract. This helps support class hierarchies
     *
     * @param bool $flag
     */
    public function setAbstract(bool $flag = true): void
    {
        $this->isAbstract = $flag;
    }

    /**
     * Sets a cross reference status for this foreign key.
     *
     * @param bool $flag
     */
    public function setCrossRef(bool $flag = true): void
    {
        $this->isCrossRef = $flag;
    }

    /**
     * Alias for Table::setCrossRef.
     *
     * @see Table::setCrossRef
     *
     * @param bool $flag
     * @deprecated use setCrossRef
     */
    public function setIsCrossRef(bool $flag = true): void
    {
        $this->setCrossRef($flag);
    }

    /**
     * Returns whether or not there is a cross reference status for this foreign
     * key.
     *
     * @return bool
     */
    public function isCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Alias for Table::getIsCrossRef.
     *
     * @see Table::isCrossRef
     *
     * @return bool
     * @deprecated use isCrossRef
     */
    public function getIsCrossRef(): bool
    {
        return $this->isCrossRef;
    }

    /**
     * Returns whether or not the table behaviors offer additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->hasAdditionalBuilders()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of additional builders provided by the table behaviors.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        $additionalBuilders = [];
        foreach ($this->behaviors as $behavior) {
            $additionalBuilders = array_merge($additionalBuilders, $behavior->getAdditionalBuilders());
        }

        return $additionalBuilders;
    }

    //
    // MISC
    // --------------

    /**
     * Returns the schema name from this table or from its database.
     *
     * @return string
     */
    public function guessSchemaName(): string
    {
        if (null === $this->schemaName) {
            return $this->database->getSchema()->getName()->toString();
        }

        return $this->schemaName;
    }

    /**
     * Returns whether or not this table is linked to a schema.
     *
     * @return bool
     */
    public function hasSchema(): bool
    {
        return $this->database
        && ($this->database->getSchema() ?: $this->database->getSchema())
        && ($platform = $this->getPlatform())
        && $platform->supportsSchemas();
    }

    /**
     * Returns the PHP name of an active record object this entry references.
     *
     * @return string
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Returns whether or not this table is specified in the schema or if there
     * is just a foreign key reference to it.
     *
     * @return bool
     */
    public function isAlias(): bool
    {
        return null !== $this->alias;
    }

    /**
     * Sets whether or not this table is specified in the schema or if there is
     * just a foreign key reference to it.
     *
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }
}
