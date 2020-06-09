<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Set;
use Propel\Generator\Model\Database;

/**
 * Service class for comparing Database objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseComparator
{
    protected DatabaseDiff $databaseDiff;
    protected Database $fromDatabase;
    protected Database $toDatabase;

    /**
     * Whether we should detect renamings and track it via `addRenamedTable` at the
     * DatabaseDiff object.
     */
    protected bool $withRenaming = false;

    protected bool $removeTable = true;

    /**
     * @var Set list of excluded tables
     */
    protected Set $excludedTables;

    public function __construct(DatabaseDiff $databaseDiff = null)
    {
        $this->databaseDiff = $databaseDiff ?? new DatabaseDiff();
        $this->excludedTables = new Set();
    }

    public function getDatabaseDiff(): DatabaseDiff
    {
        return $this->databaseDiff;
    }

    /**
     * Sets the fromDatabase property.
     *
     * @param Database $fromDatabase
     */
    public function setFromDatabase(Database $fromDatabase): void
    {
        $this->fromDatabase = $fromDatabase;
    }

    //@todo never used: remove?
    /**
     * Returns the fromDatabase property.
     *
     * @return Database
     */
    public function getFromDatabase(): Database
    {
        return $this->fromDatabase;
    }

    /**
     * Sets the toDatabase property.
     *
     * @param Database $toDatabase
     */
    public function setToDatabase(Database $toDatabase): void
    {
        $this->toDatabase = $toDatabase;
    }

    //@todo never used: remove?
    /**
     * Returns the toDatabase property.
     *
     * @return Database
     */
    public function getToDatabase(): Database
    {
        return $this->toDatabase;
    }

    /**
     * Set true to handle removed tables or false to ignore them
     *
     * @param boolean $removeTable
     */
    public function setRemoveTable(bool $removeTable): void
    {
        $this->removeTable = $removeTable;
    }

    /**
     * @return boolean
     */
    public function getRemoveTable(): bool
    {
        return $this->removeTable;
    }

    /**
     * Set the list of tables excluded from the comparison
     *
     * @param Set $excludedTables set the list of table name
     */
    public function setExcludedTables(?Set $excludedTables): void
    {
        if (null === $excludedTables) {
            $excludedTables = [];
        }
        $this->excludedTables->clear();
        $this->excludedTables->add(...$excludedTables);
    }

    /**
     * Returns the list of tables excluded from the comparison
     *
     * @return Set
     */
    public function getExcludedTables(): Set
    {
        return $this->excludedTables;
    }

    /**
     * Returns the computed difference between two database objects.
     *
     * @param  Database  $fromDatabase
     * @param  Database  $toDatabase
     * @param  bool      $withRenaming
     * @param  bool      $removeTable
     * @param  Set     $excludedTables Tables to exclude from the difference computation
     *
     * @return DatabaseDiff
     */
    public static function computeDiff(
        Database $fromDatabase,
        Database $toDatabase,
        bool $withRenaming = false,
        bool $removeTable = true,
        ?Set $excludedTables = null
    ): ?DatabaseDiff
    {
        $databaseComparator = new self();
        $databaseComparator->setFromDatabase($fromDatabase);
        $databaseComparator->setToDatabase($toDatabase);
        $databaseComparator->setWithRenaming($withRenaming);
        $databaseComparator->setRemoveTable($removeTable);
        $databaseComparator->setExcludedTables($excludedTables);

        $platform = $toDatabase->getPlatform() ?: $fromDatabase->getPlatform();

        if ($platform) {
            foreach ($fromDatabase->getTables() as $table) {
                $platform->normalizeTable($table);
            }
            foreach ($toDatabase->getTables() as $table) {
                $platform->normalizeTable($table);
            }
        }

        $differences = 0;
        $differences += $databaseComparator->compareTables();

        return ($differences > 0) ? $databaseComparator->getDatabaseDiff() : null;
    }

    /**
     * @param boolean $withRenaming
     */
    public function setWithRenaming(bool $withRenaming): void
    {
        $this->withRenaming = $withRenaming;
    }

    /**
     * @return boolean
     */
    public function getWithRenaming(): bool
    {
        return $this->withRenaming;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the tables of the fromDatabase and the toDatabase, and modifies
     * the inner databaseDiff if necessary.
     *
     * @return integer
     */
    public function compareTables(): int
    {
        $fromDatabaseTables = $this->fromDatabase->getTables();
        $toDatabaseTables = $this->toDatabase->getTables();
        $databaseDifferences = 0;

        // check for new tables in $toDatabase
        foreach ($toDatabaseTables as $table) {
            if ($this->excludedTables->contains($table->getName())) {
                continue;
            }
            if (!$this->fromDatabase->hasTableByName($table->getName()) && !$table->isSkipSql()) {
                $this->databaseDiff->getAddedTables()->set($table->getName(), $table);
                $databaseDifferences++;
            }
        }

        // check for removed tables in $toDatabase
        if ($this->getRemoveTable()) {
            foreach ($fromDatabaseTables as $table) {
                if ($this->excludedTables->contains($table->getName())) {
                    continue;
                }
                if (!$this->toDatabase->hasTableByName($table->getName()) && !$table->isSkipSql()) {
                    $this->databaseDiff->getRemovedTables()->set($table->getName(), $table);
                    $databaseDifferences++;
                }
            }
        }

        // check for table differences
        foreach ($fromDatabaseTables as $fromTable) {
            if ($this->excludedTables->contains($fromTable->getName())) {
                continue;
            }
            if ($this->toDatabase->hasTableByName($fromTable->getName())) {
                $toTable = $this->toDatabase->getTableByName($fromTable->getName());
                $databaseDiff = TableComparator::computeDiff($fromTable, $toTable);
                if (null !== $databaseDiff) {
                    $this->databaseDiff->getModifiedTables()->set($fromTable->getName(), $databaseDiff);
                    $databaseDifferences++;
                }
            }
        }

        // check for table renamings
        foreach ($this->databaseDiff->getAddedTables()->toArray() as $addedTableName => $addedTable) {
            foreach ($this->databaseDiff->getRemovedTables()->toArray() as $removedTableName => $removedTable) {
                if (null === TableComparator::computeDiff($addedTable, $removedTable)) {
                    // no difference except the name, that's probably a renaming
                    if ($this->getWithRenaming()) {
                        $this->databaseDiff->getRenamedTables()->set($removedTableName, $addedTableName);
                        $this->databaseDiff->getAddedTables()->remove($addedTableName);
                        $this->databaseDiff->getRemovedTables()->remove($removedTableName);
                        $databaseDifferences--;
                    } else {
                        $this->databaseDiff->getPossibleRenamedTables()->set($removedTableName, $addedTableName);
                    }
                    // skip to the next added table
                    break;
                }
            }
        }

        return $databaseDifferences;
    }
}
