<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Common\Collection\Map;
use Propel\Generator\Model\Model;

/**
 * Value object for storing Database object diffs
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class DatabaseDiff
{
    /** @var Map */
    protected $addedTables;

    /** @var Map */
    protected $removedTables;

    /** @var Map */
    protected $modifiedTables;

    /** @var Map  */
    protected $renamedTables;

    /** @var Map  */
    protected $possibleRenamedTables;

    public function __construct()
    {
        $this->addedTables    = new Map();
        $this->removedTables  = new Map();
        $this->modifiedTables = new Map();
        $this->renamedTables  = new Map();
        $this->possibleRenamedTables  = new Map();
    }

    /**
     * @return Map
     */
    public function getPossibleRenamedTables(): Map
    {
        return $this->possibleRenamedTables;
    }

    /**
     * Returns the list of added tables.
     *
     * @return Map
     */
    public function getAddedTables(): Map
    {
        return $this->addedTables;
    }

    /**
     * Set the addedTables collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setAddedTables(Map $tables): void
    {
        $this->addedTables->clear();
        $this->addedTables->setAll($tables);
    }

    /**
     * Returns the list of removed tables.
     *
     * @return Map
     */
    public function getRemovedTables(): Map
    {
        return $this->removedTables;
    }

    /**
     * Set the removedTables collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setRemovedTables(Map $tables): void
    {
        $this->removedTables->clear();
        $this->removedTables->setAll($tables);
    }

    /**
     * Returns the modified tables.
     *
     * @return Map
     */
    public function getModifiedTables(): Map
    {
        return $this->modifiedTables;
    }

    /**
     * Set the modifiedTables collection: all data will be overridden.
     *
     * @param Map $tables
     */
    public function setModifiedTables(Map $tables): void
    {
        $this->modifiedTables->clear();
        $this->modifiedTables->setAll($tables);
    }

    /**
     * Returns the list of renamed tables.
     *
     * @return Map
     */
    public function getRenamedTables(): Map
    {
        return $this->renamedTables;
    }

    /**
     * Set the renamedTables collection: all data will be overridden.
     *
     * @param Map $table
     */
    public function setRenamedTables(Map $table): void
    {
        $this->renamedTables->clear();
        $this->renamedTables->setAll($table);
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return DatabaseDiff
     */
    public function getReverseDiff(): DatabaseDiff
    {
        $diff = new self();
        $diff->setAddedTables($this->getRemovedTables());
        // idMethod is not set for tables build from reverse engineering
        // FIXME: this should be handled by reverse classes
        foreach ($diff->getAddedTables() as $table) {
            if ($table->getIdMethod() == Model::ID_METHOD_NONE) {
                $table->setIdMethod(Model::ID_METHOD_NATIVE);
            }
        }
        $diff->setRemovedTables($this->getAddedTables());
        $diff->setRenamedTables(new Map(array_flip($this->getRenamedTables()->toArray())));
        $tableDiffs = new Map();
        foreach ($this->getModifiedTables() as $name => $tableDiff) {
            $tableDiffs->set($name, $tableDiff->getReverseDiff());
        }
        $diff->setModifiedTables($tableDiffs);

        return $diff;
    }

    /**
     * Returns a description of the database modifications.
     *
     * @return string
     */
    public function getDescription(): string
    {
        $changes = [];
        if ($count = $this->getAddedTables()->size()) {
            $changes[] = sprintf('%d added tables', $count);
        }
        if ($count = $this->getRemovedTables()->size()) {
            $changes[] = sprintf('%d removed tables', $count);
        }
        if ($count = $this->getModifiedTables()->size()) {
            $changes[] = sprintf('%d modified tables', $count);
        }
        if ($count = $this->getRenamedTables()->size()) {
            $changes[] = sprintf('%d renamed tables', $count);
        }

        return implode(', ', $changes);
    }

    public function __toString()
    {
        $ret = '';
        if ($addedTables = $this->getAddedTables()) {
            $ret .= "addedTables:\n";
            foreach ($addedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($removedTables = $this->getRemovedTables()) {
            $ret .= "removedTables:\n";
            foreach ($removedTables as $tableName => $table) {
                $ret .= sprintf("  - %s\n", $tableName);
            }
        }
        if ($modifiedTables = $this->getModifiedTables()) {
            $ret .= "modifiedTables:\n";
            foreach ($modifiedTables as $tableDiff) {
                $ret .= $tableDiff->__toString();
            }
        }
        if ($renamedTables = $this->getRenamedTables()) {
            $ret .= "renamedTables:\n";
            foreach ($renamedTables as $fromName => $toName) {
                $ret .= sprintf("  %s: %s\n", $fromName, $toName);
            }
        }

        return $ret;
    }
}
