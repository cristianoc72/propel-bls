<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use cristianoc72\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class EqualsMethod extends BuildComponent
{
    use NamingTrait;

    public function process(): void
    {
        $this->addMethod('equals')
            ->setType('bool', 'Whether equal to the object specified.')
            ->setMultilineDescription([
                "Compares this with another <code>{$this->getObjectClassName()}</code> instance.  If",
                "<code>obj</code> is an instance of <code>{$this->getObjectClassName()}</code>, delegates to",
                "<code>equals({$this->getObjectClassName()})</code>.  Otherwise, returns <code>false</code>."
            ])
            ->addParameter(PhpParameter::create('obj')->setDescription('The object to compare to.'))
            ->setBody("
if (!\$obj instanceof static) {
    return false;
}

if (\$this === \$obj) {
    return true;
}

if (null === \$this->getPrimaryKey() || null === \$obj->getPrimaryKey()) {
    return false;
}

return \$this->getPrimaryKey() === \$obj->getPrimaryKey();            
"
            )
        ;
    }
}
