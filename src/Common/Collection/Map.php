<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Collection;

use phootwork\collection\Map as BaseMap;

class Map extends BaseMap
{
    use CollectionTrait;

    /**
     * @param $key
     * @param mixed $element
     *
     * @return void
     */
    public function set($key, $element): void
    {
        $this->checkClass($element);
        parent::set($key, $element);
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (null !== $default) {
            $this->checkClass($default);
        }

        return parent::get($key, $default);
    }
}
