<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component;

use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Model\Behavior;

trait ComponentTrait
{
    /**
     * @param string          $className fqcn or relative to current '__NAMESPACE__\\Component\\' of $this unless prefixed with \\.
     * @param AbstractBuilder $builder
     * @param Behavior        $behavior
     *
     * @throws \ReflectionException
     */
    protected function applyComponent(string $className, AbstractBuilder $builder = null, Behavior $behavior = null): void
    {
        if ('\\' !== $className[0]) {
            $reflection = new \ReflectionClass($this);
            $namespace = $reflection->getNamespaceName();
            $className = $namespace . '\\Component\\' . $className;
        }

        if (null === $builder && method_exists($this, 'getBuilder')) {
            $builder = $this->getBuilder();
        }

        if (null == $behavior && $this instanceof Behavior) {
            $behavior = $this;
        }

        /** @var BuildComponent $instance */
        $instance = new $className($builder, $behavior);

        $args = func_get_args();
        array_shift($args); //shift $className away
        array_shift($args); //shift $builder away
        array_shift($args); //shift $behavior away

        return call_user_func_array([$instance, 'process'], $args);
    }
}
