<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\Query;

use gossi\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Model\CrossRelation;
use Propel\Generator\Model\NamingTool;

/**
 * Adds all filterBy$relationName methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByCrossRelationMethods extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
        $this->getDefinition()->declareUse('Propel\Runtime\Exception\PropelException');

        foreach ($this->getEntity()->getCrossRelations() as $crossRelation) {
            $this->addFilterByCrossRelation($crossRelation);
        }
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param CrossRelation $crossRelation
     */
    protected function addFilterByCrossRelation(CrossRelation $crossRelation)
    {
        $relationName = $this->getRefRelationPhpName($crossRelation->getIncomingRelation(), true);

        foreach ($crossRelation->getRelations() as $relation) {
            $queryClass = $this->getQueryClassName();
            $foreignEntity = $relation->getForeignEntity();
            $fkPhpName = $foreignEntity->getFullName();
            $relName = $this->getRelationPhpName($relation, false);
            $objectName = '$' . NamingTool::toCamelCase($foreignEntity->getName());

            $description = "Filter the query by a related $fkPhpName object
using the {$relation->getEntity()->getName()} entity as cross reference";

            $body = "
return \$this
    ->use{$relationName}Query()
    ->filterBy{$relName}($objectName, \$comparison)
    ->endUse();
";

            $methodName = "filterBy$relName";
            $variableParameter = new PhpParameter(NamingTool::toCamelCase($foreignEntity->getName()));

//            if ($relation->isComposite()) {
            $variableParameter->setType('\\'.$fkPhpName);
            $variableParameter->setTypeDescription("The related object to use as filter");
//            } else {
//                $variableParameter->setType("$fkPhpName|ObjectCollection");
//                $variableParameter->setTypeDescription("The related object(s) to use as filter");
//            }

            $this->addMethod($methodName)
                ->addParameter($variableParameter)
                ->addSimpleDescParameter(
                    'comparison',
                    'string',
                    'Operator to use for the column comparison, defaults to Criteria::EQUAL',
                    null
                )
                ->setDescription($description)
                ->setType("\$this|" . $queryClass)
                ->setTypeDescription("The current query, for fluid interface")
                ->setBody($body);
        }
    }
}
