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
use Propel\Generator\Model\CrossForeignKey;

class CrossFKProperties extends BuildComponent
{
    use CrossForeignKeyTrait;

    public function process(): void
    {
        foreach ($this->getTable()->getCrossForeignKeys() as $crossForeignKey) {
            $this->addCrossFKProperties($crossForeignKey);
            $this->addCrossFKScheduledForDeletion($crossForeignKey);
        }
    }

    private function addCrossFKProperties(CrossForeignKey $crossForeignKey): void
    {
        $ForeignKey = $crossForeignKey->getForeignKeys()[0];
        $className = $ForeignKey->getForeignEntity()->getFullName();
        $varName = $this->getCrossForeignKeysVarName($crossForeignKey)->toString();

        $this->addProperty($varName, "ObjectCollection|\\{$className}[]")
            ->setTypeDescription("Cross Collection to store aggregation of \\$className objects.");

        if ($crossForeignKey->isPolymorphic()) {
            $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCombinationCollection');
            $this->addConstructorBody("\$this->$varName = new ObjectCombinationCollection();");
        } else {
            $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
            $this->addConstructorBody("\$this->$varName = new ObjectCollection();
\$this->{$varName}->setModel('$className');");
        }
    }

    private function addCrossFKScheduledForDeletion(CrossForeignKey $crossForeignKey): void
    {
        $name = $this->getCrossScheduledForDeletionVarName($crossForeignKey);
        if (1 < count($crossForeignKey->getForeignKeys()) || $crossForeignKey->getUnclassifiedPrimaryKeys()) {
            list($names) = $this->getCrossForeignKeysInformation($crossForeignKey);
            $this->addProperty($name, 'ObjectCombinationCollection', null, "Cross CombinationCollection to store aggregation of $names combinations.");
        } else {
            $refFK = $crossForeignKey->getIncomingForeignKey();
            if (!$refFK->isLocalPrimaryKey()) {
                $this->addProperty($name, 'ObjectCollection', null, "An array of objects scheduled for deletion.");
            }
        }
    }
}