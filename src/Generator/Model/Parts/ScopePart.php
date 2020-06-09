<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

/**
 * Trait ScopePart
 *
 * @author Thomas Gossmann
 */
trait ScopePart
{
    use SuperordinatePart;

    private string $scope = '';

    /**
     * Sets scope
     *
     * @param string $scope
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * Returns scope
     *
     * @return string
     */
    public function getScope(): string
    {
        $scope = $this->scope;

        if ('' === $scope && $this->getSuperordinate() && method_exists($this->getSuperordinate(), 'getScope')) {
            $scope = $this->getSuperordinate()->getScope();
        }

        return $scope;
    }
}
