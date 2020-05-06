<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use cristianoc72\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

class Constants extends BuildComponent
{
    use NamingTrait;

    public function process(): void
    {
        $this->getDefinition()
            ->setConstant(PhpConstant::create('TABLE_MAP')
                ->setValue($this->getTableMapClassName(true))
                ->setDescription('TableMap class name')
            )
        ;
    }
}
