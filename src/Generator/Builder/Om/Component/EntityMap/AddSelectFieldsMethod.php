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
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;

/**
 * Adds addSelectFields method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class AddSelectFieldsMethod extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $this->getDefinition()->declareUse('Propel\Runtime\ActiveQuery\Criteria');

        $body = "
if (null === \$alias) {";

        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                $body .= "
    \$criteria->addSelectField({$field->getFQConstantName()});";
            }
        }

        $body .= "
} else {";
        foreach ($this->getEntity()->getFields() as $field) {
            if (!$field->isLazyLoad()) {
                $body .= "
    \$criteria->addSelectField(\$alias . '." . $field->getName() . "');";
            }
        }
        $body .= "
}
";

        $this->addMethod('addSelectFields')
            ->addSimpleParameter('criteria', 'Criteria')
            ->addSimpleParameter('alias', 'string', null)
            ->setBody($body);
    }
}
