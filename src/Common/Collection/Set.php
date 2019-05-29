<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Collection;

use phootwork\collection\Set as BaseSet;

class Set extends BaseSet
{
    use CollectionTrait;

    /**
     * @param mixed $element
     *
     * @return void
     */
    public function add($element): void
    {
        $this->checkClass($element);
        parent::add($element);
    }
}
