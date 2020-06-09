<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use phootwork\lang\Text;

/**
 * Trait NamePart
 *
 * @author Thomas Gossmann
 */
trait NamePart
{
    private Text $name;
    private Text $phpName;

    /**
     * Returns the class name without namespace.
     *
     * @return Text
     */
    public function getName(): Text
    {
        if (!isset($this->name)) {
            $this->name = new Text('');
        }

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = new Text($name);
    }

    /**
     * Return the PhpName. If not specified
     * @return Text
     */
    public function getPhpName(): Text
    {
        if (!isset($this->phpName)) {
            $this->phpName = $this->name->toStudlyCase();
        }

        return $this->phpName;
    }

    /**
     * @param string $phpName
     */
    public function setPhpName(string $phpName): void
    {
        $this->phpName = new Text($phpName);
    }
}
