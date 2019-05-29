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
    /**
     * @var Text
     */
    private $name;

    /**
     * @var Text
     */
    private $phpName;

    /**
     * Returns the class name without namespace.
     *
     * @return Text
     */
    public function getName(): Text
    {
        if (null === $this->name) {
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
        if (null === $this->phpName) {
            $this->phpName = $this->name->toStudlyCase();
        }

        return $this->phpName;
    }
}
