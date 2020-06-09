<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\Map;
use phootwork\lang\Text;
use Propel\Generator\Model\Parts\TablePart;
use Propel\Generator\Model\Parts\ColumnsPart;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SuperordinatePart;
use Propel\Generator\Model\Parts\VendorPart;

/**
 * Information about indices of a table.
 *
 * @author Jason van Zyl <vanzyl@apache.org>
 * @author Daniel Rall <dlr@finemaltcoding.com>
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class Index
{
    use NamePart, TablePart, ColumnsPart, SuperordinatePart, VendorPart;

    /**
     * @var bool
     */
    protected bool $autoNaming = false;

    /**
     * @var Map Map of `columnname => size` to use for indexes creation.
     */
    protected Map $columnSizes;

    /**
     * Creates a new Index instance.
     *
     * @param string $name Name of the index
     */
    public function __construct(string $name = null)
    {
        $this->initColumns();
        $this->initVendor();
        $this->columnSizes = new Map();

        if (null !== $name) {
            $this->setName($name);
        }
    }

    /**
     * @inheritdoc
     *
     * @return Table
     */
    public function getSuperordinate(): Table
    {
        return $this->getTable();
    }

    /**
     * Returns the uniqueness of this index.
     *
     * @return boolean
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * Returns the index name.
     *
     * @return Text
     */
    public function getName(): Text
    {
        $this->doNaming();

        if ($this->table && $database = $this->table->getDatabase()) {
            return $this->name->substring(0, $database->getPlatform()->getMaxColumnNameLength());
        }

        return $this->name;
    }

    protected function doNaming(): void
    {
        if (!$this->name || $this->autoNaming) {
            $newName = sprintf('%s_', $this instanceof Unique ? 'u' : 'i');

            if (!$this->columns->isEmpty()) {
                $hash[0] = '';
                $hash[1] = '';
                $this->columns->each(function (Column $element) use ($hash) {
                    $hash[0] .= $element->getName() . ', ';
                    $hash[1] .= $element->getSize() . ', ';
                });
                $hash = array_map(function ($element) {
                    return substr($element, 0, -2);
                }, $hash);

                $newName .= substr(md5(strtolower(implode(':', $hash))), 0, 6);
            } else {
                $newName .= 'no_columns';
            }

            if ($this->table) {
                $newName = $this->getTable()->getTableName() . '_' . $newName;
            }

            $this->name = $newName;
            $this->autoNaming = true;
        }
    }

    /**
     * Returns whether or not this index has a given column at a given position.
     *
     * @param  integer $pos             Position in the column list
     * @param  string  $name            Column name
     * @param  integer $size            Optional size check
     * @return boolean
     */
    public function hasColumnAtPosition(int $pos, string $name, int $size = null): bool
    {
        $columnsArray = $this->getColumns()->toArray();

        if (!isset($columnsArray[$pos])) {
            return false;
        }

        /** @var Column $column */
        $column = $columnsArray[$pos];

        if ($column->getName() !== $name) {
            return false;
        }

        if ($column->getSize() != $size) {
            return false;
        }

        return true;
    }

    public function getColumnSizes(): Map
    {
        return $this->columnSizes;
    }
}
