<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Map;
use phootwork\json\Json;
use phootwork\json\JsonException;
use Propel\Generator\Model\Column;

/**
 * Value object for storing Column object diffs.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class ColumnDiff
{
    /**
     * A Map of modified properties.
     */
    protected Map $changedProperties;

    /**
     * The original column definition.
     */
    protected Column $fromColumn;

    /**
     * The modified column definition.
     */
    protected Column $toColumn;

    /**
     * Constructor.
     *
     * @param Column $fromColumn The original column
     * @param Column $toColumn   The modified column
     */
    public function __construct(Column $fromColumn = null, Column $toColumn = null)
    {
        if (null !== $fromColumn) {
            $this->setFromColumn($fromColumn);
        }

        if (null !== $toColumn) {
            $this->setToColumn($toColumn);
        }

        $this->changedProperties = new Map();
    }

    /**
     * Sets for the changed properties.
     *
     * @param Map $properties
     */
    public function setChangedProperties(Map $properties): void
    {
        $this->changedProperties->clear();
        $this->changedProperties->setAll($properties);
    }

    /**
     * Returns the changed properties.
     *
     * @return Map
     */
    public function getChangedProperties(): Map
    {
        return $this->changedProperties;
    }

    /**
     * Sets the fromColumn property.
     *
     * @param Column $fromColumn
     */
    public function setFromColumn(Column $fromColumn): void
    {
        $this->fromColumn = $fromColumn;
    }

    /**
     * Returns the fromColumn property.
     *
     * @return Column
     */
    public function getFromColumn(): Column
    {
        return $this->fromColumn;
    }

    /**
     * Sets the toColumn property.
     *
     * @param Column $toColumn
     */
    public function setToColumn(Column $toColumn): void
    {
        $this->toColumn = $toColumn;
    }

    /**
     * Returns the toColumn property.
     *
     * @return Column
     */
    public function getToColumn(): Column
    {
        return $this->toColumn;
    }

    /**
     * Returns the reverse diff for this diff.
     *
     * @return ColumnDiff
     */
    public function getReverseDiff(): ColumnDiff
    {
        $diff = new self();

        // columns
        $diff->setFromColumn($this->toColumn);
        $diff->setToColumn($this->fromColumn);

        // properties
        $changedProperties = [];
        foreach ($this->changedProperties as $name => $propertyChange) {
            $changedProperties[$name] = array_reverse($propertyChange);
        }
        $diff->setChangedProperties(new Map($changedProperties));

        return $diff;
    }

    /**
     * Returns the string representation of the difference.
     *
     * @return string
     * @throws JsonException If something went wrong in json encoding
     */
    public function __toString()
    {
        $ret = '';
        $ret .= sprintf("      %s:\n", $this->fromColumn->getFullyQualifiedName());
        $ret .= "        modifiedProperties:\n";
        foreach ($this->changedProperties as $key => $value) {
            $ret .= sprintf("          %s: %s\n", $key, Json::encode($value));
        }

        return $ret;
    }
}
