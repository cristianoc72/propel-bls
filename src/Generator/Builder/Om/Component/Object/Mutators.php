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

class Mutators extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('setNew')
            ->setType('void')
            ->setMultilineDescription([
                'Setter for the isNew attribute.  This method will be called',
                'by Propel-generated children and objects.'
            ])
            ->addParameter(PhpParameter::create('new')->setType('bool'))
            ->setBody('$this->new = $new;')
        ;
        $this->addMethod('setDeleted')
            ->setType('void')
            ->setDescription('Specify whether this object has been deleted.')
            ->addParameter(PhpParameter::create('del')->setType('bool', 'The deleted state of this object.'))
            ->setBody('$this->deleted = $del;')
        ;
        $this->addMethod('resetModified')
            ->setType('void')
            ->addParameter(PhpParameter::create('column')
                ->setType('string', 'If supplied, only the specified column is reset.')
                ->setValue(null)
            )
            ->setBody("
if (null !== \$column) {
    if (\$this->modifiedColumns->has(\$column)) {
        \$this->modifiedColumns->remove(\$column)
    }
} else {
    \$this->modifiedColumns->clear();
}
")
        ;
        $this->addMethod('setVirtualColumn')
            ->setType('self', 'The current object, for fluid interface')
            ->setDescription('Set the value of a virtual column in this object')
            ->addParameter(PhpParameter::create('name')->setType('string', 'The virtual column name'))
            ->addParameter(PhpParameter::create('value')->setTypeDescription('The value to give to the virtual column'))
            ->setBody("
\$this->virtualColumns->set(\$name, \$value);

return \$this;
")
        ;
    }
}
