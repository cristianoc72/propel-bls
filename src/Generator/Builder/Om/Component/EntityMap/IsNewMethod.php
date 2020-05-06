<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds the isNew method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class IsNewMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $entityClassName = $this->getObjectClassName();

        $body = <<<EOF
return \$this->getConfiguration()->getSession()->isNew(\$entity);
\$id = spl_object_hash(\$entity);
if (\$entity instanceof \Propel\Runtime\EntityProxyInterface) {
    if (isset(\$this->deletedIds[\$id])) {
        //it has been deleted after receiving from the database,
        return true;
    }

    return false;
} else {
    if (isset(\$this->committedIds[\$id])) {
        //it has been committed
        return false;
    }

    return true;
}
EOF;

        $this->addMethod('isNew')
            ->addSimpleParameter('entity', $entityClassName)
            ->setType('boolean')
            ->setDescription("Returns true if this is a new (not yet saved/committed) instance.")
            ->setBody($body);
    }
}
