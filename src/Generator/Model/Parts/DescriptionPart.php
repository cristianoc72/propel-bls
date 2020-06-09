<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

/**
 * Trait DescriptionPart
 *
 * @author Cristiano Cinotti
 */
trait DescriptionPart
{
    private string $description;

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Returns whether or not the entity has a description.
     *
     * @return bool
     */
    public function hasDescription(): bool
    {
        return !empty($this->description);
    }
}
