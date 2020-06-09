<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\ArrayList;
use phootwork\collection\Map;
use phootwork\collection\Set;
use Propel\Generator\Model\Parts\BehaviorPart;
use Propel\Generator\Model\Parts\CopyPart;
use Propel\Generator\Model\Parts\GeneratorPart;
use Propel\Generator\Model\Parts\NamespacePart;
use Propel\Generator\Model\Parts\PlatformMutatorPart;
use Propel\Generator\Model\Parts\SchemaNamePart;
use Propel\Generator\Model\Parts\ScopePart;
use Propel\Generator\Model\Parts\SqlPart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Model\Parts\SchemaPart;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally<jmcnally@collab.net> (Torque)
 * @author Martin Poeschl<mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall<dlr@collab.net> (Torque)
 * @author Byron Foster <byron_foster@yahoo.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 */
class Database
{
    use BehaviorPart, CopyPart, GeneratorPart, NamespacePart, PlatformMutatorPart, SuperordinatePart, SchemaNamePart,
        SchemaPart, ScopePart, SqlPart, VendorPart;

    private Map $domains;
    private Set $tables;
    private ArrayList $sequences;

    /**
     * Constructs a new Database object.
     *
     * @param string $name The database's name
     * @param PlatformInterface $platform The database's platform
     */
    public function __construct(string $name = '', PlatformInterface $platform = null)
    {
        if ('' !== $name) {
            $this->setName($name);
        }

        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        // init
        $this->sequences = new ArrayList();
        $this->domains = new Map();
        $this->tables = new Set();
        $this->initBehaviors();
        $this->initSql();
        $this->initVendor();

        $this->identifierQuoting = false;
    }

    /**
     * @return Schema
     */
    protected function getSuperordinate(): ?Schema
    {
        return $this->schema;
    }

    /**
     * Return the list of all tables.
     *
     * @return Set
     */
    public function getTables(): Set
    {
        return $this->tables;
    }

    /**
     * Return the number of tables.
     *
     * @return int
     */
    public function getTableSize()
    {
        return $this->tables->size();
    }

    /**
     * Return the number of tables in the database.
     *
     * Read-only tables are excluded from the count.
     *
     * @return integer
     */
    public function countTables(): int
    {
        return $this->getTables()
            ->findAll(function (Table $element) {
                return !$element->isReadOnly();
            })
            ->size();
    }

    /**
     * Returns the list of all tables that have a SQL representation.
     *
     * @return Table[]
     */
    public function getTablesForSql(): array
    {
        return $this->getTables()->filter(function (Table $table) {
            return !$table->isSkipSql();
        })->toArray();
    }

    /**
     * Returns whether or not the database has a table.
     *
     * @param Table $table
     * @return bool
     */
    public function hasTable(Table $table): bool
    {
        return $this->tables->contains($table);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasTableByName(string $name): bool
    {
        return $this->tables->search($name, function (Table $table, string $query): bool {
            return $table->getName() === $query;
        });
    }

    /**
     * @param string $name
     *
     * @return Table
     */
    public function getTableByName(string $name): ?Table
    {
        return $this->tables->find($name, function (Table $table, string $query) {
            return $table->getName() === $query;
        });
    }

    /**
     * @param string $fullName
     *
     * @return bool
     */
    public function hasTableByFullName(string $fullName): bool
    {
        return $this->tables->search($fullName, function (Table $table, string $query) {
            return $table->getFullName() === $query;
        });
    }

    /**
     * @param string $fullName
     *
     * @return Table
     */
    public function getTableByFullName(string $fullName): ?Table
    {
        return $this->tables->find($fullName, function (Table $table, $query) {
            return $table->getFullName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return bool
     */
    public function hasTableByTableName(string $tableName): bool
    {
        return (bool) $this->tables->find($tableName, function (Table $table, $query) {
            return $table->getTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Table
     */
    public function getTableByTableName(string $tableName): ?Table
    {
        return $this->tables->find($tableName, function (Table $table, $query) {
            return $table->getTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return bool
     */
    public function hasTableByFullTableName(string $tableName): bool
    {
        return (bool) $this->tables->find($tableName, function (Table $table, $query) {
            return $table->getFullTableName() === $query;
        });
    }

    /**
     * @param string $tableName full qualified table name (with schema)
     *
     * @return Table
     */
    public function getTableByFullTableName(string $tableName): ?Table
    {
        return $this->tables->find($tableName, function (Table $table, $query) {
            return $table->getFullTableName() === $query;
        });
    }

    /**
     * @param string $phpName the phpName of the table
     *
     * @return bool
     */
    public function hasTableByPhpName(string $phpName): bool
    {
        return (bool) $this->tables->find($phpName, function (Table $table, string $query) {
            return $table->getPhpName() === $query;
        });
    }

    /**
     * @param string $phpName The phpName of the table
     *
     * @return Table
     */
    public function getTableByPhpName(string $phpName): ?Table
    {
        return $this->tables->find($phpName, function (Table $table, string $query) {
            return $table->getPhpName() === $query;
        });
    }

    /**
     * @return string[]
     */
    public function getTableNames(): array
    {
        return $this->tables->map(function (Table $table): string {
            return $table->getName()->toString();
        })->toArray();
    }

    /**
     * Adds a new table to this database.
     *
     * @param Table $table
     */
    public function addTable(Table $table): void
    {
        if (!$this->tables->contains($table)) {
            $this->tables->add($table);
            $table->setDatabase($this);
        }
    }

    public function removeTable(Table $table): void
    {
        $this->tables->remove($table);
    }

    /**
     * Adds several tables at once.
     *
     * @param Table[] $tables An array of Table instances
     */
    public function addTables(array $tables): void
    {
        foreach ($tables as $table) {
            $this->addTable($table);
        }
    }

    /**
     * @param string[] $sequences
     */
    public function setSequences(array $sequences): void
    {
        $this->sequences->clear();
        $this->sequences->add(...$sequences);
    }

    /**
     * @return string[]
     */
    public function getSequences(): array
    {
        return $this->sequences->toArray();
    }

    /**
     * @param string $sequence
     */
    public function addSequence(string $sequence): void
    {
        $this->sequences->add($sequence);
    }

    /**
     * @param  string $sequence
     * @return bool
     */
    public function hasSequence(string $sequence): bool
    {
        return $this->sequences->contains($sequence);
    }

    /**
     * @param string $sequence
     */
    public function removeSequence(string $sequence): void
    {
        $this->sequences->remove($sequence);
    }

    /**
     * Sets the parent schema
     *
     * @param Schema $schema The parent schema
     */
    protected function registerSchema(Schema $schema): void
    {
        $schema->addDatabase($this);
    }

    /**
     * Remove the parent schema
     *
     * @param Schema $schema
     */
    protected function unregisterSchema(Schema $schema): void
    {
        $schema->removeDatabase($this);
    }

    /**
     * Adds a domain object to this database.
     *
     * @param Domain $domain
     */
    public function addDomain(Domain $domain): void
    {
        if (!$this->domains->contains($domain)) {
            $domain->setDatabase($this);
            $this->domains->set($domain->getName(), $domain);
        }
    }

    /**
     * Returns the already configured domain object by its name.
     *
     * @param string $name
     * @return Domain
     */
    public function getDomain(string $name): ?Domain
    {
        return $this->domains->get($name);
    }

    /**
     * Returns the next behavior on all tables, ordered by behavior priority,
     * and skipping the ones that were already executed.
     *
     * @return Behavior|null
     */
    public function getNextTableBehavior(): ?Behavior
    {
        // order the behaviors according to Behavior::$tableModificationOrder
        $behaviors = [];
        $nextBehavior = null;
        foreach ($this->tables as $table) {
            foreach ($table->getBehaviors() as $behavior) {
                if (!$behavior->isTableModified()) {
                    $behaviors[$behavior->getTableModificationOrder()][] = $behavior;
                }
            }
        }
        ksort($behaviors);
        if (count($behaviors)) {
            $nextBehavior = $behaviors[key($behaviors)][0];
        }

        return $nextBehavior;
    }

    /**
     * @param Behavior $behavior
     */
    protected function registerBehavior(Behavior $behavior): void
    {
        $behavior->setDatabase($this);
    }

    /**
     * @param Behavior $behavior
     */
    protected function unregisterBehavior(Behavior $behavior): void
    {
        $behavior->setDatabase(null);
    }

    public function __toString(): string
    {
        return $this->toSql();
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $tables = [];
        foreach ($this->getTables() as $table) {
            $columns = [];
            foreach ($table->getColumns() as $column) {
                $columns[] = sprintf(
                    "      %s %s %s %s %s %s",
                    $column->getName(),
                    $column->getType(),
                    $column->getSize() ? '(' . $column->getSize() . ')' : '',
                    $column->isPrimaryKey() ? 'PK' : '',
                    $column->isNotNull() ? 'NOT NULL' : '',
                    $column->getDefaultValueString() ? "'".$column->getDefaultValueString()."'" : '',
                    $column->isAutoIncrement() ? 'AUTO_INCREMENT' : ''
                );
            }

            $fks = [];
            foreach ($table->getRelations() as $fk) {
                $fks[] = sprintf(
                    "      %s to %s.%s (%s => %s)",
                    $fk->getName(),
                    $fk->getForeignSchemaName(),
                    $fk->getForeignTableCommonName(),
                    join(', ', $fk->getLocalColumns()),
                    join(', ', $fk->getForeignColumns())
                );
            }

            $indices = [];
            foreach ($table->getIndices() as $index) {
                $indexColumns = [];
                foreach ($index->getColumns() as $indexColumnName) {
                    $indexColumns[] = sprintf('%s (%s)', $indexColumnName, $index->getColumnSize($indexColumnName));
                }
                $indices[] = sprintf(
                    "      %s (%s)",
                    $index->getName(),
                    join(', ', $indexColumns)
                );
            }

            $unices = [];
            foreach ($table->getUnices() as $index) {
                $unices[] = sprintf(
                    "      %s (%s)",
                    $index->getName(),
                    join(', ', $index->getColumns())
                );
            }

            $tableDef = sprintf(
                "  %s (%s):\n%s",
                $table->getName(),
                $table->getCommonName(),
                implode("\n", $columns)
            );

            if ($fks) {
                $tableDef .= "\n    FKs:\n" . implode("\n", $fks);
            }

            if ($indices) {
                $tableDef .= "\n    indices:\n" . implode("\n", $indices);
            }

            if ($unices) {
                $tableDef .= "\n    unices:\n". implode("\n", $unices);
            }

            $tables[] = $tableDef;
        }

        return sprintf(
            "%s:\n%s",
            $this->getName() . ($this->getSchema() ? '.'. $this->getSchema() : ''),
            implode("\n", $tables)
        );
    }
}
