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

class Accessors extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('getModifiedColumns')
            ->setType('Set', 'A unique list of the modified column names for this object.')
            ->setDescription('Get the columns that have been modified in this object.')
            ->setBody('return $this->modifiedColumns->keys();')
        ;
        $this->addMethod('getVirtualColumns')
            ->setType('Map')
            ->setDescription('Get the Map of the virtual columns in this object')
            ->setBody('return $this->virtualColumns;')
        ;
        $this->addMethod('getVirtualColumn')
            ->setType('mixed')
            ->addParameter(PhpParameter::create('name')->setType('string', 'The virtual column name'))
            ->setBody("if (!\$this->virtualColumns->has(\$name)) {
    throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', \$name));
}

return \$this->virtualColumns->get(\$name);            
"
            )
        ;
    }
}
