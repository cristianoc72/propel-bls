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
 * Trait NamespacePart
 *
 * @author Thomas Gossmann
 */
trait NamespacePart
{
    use NamePart;
    use SuperordinatePart;

    /** @var Text */
    private $namespace;

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $name = new Text($name);
        if ($name->contains('\\')) {
            $namespace = $name->split('\\');
            $this->name = new Text($namespace->pop());
            $this->namespace = $namespace->join('\\');
        } else {
            $this->name = $name;
        }
    }

    /**
     * Sets the namespace
     *
     * @param string $namespace
     */
    public function setNamespace(?string $namespace): void
    {
        $this->namespace = (new Text($namespace))->trimEnd('\\');
    }

    /**
     * Returns the namespace
     *
     * @return Text
     */
    public function getNamespace(): Text
    {
        $namespace = $this->namespace;

        if ($namespace->isEmpty() && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getNamespace')) {
            $namespace = $this->getSuperordinate()->getNamespace();
        }

        return $namespace;
    }

    /**
     * Returns the class name with namespace.
     *
     * @return Text
     */
    public function getFullName(): Text
    {
        return $this->getName()->prepend($this->getNamespace()->ensureEnd('\\'));
    }
}
