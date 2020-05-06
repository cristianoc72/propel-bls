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

/**
 * Adds filterByPrimaryKeys method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FilterByPrimaryKeysMethod extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $this->getDefinition()->declareUse($this->getEntityMapClassName(true));
        $this->addFilterByPrimaryKey();
    }

    /**
     * Adds the filterByPrimaryKey method for this object.
     */
    protected function addFilterByPrimaryKey()
    {
        if (!$this->getEntity()->hasPrimaryKey()) {
            return;
        }

        $body = '';

        $pks = $this->getEntity()->getPrimaryKey();
        if (1 === count($pks)) {
            // simple primary key
            $field = $pks[0];
            $const = $field->getFQConstantName();
            $body .= "

    return \$this->addUsingAlias($const, \$keys, Criteria::IN);";
        } else {
            // composite primary key
            $body .= "
    if (empty(\$keys)) {
        return \$this->add(null, '1<>1', Criteria::CUSTOM);
    }
    foreach (\$keys as \$key) {";
            $i = 0;
            foreach ($pks as $field) {
                $const = $field->getFQConstantName();
                $body .= "
    \$cton$i = \$this->getNewCriterion($const, \$key[$i], Criteria::EQUAL);";
                if ($i > 0) {
                    $body .= "
    \$cton0->addAnd(\$cton$i);";
                }
                $i++;
            }
            $body .= "
    \$this->addOr(\$cton0);
}";
        }

        $body .= "

return \$this;";

        $this->addMethod('filterByPrimaryKeys')
            ->addSimpleDescParameter('keys', 'array', 'Primary keys to use for the query')
            ->setBody($body)
            ->setType("\$this|{$this->getQueryClassName()}")
            ->setTypeDescription('The current query, for fluid interface')
            ->setDescription('Filter the query by primary key.')
        ;
    }
}
