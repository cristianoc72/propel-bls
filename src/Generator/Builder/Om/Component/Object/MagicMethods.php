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

class MagicMethods extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('__sleep')
            ->setType('array')
            ->setMultilineDescription([
                'Clean up internal collections prior to serializing',
                'Avoids recursive loops that turn into segmentation faults when serializing'
            ])
            ->setBody('
$this->clearAllReferences();

$cls = new \ReflectionClass($this);
$propertyNames = [];
$serializableProperties = array_diff($cls->getProperties(), $cls->getProperties(\ReflectionProperty::IS_STATIC));

foreach($serializableProperties as $property) {
    $propertyNames[] = $property->getName();
}

return $propertyNames;
'
            )
        ;
    }
}
