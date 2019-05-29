<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use Propel\Generator\Model\Database;

/**
 * Trait DatabasePart
 *
 * @author Cristiano Cinotti
 */
trait DatabasePart
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @param Database $database
     *
     * @return void
     */
    public function setDatabase(Database $database): void
    {
        $this->database = $database;
    }

    /**
     * Returns the entity this behavior is applied to if behavior is applied to
     * a database element.
     *
     * @return Database
     */
    public function getDatabase(): ?Database
    {
        return $this->database;
    }
}
