<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\lang\Text;
use Propel\Common\Collection\Set;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Column;

/**
 * Trait Columns part.
 * Methods to manage a collection of columns.
 *
 * @author Cristiano Cinotti
 */
trait ColumnsPart
{
    /**
     * @var Set
     */
    private $columns;

    abstract public function getTable(): ?Table;
    abstract public function getName(): Text;

    public function initColumns()
    {
        $this->columns =new Set([], Column::class);
    }

    /**
     * Return the Column object with the given name.
     *
     * @param string $name
     *
     * @return Column|null
     */
    public function getColumnByName(string $name): ?Column
    {
        return $this->columns->find(function (Column $element) use ($name): bool {
            return $element->getName() === $name;
        });
    }

    /**
     * Return the Column object with the given name (case insensitive search).
     *
     * @param string $name
     *
     * @return null|Column
     */
    public function getColumnByLowercaseName(string $name): ?Column
    {
        return $this->columns->find(function (Column $element) use ($name): bool {
            return strtolower($element->getName()) === strtolower($name);
        });
    }

    /**
     * Adds a new column to the object.
     * If the object is an Table, the column name must be unique.
     *
     * @param Column $column
     *
     * @throws EngineException If the column is already added
     */
    public function addColumn(Column $column): void
    {
        if (null !== $this->getTable()) {
            $column->setTable($this->getTable());
        }
        $this->columns->add($column);
    }

    /**
     * Adds several columns at once.
     *
     * @param Column[] $columns An array of Column instance
     */
    public function addColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns whether or not the table has a column.
     *
     * @param Column|string $column The Column object or its name
     *
     * @return bool
     */
    public function hasColumn($column): bool
    {
        if ($column instanceof Column) {
            return $this->columns->contains($column);
        }

        return (bool) $this->getColumnByName($column);
    }

    /**
     * Returns the Column object with the specified name.
     *
     * @param string $name The name of the column (e.g. 'my_column')
     *
     * @return Column
     */
    public function getColumn(string $name): Column
    {
        if (!$this->hasColumn($name)) {
            $columnsList = '';
            $this->columns->each(function (Column $element) use ($columnsList): void {
                $columnsList .= $element->getName() . ', ';
            });
            $columnsList = substr($columnsList, 0, -2);

            throw new \InvalidArgumentException(
                sprintf(
                "Column `%s` not found in %s `%s` [%s]",
                $name,
                get_class($this),
                $this->getName(),
                $columnsList
            )
            );
        }

        return $this->getColumnByName($name);
    }

    /**
     * Returns an array containing all Column objects.
     *
     * @return Set
     */
    public function getColumns(): Set
    {
        return $this->columns;
    }

    /**
     * Removes a column from the columns collection.
     *
     * @param  Column $column The Column or its name
     *
     * @throws EngineException
     */
    public function removeColumn(Column $column): void
    {
        if (!$this->columns->contains($column)) {
            throw new EngineException(sprintf('No column named %s found in %s %s.', $column->getName(), get_class($this), $this->getName()));
        }

        $this->columns->remove($column);

        if ($this instanceof Table) {
            $i = 1;
            foreach ($this->columns as $column) {
                $column->setPosition($i);
                $i++;
            }

            // @FIXME: also remove indexes on this column?
        }
    }

    public function removeColumnByName(string $name): void
    {
        $column = $this->getColumn($name);
        if (null === $column) {
            throw new EngineException(sprintf('No column named %s found in %s %s.', $name, get_class($this), $this->getName()));
        }

        $this->removeColumn($column);
    }
}
