<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\Table;
use Propel\Generator\Model\Index;

/**
 * Service class for comparing Table objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class TableComparator
{
    /**
     * The table difference.
     */
    protected TableDiff $tableDiff;

    /**
     * Constructor.
     *
     * @param TableDiff $tableDiff
     */
    public function __construct(TableDiff $tableDiff = null)
    {
        $this->tableDiff = $tableDiff ?? new TableDiff();
    }

    /**
     * Returns the table difference.
     *
     * @return TableDiff
     */
    public function getTableDiff(): TableDiff
    {
        return $this->tableDiff;
    }

    /**
     * Sets the table the comparator starts from.
     *
     * @param Table $fromTable
     */
    public function setFromTable(Table $fromTable): void
    {
        $this->tableDiff->setFromTable($fromTable);
    }

    /**
     * Returns the table the comparator starts from.
     *
     * @return Table
     */
    public function getFromTable(): Table
    {
        return $this->tableDiff->getFromTable();
    }

    /**
     * Sets the table the comparator goes to.
     *
     * @param Table $toTable
     */
    public function setToTable(Table $toTable): void
    {
        $this->tableDiff->setToTable($toTable);
    }

    /**
     * Returns the table the comparator goes to.
     *
     * @return Table
     */
    public function getToTable(): Table
    {
        return $this->tableDiff->getToTable();
    }

    /**
     * Returns the computed difference between two table objects.
     *
     * @param  Table             $fromTable
     * @param  Table             $toTable
     * @return TableDiff|Boolean
     */
    public static function computeDiff(Table $fromTable, Table $toTable): ?TableDiff
    {
        $tc = new self();

        $tc->setFromTable($fromTable);
        $tc->setToTable($toTable);

        $differences = 0;
        $differences += $tc->compareColumns();
        $differences += $tc->comparePrimaryKeys();
        $differences += $tc->compareIndices();
        $differences += $tc->compareForeignKeys();
        return ($differences > 0) ? $tc->getTableDiff() : null;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the columns of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareColumns(): int
    {
        $fromTableColumns = $this->getFromTable()->getColumns();
        $toTableColumns = $this->getToTable()->getColumns();
        $columnDifferences = 0;

        // check for new columns in $toTable
        foreach ($toTableColumns as $column) {
            if (!$this->getFromTable()->hasColumn($column->getName())) {
                $this->tableDiff->getAddedColumns()->set($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for removed columns in $toTable
        foreach ($fromTableColumns as $column) {
            if (!$this->getToTable()->hasColumn($column->getName())) {
                $this->tableDiff->getRemovedColumns()->set($column->getName(), $column);
                $columnDifferences++;
            }
        }

        // check for column differences
        foreach ($fromTableColumns as $fromColumn) {
            if ($this->getToTable()->hasColumn($fromColumn->getName())) {
                $toColumn = $this->getToTable()->getColumn($fromColumn->getName());
                $columnDiff = ColumnComparator::computeDiff($fromColumn, $toColumn);
                if (null !== $columnDiff) {
                    $this->tableDiff->getModifiedColumns()->set($fromColumn->getName(), $columnDiff);
                    $columnDifferences++;
                }
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedColumns()->toArray() as $addedColumnName => $addedColumn) {
            foreach ($this->tableDiff->getRemovedColumns() as $removedColumnName => $removedColumn) {
                if (null === ColumnComparator::computeDiff($addedColumn, $removedColumn)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->getRenamedColumns()->set($removedColumnName, [$removedColumn, $addedColumn]);
                    $this->tableDiff->getAddedColumns()->remove($addedColumnName);
                    $this->tableDiff->getRemovedColumns()->remove($removedColumnName);
                    $columnDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $columnDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compares the primary keys of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function comparePrimaryKeys(): int
    {
        $pkDifferences = 0;
        $fromTablePk = $this->getFromTable()->getPrimaryKey();
        $toTablePk = $this->getToTable()->getPrimaryKey();

        // check for new pk columns in $toTable
        foreach ($toTablePk as $column) {
            if (!$this->getFromTable()->hasColumn($column->getName()) ||
                !$this->getFromTable()->getColumn($column->getName())->isPrimaryKey()) {
                $this->tableDiff->getAddedPkColumns()->set($column->getName(), $column);
                $pkDifferences++;
            }
        }

        // check for removed pk columns in $toTable
        foreach ($fromTablePk as $column) {
            if (!$this->getToTable()->hasColumn($column->getName()) ||
                !$this->getToTable()->getColumn($column->getName())->isPrimaryKey()) {
                $this->tableDiff->getRemovedPkColumns()->set($column->getName(), $column);
                $pkDifferences++;
            }
        }

        // check for column renamings
        foreach ($this->tableDiff->getAddedPkColumns()->toArray() as $addedColumnName => $addedColumn) {
            foreach ($this->tableDiff->getRemovedPkColumns() as $removedColumnName => $removedColumn) {
                if (null === ColumnComparator::computeDiff($addedColumn, $removedColumn)) {
                    // no difference except the name, that's probably a renaming
                    $this->tableDiff->getRenamedPkColumns()->set($removedColumnName, [$removedColumn, $addedColumn]);
                    $this->tableDiff->getAddedPkColumns()->remove($addedColumnName);
                    $this->tableDiff->getRemovedPkColumns()->remove($removedColumnName);
                    $pkDifferences--;
                    // skip to the next added column
                    break;
                }
            }
        }

        return $pkDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the indices and unique indices of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareIndices(): int
    {
        $indexDifferences = 0;
        $fromTableIndices = array_merge($this->getFromTable()->getIndices()->toArray(), $this->getFromTable()->getUnices()->toArray());
        $toTableIndices = array_merge($this->getToTable()->getIndices()->toArray(), $this->getToTable()->getUnices()->toArray());

        /** @var  Index $fromTableIndex */
        foreach ($fromTableIndices as $fromTableIndexPos => $fromTableIndex) {
            /** @var  Index $toTableIndex */
            foreach ($toTableIndices as $toTableIndexPos => $toTableIndex) {
                if ($fromTableIndex->getName() === $toTableIndex->getName()) {
                    if (false === IndexComparator::computeDiff($fromTableIndex, $toTableIndex)) {
                        //no changes
                        unset($fromTableIndices[$fromTableIndexPos]);
                        unset($toTableIndices[$toTableIndexPos]);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->getModifiedIndices()->set($fromTableIndex->getName(), [$fromTableIndex, $toTableIndex]);
                        unset($fromTableIndices[$fromTableIndexPos]);
                        unset($toTableIndices[$toTableIndexPos]);
                        $indexDifferences++;
                    }
                }
            }
        }

        foreach ($fromTableIndices as $fromTableIndex) {
            $this->tableDiff->getRemovedIndices()->set($fromTableIndex->getName(), $fromTableIndex);
            $indexDifferences++;
        }

        foreach ($toTableIndices as $toTableIndex) {
            $this->tableDiff->getAddedIndices()->set($toTableIndex->getName(), $toTableIndex);
            $indexDifferences++;
        }

        return $indexDifferences;
    }

    /**
     * Returns the number of differences.
     *
     * Compare the foreign keys of the fromTable and the toTable,
     * and modifies the inner tableDiff if necessary.
     *
     * @return integer
     */
    public function compareForeignKeys(): int
    {
        $fkDifferences = 0;
        $fromTableFks = $this->getFromTable()->getForeignKeys();
        $toTableFks = $this->getToTable()->getForeignKeys();

        foreach ($fromTableFks as $fromTableFkPos => $fromTableFk) {
            foreach ($toTableFks as $toTableFkPos => $toTableFk) {
                if ($fromTableFk->getName() === $toTableFk->getName()) {
                    if (false === ForeignKeyComparator::computeDiff($fromTableFk, $toTableFk)) {
                        unset($fromTableFks[$fromTableFkPos]);
                        unset($toTableFks[$toTableFkPos]);
                    } else {
                        // same name, but different columns
                        $this->tableDiff->getModifiedFks()->set($fromTableFk->getName(), [$fromTableFk, $toTableFk]);
                        unset($fromTableFks[$fromTableFkPos]);
                        unset($toTableFks[$toTableFkPos]);
                        $fkDifferences++;
                    }
                }
            }
        }

        foreach ($fromTableFks as $fromTableFk) {
            if (!$fromTableFk->isSkipSql() && !in_array($fromTableFk, $toTableFks)) {
                $this->tableDiff->getRemovedFks()->set($fromTableFk->getName(), $fromTableFk);
                $fkDifferences++;
            }
        }

        foreach ($toTableFks as $toTableFk) {
            if (!$toTableFk->isSkipSql() && !in_array($toTableFk, $fromTableFks)) {
                $this->tableDiff->getAddedFks()->set($toTableFk->getName(), $toTableFk);
                $fkDifferences++;
            }
        }

        return $fkDifferences;
    }
}
