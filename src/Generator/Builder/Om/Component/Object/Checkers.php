<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use cristianoc72\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Model\Column;

class Checkers extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('isModified')
            ->setType('bool')
            ->setDescription('Returns whether the object has been modified.')
            ->setBody('return !$this->modifiedColumns->isEmpty();')
        ;
        $this->addMethod('isColumnModified')
            ->setType('bool', 'True if $col has been modified.')
            ->setDescription('Has specified column been modified?')
            ->addParameter(PhpParameter::create('col')
                ->setType('string')
                ->setDescription('column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID'))
            ->setBody('return (bool) $this->modifiedColumns->get($col, false);')
        ;
        $this->addMethod('isNew')
            ->setType('bool', 'true, if the object has never been persisted.')
            ->setMultilineDescription([
                'Returns whether the object has ever been saved.  This will',
                'be false, if the object was retrieved from storage or was created',
                'and then saved.'
            ])
            ->setBody('return $this->new;')
        ;
        $this->addMethod('isDeleted')
            ->setType('bool', 'The deleted state of this object.')
            ->setDescription('Whether this object has been deleted.')
            ->setBody('return $this->deleted;')
        ;
        $this->addMethod('hasVirtualColumn')
            ->setType('bool')
            ->setDescription('Checks the existence of a virtual column in this object')
            ->addParameter(PhpParameter::create('name')->setType('string', 'The virtual column name'))
            ->setBody('return $this->virtualColumns->has($name);')
        ;

        $this->addHasOnlyDefaultValues();
    }

    private function addHasOnlyDefaultValues(): void
    {
        $table = $this->getTable();
        $colsWithDefaults = $table->getColumns()->findAll(function(Column $element): Column {
            if ($element->hasDefaultValue() && !$element->getDefaultValue()->isExpression()) {
                return $element;
            }
        });

        foreach ($colsWithDefaults->toArray() as $column) {
            /** @var Column $column */
            $columnName = $column->getName()->toLowerCase()->toString();
            $accessor = "\$this->$columnName";
            if ($column->isTemporalType()) {
                $fmt = $this->getBuilder()->getTemporalFormatter($column);
                $accessor = "\$this->$columnName && \$this->{$columnName}->format('$fmt')";
            }
        }

        $this->addMethod('hasOnlyDefaultValues')
            ->setType('bool', "Whether the columns in this object are only been set with default values.")
            ->setMultilineDescription([
                "Indicates whether the columns in this object are only set to default values.",
                "This method can be used in conjunction with isModified() to indicate whether an object is both",
                "modified _and_ has some values set which are non-default."
            ])
            ->setBody("
if ($accessor !== {$column->getDefaultValueString()}) {
    return false;
}

// otherwise, everything was equal, so return TRUE
return true;
"
                );
    }
}
