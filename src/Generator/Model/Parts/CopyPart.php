<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Parts;

use DeepCopy\DeepCopy;

/**
 * Methods to perform a deep copy of this object.
 *
 * @author Cristiano Cinotti
 */
trait CopyPart
{
    private DeepCopy $copier;

    /**
     * Return the cloned object
     *
     * @return array|\DateTimeInterface|\DateTimeZone|mixed|object|resource
     */
    public function copy()
    {
        return $this->getCopier()->copy($this);
    }

    protected function getCopier(): DeepCopy
    {
        if (!isset($this->copier)) {
            $this->copier = new DeepCopy();
        }

        return $this->copier;
    }
}
