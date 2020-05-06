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

use gossi\codegen\model\PhpConstant;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;

/**
 * Adds all field column constants.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class ColConstants extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        foreach ($this->getEntity()->getFields() as $field) {
            $constant = new PhpConstant($field->getConstantName());
            $constant->setDescription("The qualified name for the {$field->getName()} field.");
            $constant->setValue($this->getEntity()->getFullName() . '.' .$field->getName());

            $this->getDefinition()->setConstant($constant);
        }
    }
}
