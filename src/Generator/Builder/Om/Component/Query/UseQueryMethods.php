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

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Model\Relation;

/**
 * Adds all use{$relationName}Query methods.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class UseQueryMethods extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
//        $this->getDefinition()->declareUse('Propel\Runtime\Collection\ObjectCollection');
//        $this->getDefinition()->declareUse('Propel\Runtime\Exception\PropelException');

        foreach ($this->getEntity()->getRelations() as $relation) {
            $this->addUseRelationMethod($relation);
        }

        foreach ($this->getEntity()->getReferrers() as $relation) {
            $this->addUseRefRelationMethod($relation);
        }
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param Relation $relation
     */
    protected function addUseRefRelationMethod(Relation $relation)
    {
        $foreignEntity = $relation->getEntity();
        $relationName = $this->getRefRelationPhpName($relation, true);
        $queryClass = $this->getQueryClassNameForEntity($foreignEntity);
        $this->addUseQueryMethod($relationName, $queryClass, $relation);
    }

    /**
     * Adds the filterByFk method for this object.
     *
     * @param Relation $relation
     */
    protected function addUseRelationMethod(Relation $relation)
    {
        $foreignEntity = $relation->getForeignEntity();
        $relationName = $this->getRelationPhpName($relation);
        $queryClass = $this->getQueryClassNameForEntity($foreignEntity);

        $this->addUseQueryMethod($relationName, $queryClass, $relation);
    }

    protected function addUseQueryMethod($relationName, $queryClass, Relation $relation)
    {
        $methodName = "use{$relationName}Query";
        $relationVarName = lcfirst($relationName);

        $body = "
return \$this
    ->join" . $relationName . "(\$relationAlias, \$joinType)
    ->useQuery(\$relationAlias ? \$relationAlias : '$relationVarName');
";

        $joinType = $this->getJoinType($relation);

        $this->addMethod($methodName)
            ->addSimpleDescParameter('relationAlias', 'string', 'optional alias for the relation, to be used as main alias in the secondary query', null)
            ->addSimpleDescParameter('joinType', 'string', "Accepted values are null, 'left join', 'right join', 'inner join'", $joinType)
            ->setDescription("Use the $relationVarName relation " . $relation->getForeignEntity()->getName() . " object

@see useQuery()")
            ->setType($queryClass)
            ->setTypeDescription("A secondary query class using the current class as primary query")
            ->setBody($body);
    }
}
