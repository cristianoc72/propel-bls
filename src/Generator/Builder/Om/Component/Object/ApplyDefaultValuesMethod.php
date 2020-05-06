<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\Column;

class ApplyDefaultValuesMethod extends BuildComponent
{
    use SimpleTemplateTrait;

    public function process(): void
    {
        $this
            ->addMethod('applyDefaultValues')
            ->setMultilineDescription([
                'Applies default values to this object.',
                'This method should be called from the object\'s constructor (or',
                'equivalent initialization method).',
                '@see __construct()'
            ])
            ->setBody($this->renderTemplate([
                'columns' => $this->getTable()->getColumns()->findAll(function(Column $element): ?Column {
                    if ($element->hasDefaultValue() && !$element->getDefaultValue()->isExpression()) {
                        return $element;
                    }

                    return null;
                }),
                'expressionColumns' => $this->getTable()->getColumns()->findAll(function(Column $element): ?Column {
                    if ($element->hasDefaultValue() && $element->getDefaultValue()->isExpression()) {
                        return $element;
                    }

                    return null;
                }),
                'dateTimeClass' => $this->getBuilder()->getBuildProperty('generator.dateTime.dateTimeClass')
            ]))
        ;

        $this->addConstructorBody('$this->applyDefaultValues();');
    }
}
