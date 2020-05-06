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
use Propel\Generator\Builder\Om\Component\CrossForeignKeyTrait;
use Propel\Generator\Model\ForeignKey;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;

class RefFKProperties extends BuildComponent
{
    use CrossForeignKeyTrait;

    public function process(): void
    {
        $this->getTable()->getReferrers()->each(function (ForeignKey $element) {
            $this->addRefFkProperty($element);
            if (!$element->isLocalPrimaryKey()) {
                $this->addRefFKScheduledForDeletion($element);
            }
        });
    }

    private function addRefFkProperty(ForeignKey $refForeignKey): void
    {
        $className = $this->useClass($refForeignKey->getTable()->getFullName()->toString());

        if ($refForeignKey->isLocalPrimaryKey()) {
            $varName = $this->getPKRefForeignKeyVarName($refForeignKey)->toString();
            $this->addProperty($varName, $className)
                ->setTypeDescription("one-to-one related $className object. (referrer relation)");
        } else {
            $collection = $this->useClass(Collection::class);
            $varName = $this->getRefForeignKeyCollVarName($refForeignKey)->toString();
            $this->addProperty($varName, $collection)
                ->setTypeDescription("Collection of $className. (referrer relation)");

            $this->useClass(ObjectCollection::class);
            $this->addConstructorBody("\$this->$varName = new ObjectCollection();
\$this->{$varName}->setModel('$className');");
        }
    }

    private function addRefFKScheduledForDeletion(ForeignKey $refFK): void
    {
        $this->addProperty(
            $this->getForeignKeyVarName($refFK, true) . 'ScheduledForDeletion',
            'ObjectCollection',
            null,
            'An array of objects scheduled for deletion.')
        ;
    }
}
