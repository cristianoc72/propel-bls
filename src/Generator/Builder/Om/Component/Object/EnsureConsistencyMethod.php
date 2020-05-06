<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use gossi\docblock\tags\ThrowsTag;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Runtime\Exception\PropelException;

class EnsureConsistencyMethod extends BuildComponent
{
    use ForeignKeyTrait;

    public function process(): void
    {
        $method = $this->addMethod('ensureConsistency')
            ->setType('void')
            ->setMultilineDescription([
                'Checks and repairs the internal consistency of the object.',
                '',
                'This method is executed after an already-instantiated object is re-hydrated',
                'from the database.  It exists to check any foreign keys to make sure that',
                'the objects related to the current object are correct based on foreign key.',
                '',
                'You can override this method in the stub class, but you should always invoke',
                'the base method from the overridden method (i.e. parent::ensureConsistency()),',
                'in case your model changes.'
            ])
            ;

        $body = '';

        /** @var Column $column */
        foreach ($this->getTable()->getColumns()->toArray() as $column) {

            $columnName = $column->getName()->toLowerCase()->toString();

            if ($column->isForeignKey()) {
                /** @var ForeignKey $fk */
                foreach ($column->getForeignKeys()->toArray() as $fk) {

                    $tblFK = $this->getTable()->getDatabase()->getTableByName($fk->getForeignTableName());
                    $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($column->getName()->toString()));
                    $varName = $this->getForeignKeyVarName($fk);

                    if (!$colFK) {
                        continue;
                    }

                    $body .= "
        if (\$this->".$varName." !== null && \$this->$columnName !== \$this->".$varName."->get{$colFK->getMethodName()}()) {
            \$this->$varName = null;
        }";
                } // foreach
            } /* if col is foreign key */
        } // foreach

        $method->setBody($body)
            ->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)));
    }
}
