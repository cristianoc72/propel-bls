<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;

use cristianoc72\codegen\model\PhpMethod;
use gossi\docblock\tags\ThrowsTag;
use phootwork\json\JsonException;
use phootwork\lang\ArrayObject;
use Propel\Common\Exception\SetColumnConverterException;
use Propel\Common\Util\SetColumnConverter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Exception\PropelException;

class ColumnMutators extends BuildComponent
{
    use NamingTrait, SimpleTemplateTrait, ForeignKeyTrait;

    /**
     * @throws \ReflectionException
     * @throws \Twig\Error\Error
     */
    public function process(): void
    {
        /** @var Column $column */
        foreach ($this->getTable()->getColumns() as $column) {
            $columnName = $column->getName()->toLowerCase()->toString();
            $constantName = $column->getConstantName();
            $context = ['columnName' => $columnName, 'constantName' => $constantName];

            $method = $this->addMethod("set{$column->getMethodName()}",
                $this->getTable()->isReadOnly() ? 'protected' : $column->getMutatorVisibility())
                ->setType("self", "\$this|{$this->getObjectClassName(true)} The current object (for fluent API support)")
                ->setMultilineDescription([
                    "Set the value of [$columnName] column.",
                    $column->getDescription()
                ])
            ;

            if ($column->isLazyLoad()) {
                $method->setBody("
        // explicitly set the is-loaded flag to true for this lazy load col;
        // it doesn't matter if the value is actually set or not (logic below) as
        // any attempt to set the value means that no db lookup should be performed
        // when the get{$column->getMethodName()}() method is called.
        \$this->{$columnName}_isLoaded = true;
"
                );
            }

            if (PropelTypes::OBJECT === $column->getType()) {
                $method->addSimpleParameter('v', 'object')
                    ->appendToBody($this->renderTemplate($context, 'object_mutator.twig'));
            } elseif ($column->isLobType()) {
                $method->addSimpleParameter('v', 'mixed')
                    ->appendToBody($this->renderTemplate($context, 'lob_mutator'));
            } elseif (
                PropelTypes::DATE === $column->getType()
                || PropelTypes::TIME === $column->getType()
                || PropelTypes::TIMESTAMP === $column->getType()
            ) {
                $this->addTemporalMutator($method, $column);
            } elseif (PropelTypes::PHP_ARRAY === $column->getType()) {
                $method->addSimpleParameter('v')
                    ->appendToBody($this->renderTemplate($context, 'array_mutator'));
                if ($column->isNamePlural()) {
                    $this->addArrayElementMethod($column);
                    $this->removeArrayElementMethod($column);
                }
            } elseif (PropelTypes::JSON === $column->getType()) {
                $method->addSimpleParameter('v', 'string|array|object')
                    ->setBody($this->renderTemplate($context, 'json_mutator'))
                    ->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(JsonException::class)))
                ;
                $this->useClass(JsonException::class);
            } elseif ($column->isEnumType()) {
                $context['tableMapName'] = $this->useClass($this->getTableMapClassName(true));
                $method->addSimpleParameter('v', 'string')->setBody($this->renderTemplate($context, 'enum_mutator'))
                    ->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)));
                $this->useClass(PropelException::class);
                $this->useClass(SetColumnConverter::class);
                $this->useClass(SetColumnConverterException::class);
            } elseif ($column->isSetType()) {
                $context['tableMapName'] = $this->useClass($this->getTableMapClassName(true));
                $method->addSimpleParameter('v', 'array')->setBody($this->renderTemplate($context, 'set_mutator'))
                    ->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)));
                $this->useClass(PropelException::class);
                if ($column->isNamePlural()) {
                    $this->addArrayElementMethod($column);
                    $this->removeArrayElementMethod($column);
                }
            } elseif ($column->isBooleanType()) {
                $this->addBooleanMutator($method, $column);
            } else {
                $this->addDefaultMutator($method, $column);
            }

            $this->finalizeColumnMutator($method, $column);
        }
    }

    /**
     * @param PhpMethod $method
     * @param Column $column
     *
     * @throws \ReflectionException
     * @throws \Twig\Error\Error
     */
    private function addTemporalMutator(PhpMethod $method, Column $column): void
    {
        $context = [
            'columnName' => $column->getName()->toLowerCase()->toString(),
            'dateTimeClass' => $this->getBuilder()->getBuildProperty('generator.dateTime.dateTimeClass'),
            'defaultValue' => $column->getDefaultValueString(),
            'column' => $column
        ];

        switch ($column->getType()) {
            case PropelTypes::DATE:
                $context['format'] = $column->hasDefaultValue() ? $this->getPlatform()->getDateFormatter() : 'Y-m-d';
                break;
            case PropelTypes::TIME:
                $context['format'] = $column->hasDefaultValue() ? $this->getPlatform()->getTimeFormatter() : 'H:i:s.u';
                break;
            default:
                $context['format'] = $column->hasDefaultValue() ? $this->getPlatform()->getTimestampFormatter() : 'Y-m-d H:i:s.u';
        }

        $method
            ->setMultilineDescription([
                "Sets the value of [{$context['columnName']}] column to a normalized version of the date/time value specified.",
                $column->getDescription()
            ])
            ->addSimpleDescParameter('v', 'string', "integer (timestamp), or \DateTimeInterface value.\nEmpty strings are treated as NULL.")
            ->appendToBody($this->renderTemplate($context, 'temporal_mutator'))
        ;
    }

    private function addArrayElementMethod(Column $column): void
    {
        $method = $this->addMethod("add{$column->getSingularName()->toStudlyCase()}", $column->getAccessorVisibility())
            ->setDescription("Adds a value to the [{$column->getName()->toLowerCase()}] "
                . (($column->getType() === PropelTypes::PHP_ARRAY) ? 'array' : 'set') . " column value.")
            ->setType('self', "\$this|{$this->getObjectClassName(true)} The current object (for fluent API support)")
            ->addSimpleParameter('value')
            ->setBody("
\$currentArray = \$this->get{$column->getMethodName()}(" . ($column->isLazyLoad() ? "\$con" : "") . ");
\$currentArray [] = \$value;
\$this->set{$column->getMethodName()}(\$currentArray);

return \$this;
"
            )
        ;

        if ($column->isLazyLoad()) {
            $method->addSimpleDescParameter('con', 'ConnectionInterface', "An optional ConnectionInterface connection to use for fetching this lazy-loaded column.", null);
        }
    }

    private function removeArrayElementMethod(Column $column): void
    {
        $this->useClass(ArrayObject::class);

        $method = $this->addMethod("remove{$column->getSingularName()->toStudlyCase()}", $column->getAccessorVisibility())
            ->setDescription("Removes a value from the [{$column->getName()->toLowerCase()}] "
                . (($column->getType() === PropelTypes::PHP_ARRAY) ? 'array' : 'set') . " column value.")
            ->setType('self', "\$this|{$this->getObjectClassName(true)} The current object (for fluent API support)")
            ->addSimpleParameter('value')
            ->setBody("
\$array = new ArrayObject(\$this->get{$column->getMethodName()}(" . ($column->isLazyLoad() ? "\$con" : "") ."));
\$array->remove(\$value);
\$this->set{$column->getMethodName()}(\$array->toArray());

return \$this;
"
            )
        ;

        if ($column->isLazyLoad()) {
            $method->addSimpleDescParameter('con', 'ConnectionInterface', "An optional ConnectionInterface connection to use for fetching this lazy-loaded column.", null);
        }
    }

    private function addBooleanMutator(PhpMethod $method, Column $column): void
    {
        $columnName = $column->getName()->toLowerCase()->toString();
        $method
            ->setMultilineDescription([
                "Sets the value of the [$columnName] column.",
                "Non-boolean arguments are converted using the following rules:",
                "  * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true",
                "  * 0, '0', 'false', 'off', and 'no'  are converted to boolean false",
                "Check on string values is case insensitive (so 'FaLsE' is seen as 'false').",
                $column->getDescription()
            ])
            ->addSimpleParameter('v', 'boolean|integer|string')
            ->appendToBody("
if (\$v !== null) {
    if (is_string(\$v)) {
        \$v = in_array(strtolower(\$v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
    } else {
        \$v = (boolean) \$v;
    }
}

if (\$this->$columnName !== \$v) {
    \$this->$columnName = \$v;
    \$this->modifiedColumns->set({$column->getConstantName()}, true);
}
"
            )
        ;
    }

    private function addDefaultMutator(PhpMethod $method, Column $column)
    {
        $columnName = $column->getName()->toLowerCase()->toString();
        $body = '';

        // Perform type-casting to ensure that we can use type-sensitive
        // checking in mutators.
        if ($column->isPhpPrimitiveType()) {
            $body .= "
if (\$v !== null) {
    \$v = ({$column->getPhpType()}) \$v;
}
";
        }

        $body .= "
if (\$this->$columnName !== \$v) {
    \$this->$columnName = \$v;
    \$this->modifiedColumns->set({$column->getConstantName()}, true);
}
";
        $method->addSimpleParameter('v')->appendToBody($body);
    }

    private function finalizeColumnMutator(PhpMethod $method, Column $column): void
    {
        $table = $this->getTable();
        $body = '';

        if ($column->isForeignKey()) {
            foreach ($column->getForeignKeys() as $fk) {
                $tblFK =  $table->getDatabase()->getTableByName($fk->getForeignTableName());
                $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($column->getName()));
                if (!$colFK) {
                    continue;
                }
                $varName = $this->getForeignKeyVarName($fk);

                $body .= "
if (\$this->$varName !== null && \$this->{$varName}->get{$colFK->getPhpName()}() !== \$v) {
    \$this->$varName = null;
}
";
            } // foreach fk
        } /* if col is foreign key */

        foreach ($column->getReferrers() as $refFK) {

            $tblFK = $this->getTable()->getDatabase()->getTableByName($refFK->getForeignTableName());

            if ( $tblFK->getName()->toString() !== $table->getName()->toString() ) {
                foreach ($column->getForeignKeys() as $fk) {
                    $tblFK = $table->getDatabase()->getTableByName($fk->getForeignTableName());
                    $colFK = $tblFK->getColumn($fk->getMappedForeignColumn($column->getName()));
                    if ($refFK->isLocalPrimaryKey()) {
                        $varName = $this->getPKRefForeignKeyVarName($refFK);
                        $body .= "
// update associated {$tblFK->getPhpName()}
if (\$this->$varName !== null) {
    \$this->{$varName}->set{$colFK->getPhpName()}(\$v);
}
";
                    } else {
                        $collName = $this->getRefForeignKeyCollVarName($refFK);
                        $body .= "

// update associated {$tblFK->getPhpName()}
if (\$this->$collName !== null) {
    foreach (\$this->$collName as \$referrerObject) {
            \$referrerObject->set{$colFK->getPhpName()}(\$v);
        }
    }
";
                    } // if (isLocalPrimaryKey
                } // foreach col->getPrimaryKeys()
            } // if tablFk != table
        }

        $body .= "
        
return \$this;
";
        $method->appendToBody($body);
    }
}
