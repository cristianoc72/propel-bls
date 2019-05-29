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

    /** @var string */
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
            $this->namespace = $namespace->join('\\')->toString();
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
        if (null !== $namespace) {
            $namespace = rtrim($namespace, '\\');
        }
        $this->namespace = $namespace;
    }

    /**
     * Returns the namespace
     *
     * @return string
     */
    public function getNamespace(): string
    {
        $namespace = $this->namespace;

        if (null === $namespace && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getNamespace')) {
            $namespace = $this->getSuperordinate()->getNamespace();
        }

        if (null === $namespace) {
            $namespace = '';
        }

        return $namespace;
    }

    /**
     * Returns the class name with namespace.
     *
     * @return string
     */
    public function getFullName(): string
    {
        $name = $this->getName();
        $namespace = $this->getNamespace();

        if ($namespace) {
            return $namespace . '\\' . $name;
        } else {
            return $name->toString();
        }
    }
}
