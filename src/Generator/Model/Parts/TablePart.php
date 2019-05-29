<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Table;

/**
 * Trait TablePart
 *
 * @author Cristiano Cinotti
 */
trait TablePart
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @param Table $table
     */
    public function setTable(?Table $table): void
    {
        $this->table = $table;
    }

    /**
     * @return Table
     */
    public function getTable(): ?Table
    {
        return $this->table;
    }
}
