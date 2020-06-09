<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Map;
use phootwork\json\Json;
use phootwork\json\JsonException;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;

/**
 * Value object for storing Table object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class TableDiff
{
    /**
     * The first Table object.
     */
    protected Table $fromTable;

    /**
     * The second Table object.
     */
    protected Table $toTable;

    /**
     * The list of added columns.
     *
     * Map format:
     *  key   => The name of added column
     *  value => The added Column object
     */
    protected Map $addedColumns;

    /**
     * The list of removed columns.
     *
     * Map format:
     *  key  => The name of the removed column
     *  value => The removed Column object
     */
    protected Map $removedColumns;

    /**
     * The list of modified columns.
     *
     * Map format:
     *  key   => The name of modified column
     *  value => The ColumnDiff object, mapping the modification
     */
    protected Map $modifiedColumns;

    /**
     * The list of renamed columns.
     *
     * Map format:
     *  key   => The name of the column
     *  value => Array of Column objects [$fromColumn, $toColumn]
     */
    protected Map $renamedColumns;

    /**
     * The list of added primary key columns.
     */
    protected Map $addedPkColumns;

    /**
     * The list of removed primary key columns.
     */
    protected Map $removedPkColumns;

    /**
     * The list of renamed primary key columns.
     */
    protected Map $renamedPkColumns;

    /**
     * The list of added indices.
     *
     * Map format:
     *  key   => The name of the index
     *  value => The Index object
     */
    protected Map $addedIndices;

    /**
     * The list of removed indices.
     *
     * Map format:
     *  key   => The name of the index
     *  value => The Index object
     */
    protected Map $removedIndices;

    /**
     * The list of modified indices.
     *
     * Map format:
     *  key   => The name of the modified index
     *  value => array of Index objects [$fromIndex, $toIndex]
     */
    protected Map $modifiedIndices;

    /**
     * The list of added foreignKeys.
     *
     * Map format:
     *  key   => The name of added foreignKey
     *  value => The ForeignKey object
     */
    protected Map $addedFks;

    /**
     * The list of removed foreign keys.
     *
     * Map format:
     *  key   => The name of added foreignKey
     *  value => The ForeignKey object
     */
    protected Map $removedFks;

    /**
     * The list of modified columns.
     *
     * Map format:
     *  key   => The name of the modified foreignKey
     *  value => array of ForeignKey objects [$fromForeignKey, $toForeignKey]
     */
    protected Map $modifiedFks;

    /**
     * Constructor.
     *
     * @param Table $fromTable The first table
     * @param Table $toTable   The second table
     */
    public function __construct(Table $fromTable = null, Table $toTable = null)
    {
        $this->fromTable = $fromTable === null ?: $fromTable;
        $this->toTable = $toTable === null ?: $toTable;
        $this->addedColumns     = new Map();
        $this->removedColumns   = new Map();
        $this->modifiedColumns  = new Map();
        $this->renamedColumns   = new Map();
        $this->addedPkColumns   = new Map();
        $this->removedPkColumns = new Map();
        $this->renamedPkColumns = new Map();
        $this->addedIndices     = new Map();
        $this->modifiedIndices  = new Map();
        $this->removedIndices   = new Map();
        $this->addedFks         = new Map();
        $this->modifiedFks      = new Map();
        $this->removedFks       = new Map();
    }

    /**
     * Sets the fromTable property.
     *
     * @param Table $fromTable
     */
    public function setFromTable(Table $fromTable): void
    {
        $this->fromTable = $fromTable;
    }

    /**
     * Returns the fromTable property.
     *
     * @return Table
     */
    public function getFromTable(): Table
    {
        return $this->fromTable;
    }

    /**
     * Sets the toTable property.
     *
     * @param Table $toTable
     */
    public function setToTable(Table $toTable): void
    {
        $this->toTable = $toTable;
    }

    /**
     * Returns the toTable property.
     *
     * @return Table
     */
    public function getToTable(): Table
    {
        return $this->toTable;
    }

    /**
     * Sets the added columns.
     *
     * @param Map $columns
     */
    public function setAddedColumns(Map $columns): void
    {
        $this->addedColumns->clear();
        $this->addedColumns->setAll($columns);
    }

    /**
     * Returns the list of added columns
     *
     * @return Map
     */
    public function getAddedColumns(): Map
    {
        return $this->addedColumns;
    }

    /**
     * Setter for the removedColumns property
     *
     * @param Map $removedColumns
     */
    public function setRemovedColumns(Map $removedColumns): void
    {
        $this->removedColumns->clear();
        $this->removedColumns->setAll($removedColumns);
    }

    /**
     * Getter for the removedColumns property.
     *
     * @return Map
     */
    public function getRemovedColumns(): Map
    {
        return $this->removedColumns;
    }

    /**
     * Sets the list of modified columns.
     *
     * @param Map $modifiedColumns An associative array of ColumnDiff objects
     */
    public function setModifiedColumns(Map $modifiedColumns): void
    {
        $this->modifiedColumns->clear();
        $this->modifiedColumns->setAll($modifiedColumns);
    }

    /**
     * Getter for the modifiedColumns property
     *
     * @return Map
     */
    public function getModifiedColumns(): Map
    {
        return $this->modifiedColumns;
    }

    /**
     * Sets the list of renamed columns.
     *
     * @param Map $renamedColumns
     */
    public function setRenamedColumns(Map $renamedColumns): void
    {
        $this->renamedColumns->clear();
        $this->renamedColumns->setAll($renamedColumns);
    }

    /**
     * Getter for the renamedColumns property
     *
     * @return Map
     */
    public function getRenamedColumns(): Map
    {
        return $this->renamedColumns;
    }

    /**
     * Sets the list of added primary key columns.
     *
     * @param Map $addedPkColumns
     */
    public function setAddedPkColumns(Map $addedPkColumns): void
    {
        $this->addedPkColumns->clear();
        $this->addedPkColumns->setAll($addedPkColumns);
    }

    /**
     * Getter for the addedPkColumns property
     *
     * @return Map
     */
    public function getAddedPkColumns(): Map
    {
        return $this->addedPkColumns;
    }

    /**
     * Sets the list of removed primary key columns.
     *
     * @param Map $removedPkColumns
     */
    public function setRemovedPkColumns(Map $removedPkColumns): void
    {
        $this->removedPkColumns->clear();
        $this->removedPkColumns->setAll($removedPkColumns);
    }

    /**
     * Getter for the removedPkColumns property
     *
     * @return Map
     */
    public function getRemovedPkColumns(): Map
    {
        return $this->removedPkColumns;
    }

    /**
     * Sets the list of all renamed primary key columns.
     *
     * @param Map $renamedPkColumns
     */
    public function setRenamedPkColumns(Map $renamedPkColumns): void
    {
        $this->renamedPkColumns->clear();
        $this->renamedPkColumns->setAll($renamedPkColumns);
    }

    /**
     * Getter for the renamedPkColumns property
     *
     * @return Map
     */
    public function getRenamedPkColumns(): Map
    {
        return $this->renamedPkColumns;
    }

    /**
     * Whether the primary key was modified
     *
     * @return boolean
     */
    public function hasModifiedPk(): bool
    {
        return
            !$this->renamedPkColumns->isEmpty() ||
            !$this->removedPkColumns->isEmpty() ||
            !$this->addedPkColumns->isEmpty()
        ;
    }

    /**
     * Sets the list of new added indices.
     *
     * @param Map $addedIndices
     */
    public function setAddedIndices(Map $addedIndices): void
    {
        $this->addedIndices->clear();
        $this->addedIndices->setAll($addedIndices);
    }

    /**
     * Getter for the addedIndices property
     *
     * @return Map
     */
    public function getAddedIndices(): Map
    {
        return $this->addedIndices;
    }

    /**
     * Set the list of removed indices.
     *
     * @param Map $removedIndices
     */
    public function setRemovedIndices(Map $removedIndices): void
    {
        $this->removedIndices->clear();
        $this->removedIndices->setAll($removedIndices);
    }

    /**
     * Getter for the removedIndices property
     *
     * @return Map
     */
    public function getRemovedIndices(): Map
    {
        return $this->removedIndices;
    }

    /**
     * Sets the list of modified indices.
     *
     * Array must be [ [ Index $fromIndex, Index $toIndex ], [ ... ] ]
     *
     * @param Map $modifiedIndices A set of modified indices
     */
    public function setModifiedIndices(Map $modifiedIndices): void
    {
        $this->modifiedIndices->clear();
        $this->modifiedIndices->setAll($modifiedIndices);
    }

    /**
     * Getter for the modifiedIndices property
     *
     * @return Map
     */
    public function getModifiedIndices(): Map
    {
        return $this->modifiedIndices;
    }

    /**
     * Sets the list of added foreign keys.
     *
     * @param Map $addedFks
     */
    public function setAddedFks(Map $addedFks): void
    {
        $this->addedFks->clear();
        $this->addedFks->setAll($addedFks);
    }

    /**
     * Getter for the addedFks property
     *
     * @return Map
     */
    public function getAddedFks(): Map
    {
        return $this->addedFks;
    }

    /**
     * Sets the list of removed foreign keys.
     *
     * @param Map $removedFks
     */
    public function setRemovedFks(Map $removedFks): void
    {
        $this->removedFks->clear();
        $this->removedFks->setAll($removedFks);
    }

    /**
     * Returns the list of removed foreign keys.
     *
     * @return Map
     */
    public function getRemovedFks(): Map
    {
        return $this->removedFks;
    }

    /**
     * Sets the list of modified foreign keys.
     *
     * Array must be [ [ ForeignKey $fromFk, ForeignKey $toFk ], [ ... ] ]
     *
     * @param Map $modifiedFks
     */
    public function setModifiedFks(Map $modifiedFks): void
    {
        $this->modifiedFks->clear();
        $this->modifiedFks->setAll($modifiedFks);
    }

    /**
     * Returns the list of modified foreign keys.
     *
     * @return Map
     */
    public function getModifiedFks(): Map
    {
        return $this->modifiedFks;
    }

    /**
     * Returns whether or not there are
     * some modified foreign keys.
     *
     * @return boolean
     */
    public function hasModifiedFks(): bool
    {
        return !$this->modifiedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some modified indices.
     *
     * @return boolean
     */
    public function hasModifiedIndices(): bool
    {
        return !$this->modifiedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some modified columns.
     *
     * @return boolean
     */
    public function hasModifiedColumns(): bool
    {
        return !$this->modifiedColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed foreign keys.
     *
     * @return boolean
     */
    public function hasRemovedFks(): bool
    {
        return !$this->removedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed indices.
     *
     * @return boolean
     */
    public function hasRemovedIndices(): bool
    {
        return !$this->removedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some renamed columns.
     *
     * @return boolean
     */
    public function hasRenamedColumns(): bool
    {
        return !$this->renamedColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed columns.
     *
     * @return boolean
     */
    public function hasRemovedColumns(): bool
    {
        return !$this->removedColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added columns.
     *
     * @return boolean
     */
    public function hasAddedColumns(): bool
    {
        return !$this->addedColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added indices.
     *
     * @return boolean
     */
    public function hasAddedIndices(): bool
    {
        return !$this->addedIndices->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added foreign keys.
     *
     * @return boolean
     */
    public function hasAddedFks(): bool
    {
        return !$this->addedFks->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some added primary key columns.
     *
     * @return boolean
     */
    public function hasAddedPkColumns(): bool
    {
        return !$this->addedPkColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some removed primary key columns.
     *
     * @return boolean
     */
    public function hasRemovedPkColumns(): bool
    {
        return !$this->removedPkColumns->isEmpty();
    }

    /**
     * Returns whether or not there are
     * some renamed primary key columns.
     *
     * @return boolean
     */
    public function hasRenamedPkColumns(): bool
    {
        return !$this->renamedPkColumns->isEmpty();
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return TableDiff
     */
    public function getReverseDiff(): TableDiff
    {
        $diff = new self();

        // tables
        $diff->setFromTable($this->toTable);
        $diff->setToTable($this->fromTable);

        // columns
        if ($this->hasAddedColumns()) {
            $diff->setRemovedColumns($this->addedColumns);
        }

        if ($this->hasRemovedColumns()) {
            $diff->setAddedColumns($this->removedColumns);
        }

        if ($this->hasRenamedColumns()) {
            $renamedColumns = [];
            foreach ($this->renamedColumns as $columnRenaming) {
                $renamedColumns[$columnRenaming[1]->getName()] = array_reverse($columnRenaming);
            }
            $diff->setRenamedColumns(new Map($renamedColumns));
        }

        if ($this->hasModifiedColumns()) {
            $columnDiffs = [];
            foreach ($this->modifiedColumns as $name => $columnDiff) {
                $columnDiffs[$name] = $columnDiff->getReverseDiff();
            }
            $diff->setModifiedColumns(new Map($columnDiffs));
        }

        // pks
        if ($this->hasRemovedPkColumns()) {
            $diff->setAddedPkColumns($this->removedPkColumns);
        }

        if ($this->hasAddedPkColumns()) {
            $diff->setRemovedPkColumns($this->addedPkColumns);
        }

        if ($this->hasRenamedPkColumns()) {
            $renamedPkColumns = [];
            foreach ($this->renamedPkColumns as $columnRenaming) {
                $renamedPkColumns[$columnRenaming[1]->getName()] = array_reverse($columnRenaming);
            }
            $diff->setRenamedPkColumns(new Map($renamedPkColumns));
        }

        // indices
        if ($this->hasRemovedIndices()) {
            $diff->setAddedIndices($this->removedIndices);
        }

        if ($this->hasAddedIndices()) {
            $diff->setRemovedIndices($this->addedIndices);
        }

        if ($this->hasModifiedIndices()) {
            $indexDiffs = [];
            foreach ($this->modifiedIndices as $name => $indexDiff) {
                $indexDiffs[$name] = array_reverse($indexDiff);
            }
            $diff->setModifiedIndices(new Map($indexDiffs));
        }

        // fks
        if ($this->hasAddedFks()) {
            $diff->setRemovedFks($this->addedFks);
        }

        if ($this->hasRemovedFks()) {
            $diff->setAddedFks($this->removedFks);
        }

        if ($this->hasModifiedFks()) {
            $fkDiffs = [];
            foreach ($this->modifiedFks as $name => $fkDiff) {
                $fkDiffs[$name] = array_reverse($fkDiff);
            }
            $diff->setModifiedFks(new Map($fkDiffs));
        }

        return $diff;
    }

    /**
     * Returns the string representation of this object.
     *
     * @return string
     * @throws JsonException
     */
    public function __toString(): string
    {
        $ret = '';
        $ret .= sprintf("  %s:\n", $this->fromTable->getName());
        if ($addedColumns = $this->getAddedColumns()) {
            $ret .= "    addedColumns:\n";
            foreach ($addedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($removedColumns = $this->getRemovedColumns()) {
            $ret .= "    removedColumns:\n";
            foreach ($removedColumns as $colname => $column) {
                $ret .= sprintf("      - %s\n", $colname);
            }
        }
        if ($modifiedColumns = $this->getModifiedColumns()) {
            $ret .= "    modifiedColumns:\n";
            foreach ($modifiedColumns as $colDiff) {
                $ret .= (string) $colDiff;
            }
        }
        if ($renamedColumns = $this->getRenamedColumns()) {
            $ret .= "    renamedColumns:\n";
            foreach ($renamedColumns as $columnRenaming) {
                [$fromColumn, $toColumn] = $columnRenaming;
                $ret .= sprintf("      %s: %s\n", $fromColumn->getName(), $toColumn->getName());
            }
        }
        if ($addedIndices = $this->getAddedIndices()) {
            $ret .= "    addedIndices:\n";
            foreach ($addedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($removedIndices = $this->getRemovedIndices()) {
            $ret .= "    removedIndices:\n";
            foreach ($removedIndices as $indexName => $index) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($modifiedIndices = $this->getModifiedIndices()) {
            $ret .= "    modifiedIndices:\n";
            foreach ($modifiedIndices as $indexName => $indexDiff) {
                $ret .= sprintf("      - %s\n", $indexName);
            }
        }
        if ($addedFks = $this->getAddedFks()) {
            $ret .= "    addedFks:\n";
            foreach ($addedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($removedFks = $this->getRemovedFks()) {
            $ret .= "    removedFks:\n";
            foreach ($removedFks as $fkName => $fk) {
                $ret .= sprintf("      - %s\n", $fkName);
            }
        }
        if ($modifiedFks = $this->getModifiedFks()) {
            $ret .= "    modifiedFks:\n";
            foreach ($modifiedFks as $fkName => $fkFromTo) {
                $ret .= sprintf("      %s:\n", $fkName);
                /**
                 * @var ForeignKey $fromFk
                 * @var ForeignKey $toFk
                 */
                [$fromFk, $toFk] = $fkFromTo;
                $fromLocalColumns = Json::encode($fromFk->getLocalColumns()->toArray());
                $toLocalColumns = Json::encode($toFk->getLocalColumns()->toArray());

                if ($fromLocalColumns != $toLocalColumns) {
                    $ret .= sprintf("          localColumns: from %s to %s\n", $fromLocalColumns, $toLocalColumns);
                }
                $fromForeignColumns = Json::encode($fromFk->getForeignColumns()->toArray());
                $toForeignColumns = Json::encode($toFk->getForeignColumns()->toArray());
                if ($fromForeignColumns != $toForeignColumns) {
                    $ret .= sprintf("          foreignColumns: from %s to %s\n", $fromForeignColumns, $toForeignColumns);
                }
                if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) != $toFk->normalizeFKey($toFk->getOnUpdate())) {
                    $ret .= sprintf("          onUpdate: from %s to %s\n", $fromFk->getOnUpdate(), $toFk->getOnUpdate());
                }
                if ($fromFk->normalizeFKey($fromFk->getOnDelete()) != $toFk->normalizeFKey($toFk->getOnDelete())) {
                    $ret .= sprintf("          onDelete: from %s to %s\n", $fromFk->getOnDelete(), $toFk->getOnDelete());
                }
            }
        }

        return $ret;
    }
}
