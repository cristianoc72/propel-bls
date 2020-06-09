<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\ArrayList;
use phootwork\lang\Text;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\TablePart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;

/**
 * A class for information about table foreign keys.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Fedor <fedor.karpelevitch@home.com>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Ulf Hermann <ulfhermann@kulturserver.de>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class ForeignKey
{
    use NamePart, DatabasePart, TablePart, SuperordinatePart, VendorPart;

    private string $foreignTableName;

    /**
     * If foreignTableName is not given getForeignTable() uses this table directly.
     *
     * @var Table|null
     */
    private ?Table $foreignTable;
    private string $column;
    private string $refColumn;
    private string $refPhpName;
    private string $defaultJoin;
    private string $onUpdate = '';
    private string $onDelete = '';
    private ArrayList $localColumns;
    private ArrayList $foreignColumns;
    private bool $skipSql = false;
    private bool $skipCodeGeneration = false;
    private bool $autoNaming = false;
    private string $foreignSchema;

    /**
     * Constructs a new Relation object.
     *
     * @param string $name
     */
    public function __construct(?string $name = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        $this->onUpdate = Model::FK_NONE;
        $this->onDelete = Model::FK_NONE;
        $this->defaultJoin = 'INNER JOIN';
        $this->localColumns = new ArrayList();
        $this->foreignColumns = new ArrayList();
        $this->initVendor();
    }

    /**
     * @inheritdoc
     */
    public function getSuperordinate(): Table
    {
        return $this->getTable();
    }

    /**
     * @return string
     */
    public function getColumn(): ?string
    {
        $column = $this->column;

        if (!$column) {
            if ($this->hasName()) {
                $column = $this->name;
            }
        }

        return $column;
    }

    /**
     * @param string $column
     */
    public function setColumn(string $column): void
    {
        $this->column = $column;
    }

    /**
     * @return null|string
     */
    public function getRefColumn(): ?string
    {
        return $this->refColumn;
    }

    /**
     * @param string $refColumn
     */
    public function setRefColumn(string $refColumn): void
    {
        $this->refColumn = $refColumn;
    }

    /**
     * Returns the normalized input of onDelete and onUpdate behaviors.
     *
     * @param  string $behavior
     *
     * @return string
     */
    public function normalizeFKey(?string $behavior): string
    {
        if (null === $behavior) {
            return Model::FK_NONE;
        }

        $behavior = strtoupper($behavior);

        if ('NONE' === $behavior) {
            return Model::FK_NONE;
        }

        if ('SETNULL' === $behavior) {
            return Model::FK_SETNULL;
        }

        return $behavior;
    }

    /**
     * Returns whether or not the onUpdate behavior is set.
     *
     * @return boolean
     */
    public function hasOnUpdate(): bool
    {
        return Model::FK_NONE !== $this->onUpdate;
    }

    /**
     * Returns whether or not the onDelete behavior is set.
     *
     * @return boolean
     */
    public function hasOnDelete(): bool
    {
        return Model::FK_NONE !== $this->onDelete;
    }

    /**
     * @return boolean
     */
    public function isSkipCodeGeneration(): bool
    {
        return $this->skipCodeGeneration;
    }

    /**
     * @param boolean $skipCodeGeneration
     */
    public function setSkipCodeGeneration(bool $skipCodeGeneration): void
    {
        $this->skipCodeGeneration = $skipCodeGeneration;
    }

    /**
     * Returns true if $column is in our local columns list.
     *
     * @param  Column $column
     *
     * @return boolean
     */
    public function hasLocalColumn(Column $column): bool
    {
        if ($column = $this->getTable()->getColumn((string) $column->getName())) {
            return $this->localColumns->search($column->getName(), function ($element, $query) {
                return $element === $query;
            });
        }

        return false;
    }

    /**
     * Returns the onUpdate behavior.
     *
     * @return string
     */
    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    /**
     * Returns the onDelete behavior.
     *
     * @return string
     */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    /**
     * Sets the onDelete behavior.
     *
     * @param string $behavior
     */
    public function setOnDelete(string $behavior): void
    {
        $this->onDelete = $this->normalizeFKey($behavior);
    }

    /**
     * Sets the onUpdate behavior.
     *
     * @param string $behavior
     */
    public function setOnUpdate(string $behavior): void
    {
        $this->onUpdate = $this->normalizeFKey($behavior);
    }

    /**
     * Returns the foreign key name.
     *
     * @return Text
     */
    public function getName(): Text
    {
        $this->doNaming();

        return $this->name;
    }

    /**
     * @return bool
     */
    public function hasName(): bool
    {
        return !!$this->name && !$this->autoNaming;
    }

    /**
     * Sets the foreign key name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->autoNaming = !$name; //if no name we activate autoNaming
        $this->name = new Text($name);
    }

    protected function doNaming()
    {
        if (!$this->name || $this->autoNaming) {
            $newName = 'fk_';

            $hash = [];
            if ($this->getForeignTable()) {
                $hash[] = $this->getForeignTable()->getFullTableName();
            }
            $hash[] = implode(',', $this->localColumns->toArray());
            $hash[] = implode(',', $this->foreignColumns->toArray());

            $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);

            if ($this->getTable()) {
                $newName = $this->getTable()->getTableName() . '_' . $newName;
            }

            $this->name = new Text($newName);
            $this->autoNaming = true;
        }
    }

    /**
     * Returns the refPhpName for this foreign key (if any).
     *
     * @return string
     */
    public function getRefPhpName(): string
    {
        return $this->refPhpName;
    }

    /**
     * Sets a refPhpName to use for this foreign key.
     *
     * @param string $name
     */
    public function setRefPhpName(string $name): void
    {
        $this->refPhpName = $name;
    }

    /**
     * Returns the default join strategy for this foreign key (if any).
     *
     * @return string
     */
    public function getDefaultJoin(): string
    {
        return $this->defaultJoin;
    }

    /**
     * Sets the default join strategy for this foreign key (if any).
     *
     * @param string $join
     */
    public function setDefaultJoin(string $join): void
    {
        $this->defaultJoin = $join;
    }

    /**
     * Returns the foreign table name of the FK, aka 'target'.
     *
     * @return string
     */
    public function getForeignTableName(): ?string
    {
        if (null === $this->foreignTableName && null !== $this->foreignTable) {
            $this->foreignTableName = $this->foreignTable->getFullName()->toString();
        }

        return $this->foreignTableName;
    }

    /**
     * @param string $foreignTableName
     */
    public function setForeignTableName(string $foreignTableName): void
    {
        $this->foreignTableName = $foreignTableName;
    }

    /**
     * Returns the resolved foreign Table model object.
     *
     * @return Table|null
     */
    public function getForeignTable(): ?Table
    {
        if (null !== $this->foreignTable) {
            return $this->foreignTable;
        }

        if (($database = $this->getTable()->getDatabase()) && $this->getForeignTableName()) {
            return $database->getTableByName($this->getForeignTableName()) ??
                $database->getTableByFullName($this->getForeignTableName());
        }

        return null;
    }

    /**
     * @param null|Table $foreignTable
     */
    public function setForeignTable(Table $foreignTable): void
    {
        $this->foreignTable = $foreignTable;
    }

    /**
     * Returns the name of the table the foreign key is in.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->getTable()->getName()->toString();
    }

    /**
     * Returns the name of the schema the foreign key is in.
     *
     * @return string
     */
    public function getSchemaName(): string
    {
        return $this->getTable()->getSchemaName();
    }

    //@todo split into different typed methods?
    /**
     * Adds a new reference entry to the foreign key.
     *
     * @param mixed $ref1 A Column object or an associative array or a string
     * @param mixed $ref2 A Column object or a single string name
     */
    public function addReference($ref1, $ref2 = null): void
    {
        if (is_array($ref1)) {
            $this->localColumns->add($ref1['local'] ?? null);
            $this->foreignColumns->add($ref1['foreign'] ?? null);

            return;
        }

        if (is_string($ref1)) {
            $this->localColumns->add($ref1);
            $this->foreignColumns->add(is_string($ref2) ? $ref2 : null);

            return;
        }

        $local = null;
        $foreign = null;
        if ($ref1 instanceof Column) {
            $local = $ref1->getName();
        }

        if ($ref2 instanceof Column) {
            $foreign = $ref2->getName();
        }

        $this->localColumns->add($local);
        $this->foreignColumns->add($foreign);
    }

    /**
     * Clears the references of this foreign key.
     *
     */
    public function clearReferences(): void
    {
        $this->localColumns->clear();
        $this->foreignColumns->clear();
    }

    /**
     * Returns an array of local column names.
     *
     * @return ArrayList
     */
    public function getLocalColumns(): ArrayList
    {
        return $this->localColumns;
    }

    /**
     * Returns an array of local column objects.
     *
     * @return Column[]
     */
    public function getLocalColumnObjects(): array
    {
        $columns = [];
        foreach ($this->getLocalColumns() as $columnName) {
            $column = $this->getTable()->getColumn($columnName);
            if (null === $column) {
                throw new BuildException(sprintf(
                    'Column `%s` in local reference of relation `%s` from `%s` to `%s` not found.',
                    $columnName,
                    $this->getName(),
                    $this->getTable()->getName(),
                    $this->getForeignTable()->getName()
                ));
            }
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns a local Column object identified by a position.
     *
     * @param  integer $index
     *
     * @return Column
     */
    public function getLocalColumn(int $index = 0): Column
    {
        return $this->getTable()->getColumn($this->getLocalColumns()->get($index));
    }

    /**
     * Returns an array of local column to foreign column
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getLocalForeignMapping(): array
    {
        $h = [];
        for ($i = 0, $size = $this->localColumns->size(); $i < $size; $i++) {
            $h[$this->localColumns->get($i)] = $this->foreignColumns->get($i);
        }

        return $h;
    }

    /**
     * Returns an array of local column to foreign column
     * mapping for this foreign key.
     *
     * @return array
     */
    public function getForeignLocalMapping(): array
    {
        $h = [];
        for ($i = 0, $size = $this->localColumns->size(); $i < $size; $i++) {
            $h[$this->foreignColumns->get($i)] = $this->localColumns->get($i);
        }

        return $h;
    }

    /**
     * Returns an array of local and foreign column objects
     * mapped for this foreign key.
     *
     * @return Column[][]
     */
    public function getColumnObjectsMapping(): array
    {
        $mapping = [];
        $foreignColumns = $this->getForeignColumnObjects();
        for ($i = 0, $size = $this->localColumns->size(); $i < $size; $i++) {
            $mapping[] = [
                'local' => $this->getTable()->getColumn($this->localColumns->get($i)),
                'foreign' => $foreignColumns[$i],
            ];
        }

        return $mapping;
    }

    /**
     * Returns an array of local and foreign column objects
     * mapped for this foreign key.
     *
     * Easy to iterate using
     *
     * foreach ($relation->getColumnObjectsMapArray() as $map) {
     *      list($local, $foreign) = $map;
     * }
     *
     * @return Column[]
     */
    public function getColumnObjectsMapArray(): array
    {
        $mapping = [];
        $foreignColumns = $this->getForeignColumnObjects();
        for ($i = 0, $size = $this->localColumns->size(); $i < $size; $i++) {
            $mapping[] = [$this->getTable()->getColumn($this->localColumns->get($i)), $foreignColumns[$i]];
        }

        return $mapping;
    }

    /**
     * Returns the foreign column name mapped to a specified local column.
     *
     * @param  string $local
     *
     * @return string
     */
    public function getMappedForeignColumn(string $local): ?string
    {
        $m = $this->getLocalForeignMapping();

        return isset($m[$local]) ? $m[$local] : null;
    }

    /**
     * Returns the local column name mapped to a specified foreign column.
     *
     * @param  string $foreign
     *
     * @return string
     */
    public function getMappedLocalColumn(string $foreign): ?string
    {
        $mapping = $this->getForeignLocalMapping();

        return $mapping[$foreign] ?? null;
    }

    /**
     * Returns an array of foreign column names.
     *
     * @return ArrayList
     */
    public function getForeignColumns(): ArrayList
    {
        return $this->foreignColumns;
    }

    /**
     * Returns an array of foreign column objects.
     *
     * @return Column[]
     */
    public function getForeignColumnObjects(): array
    {
        $columns = [];
        $foreignTable = $this->getForeignTable();
        foreach ($this->foreignColumns as $columnName) {
            $column = null;
            if (false !== strpos($columnName, '.')) {
                [$relationName, $foreignColumnName] = explode('.', $columnName);
                $foreignRelation = $this->getForeignTable()->getForeignKey($relationName);
                if (!$foreignRelation) {
                    throw new BuildException(sprintf(
                        'Relation `%s` in Table %s (%s) in foreign reference of relation `%s` from `%s` to `%s` not found.',
                        $relationName,
                        $this->getForeignTable()->getName(),
                        $columnName,
                        $this->getName(),
                        $this->getTable()->getName(),
                        $this->getForeignTable()->getName()
                    ));
                }
            } else {
                $column = $foreignTable->getColumn($columnName);
            }

            if (null === $column) {
                throw new BuildException(sprintf(
                    'Column `%s` in foreign reference of relation `%s` from `%s` to `%s` not found.',
                    $columnName,
                    $this->getName(),
                    $this->getTable()->getName(),
                    $this->getForeignTable()->getName()
                ));
            }
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns a foreign column object.
     *
     * @param integer $index
     *
     * @return Column
     */
    public function getForeignColumn(int $index = 0): Column
    {
        return $this->getForeignTable()->getColumn($this->foreignColumns->get($index));
    }

    /**
     * Returns whether this foreign key uses only required local columns.
     *
     * @return boolean
     */
    public function isLocalColumnsRequired(): bool
    {
        foreach ($this->localColumns as $columnName) {
            if (!$this->getTable()->getColumn($columnName)->isNotNull()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether this foreign key uses at least one required local column.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalColumnRequired(): bool
    {
        foreach ($this->localColumns as $columnName) {
            if ($this->getTable()->getColumn($columnName)->isNotNull()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key uses at least one required(notNull && no defaultValue) local primary key.
     *
     * @return boolean
     */
    public function isAtLeastOneLocalPrimaryKeyIsRequired(): bool
    {
        foreach ($this->getLocalPrimaryKeys() as $pk) {
            if ($pk->isNotNull() && !$pk->hasDefaultValue()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether this foreign key is also the primary key of the foreign
     * table.
     *
     * @return boolean Returns true if all columns inside this foreign key are primary keys of the foreign table
     */
    public function isForeignPrimaryKey(): bool
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[] = $fPKCol->getName();
        }

        $foreignCols = [];
        foreach ($this->localColumns as $colName) {
            $foreignCols[] = $foreignTable->getColumn($lfmap[$colName])->getName();
        }

        return ((count($foreignPKCols) === count($foreignCols))
            && !array_diff($foreignPKCols, $foreignCols));
    }

    /**
     * Returns whether or not this foreign key relies on more than one
     * column binding.
     *
     * @return boolean
     */
    public function isComposite(): bool
    {
        return $this->localColumns->size() > 1;
    }

    /**
     * Returns whether or not this foreign key is also the primary key of
     * the local table.
     *
     * @return boolean True if all local columns are at the same time a primary key
     */
    public function isLocalPrimaryKey(): bool
    {
        $localPKCols = [];
        foreach ($this->getTable()->getPrimaryKey() as $lPKCol) {
            $localPKCols[] = $lPKCol->getName();
        }

        return count($localPKCols) === $this->localColumns->size() && !array_diff($localPKCols, $this->localColumns->toArray());
    }

    /**
     * Sets whether or not this foreign key should have its creation SQL
     * generated.
     *
     * @param boolean $skip
     */
    public function setSkipSql(bool $skip): void
    {
        $this->skipSql = $skip;
    }

    /**
     * Returns whether or not the SQL generation must be skipped for this
     * foreign key.
     *
     * @return boolean
     */
    public function isSkipSql(): bool
    {
        return $this->skipSql;
    }

    /**
     * Whether this foreign key is matched by an inverted foreign key (on foreign table).
     *
     * This is to prevent duplicate columns being generated for a 1:1 relationship that is represented
     * by foreign keys on both tables.  I don't know if that's good practice ... but hell, why not
     * support it.
     *
     * @return boolean
     * @link http://propel.phpdb.org/trac/ticket/549
     */
    public function isMatchedByInverseFK(): bool
    {
        return (Boolean)$this->getInverseFK();
    }

    public function getInverseFK(): ?ForeignKey
    {
        $foreignTable = $this->getForeignTable();
        $map = $this->getForeignLocalMapping();

        foreach ($foreignTable->getForeignKeys() as $refFK) {
            $fkMap = $refFK->getLocalForeignMapping();
            // compares keys and values, but doesn't care about order, included check to make sure it's the same table (fixes #679)
            if (($refFK->getTableName() === $this->getTableName()) && ($map === $fkMap)) {
                return $refFK;
            }
        }

        return null;
    }

    /**
     * Returns the list of other foreign keys starting on the same table.
     * Used in many-to-many relationships.
     *
     * @return ForeignKey[]
     */
    public function getOtherFks(): array
    {
        $fks = [];
        foreach ($this->getTable()->getForeignKeys() as $fk) {
            if ($fk !== $this) {
                $fks[] = $fk;
            }
        }

        return $fks;
    }

    /**
     * Whether at least one foreign column is also the primary key of the foreign table.
     *
     * @return boolean True if there is at least one column that is a primary key of the foreign table
     */
    public function isAtLeastOneForeignPrimaryKey(): bool
    {
        $cols = $this->getForeignPrimaryKeys();

        return 0 !== count($cols);
    }

    /**
     * Returns all foreign columns which are also a primary key of the foreign table.
     *
     * @return array Column[]
     */
    public function getForeignPrimaryKeys(): array
    {
        $lfmap = $this->getLocalForeignMapping();
        $foreignTable = $this->getForeignTable();

        $foreignPKCols = [];
        foreach ($foreignTable->getPrimaryKey() as $fPKCol) {
            $foreignPKCols[$fPKCol->getName()->toString()] = true;
        }

        $foreignCols = [];
        foreach ($this->getLocalColumn() as $colName) {
            if ($foreignPKCols[$lfmap[$colName]]) {
                $foreignCols[] = $foreignTable->getColumn($lfmap[$colName]);
            }
        }

        return $foreignCols;
    }

    /**
     * Returns all local columns which are also a primary key of the local table.
     *
     * @return Column[]
     */
    public function getLocalPrimaryKeys(): array
    {
        $cols = [];
        $localCols = $this->getLocalColumnObjects();

        foreach ($localCols as $localCol) {
            if ($localCol->isPrimaryKey()) {
                $cols[] = $localCol;
            }
        }

        return $cols;
    }

    /**
     * Whether at least one local column is also a primary key.
     *
     * @return boolean True if there is at least one column that is a primary key
     */
    public function isAtLeastOneLocalPrimaryKey(): bool
    {
        $cols = $this->getLocalPrimaryKeys();

        return 0 !== count($cols);
    }

    public function setForeignSchema(string $foreignSchema): void
    {
        $this->foreignSchema = $foreignSchema;
    }

    public function getForeignSchema(): string
    {
        return $this->foreignSchema;
    }
}
