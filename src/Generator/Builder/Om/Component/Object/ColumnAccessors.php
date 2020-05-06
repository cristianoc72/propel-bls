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
use cristianoc72\codegen\model\PhpParameter;
use gossi\docblock\tags\ThrowsTag;
use phootwork\json\Json;
use phootwork\lang\Text;
use Propel\Common\Exception\SetColumnConverterException;
use Propel\Common\Util\SetColumnConverter;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\OraclePlatform;
use Propel\Generator\Platform\SqlsrvPlatform;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

class ColumnAccessors extends BuildComponent
{
    use NamingTrait, SimpleTemplateTrait;

    public function process(): void
    {
        $table = $this->getTable();

        /** @var Column $column */
        foreach ($table->getColumns()->toArray() as $column) {
            $columnName = $column->getName()->toLowerCase()->toString();

            $method = $this->addMethod("get{$column->getMethodName()->toString()}", $column->getAccessorVisibility())
                ->setMultilineDescription([
                    "Get the [$columnName] column value.",
                    $column->getDescription()
                ])
            ;

            if ($column->isLazyLoad()) {
                $method
                    ->addSimpleDescParameter('con', 'ConnectionInterface', null, 'Optional Connection object')
                    ->setBody("
if (!\$this->{$columnName}_isLoaded && \$this->$columnName === " . ($column->getDefaultValueString() ?? 'null') .
                        " && !\$this->isNew()) {
    \$this->load{$column->getMethodName()}(\$con);
}
")
                ;
                $this->useClass(ConnectionInterface::class);
                $this->addLazyLoaderMethod($column);
            }

            // if they're not using the DateTime class than we will generate "compatibility" accessor method
            if (PropelTypes::DATE === $column->getType()
                || PropelTypes::TIME === $column->getType()
                || PropelTypes::TIMESTAMP === $column->getType()
            ) {
                $this->addTemporalAccessor($method, $column);
            } elseif (PropelTypes::OBJECT === $column->getType()) {
                $this->addObjectAccessor($method, $column);
            } elseif (PropelTypes::PHP_ARRAY === $column->getType()) {
                $this->addArrayAccessor($method, $column);
                if ($column->isNamePlural()) {
                    $this->addHasArrayElement($column);
                }
            } elseif (PropelTypes::JSON === $column->getType()) {
                $this->addJsonAccessor($method, $column);
            } elseif ($column->isEnumType()) {
                $this->addEnumAccessor($method, $column);
            } elseif (PropelTypes::isSetType($column->getType())) {
                $this->addSetAccessor($method, $column);
                if ($column->isNamePlural()) {
                    $this->addHasArrayElement($column);
                }
            } elseif ($column->isBooleanType()) {
                $this->addBooleanAccessor($method, $column);
            } else {
                $method->appendToBody("return \$this->$columnName;");
            }
        }
    }

    private function addTemporalAccessor(PhpMethod $method, Column $column): void
    {
        $dateTimeClass = $this->getBuilder()->getBuildProperty('generator.dateTime.DateTimeClass');
        $columnName = $column->getName()->toLowerCase()->toString();

        $handleMysqlDate = false;
        if ($this->getPlatform() instanceof MysqlPlatform) {
            if ($column->getType() === PropelTypes::TIMESTAMP) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00 00:00:00';
            } elseif ($column->getType() === PropelTypes::DATE) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00';
            }
            // 00:00:00 is a valid time, so no need to check for that.
        }

        // Default date/time formatter strings are specified in propel config
        if ($column->getType() === PropelTypes::DATE) {
            $defaultfmt = $this->getBuilder()->getBuildProperty('generator.dateTime.defaultDateFormat');
        } elseif ($column->getType() === PropelTypes::TIME) {
            $defaultfmt = $this->getBuilder()->getBuildProperty('generator.dateTime.defaultTimeFormat');
        } elseif ($column->getType() === PropelTypes::TIMESTAMP) {
            $defaultfmt = $this->getBuilder()->getBuildProperty('generator.dateTime.defaultTimeStampFormat');
        }

        if (empty($defaultfmt)) {
            $defaultfmt = null;
        }

        $method
            ->setDescription("Get the [optionally formatted] temporal [$columnName] column value.")
            ->setType("string|{$dateTimeClass}")
            ->setTypeDescription("Formatted date/time value as string or $dateTimeClass object (if format is NULL), NULL if column is NULL\"" .
                ($handleMysqlDate ? ", and 0 if column value is $mysqlInvalidDateString" : ""))
            ->addParameter(PhpParameter::create('format')
                ->setMultilineDescription([
                    "The date/time format string (either date()-style or strftime()-style).",
                    "If format is NULL, then the raw $dateTimeClass object will be returned."
                ])
                ->setValue($defaultfmt)
            )
            ->appendToBody("
if (\$format === null) {
    return \$this->$columnName;
} else {
    return \$this->$columnName instanceof \DateTimeInterface ? \$this->{$columnName}->format(\$format) : null;
}
"
            )
            ->setDocblock($method->getDocblock()
                ->appendTag(ThrowsTag::create('PropelException - if unable to parse/validate the date/time value.'))
            )
        ;
    }

    private function addObjectAccessor(PhpMethod $method, Column $column): void
    {
        $clo = $column->getName()->toLowerCase()->toString();
        $cloUnserialized = $clo.'_unserialized';
        $method->appendToBody("
if (null === \$this->$cloUnserialized && is_resource(\$this->$clo)) {
    if (\$serialisedString = stream_get_contents(\$this->$clo)) {
        \$this->$cloUnserialized = unserialize(\$serialisedString);
    }
}

return \$this->$cloUnserialized;"
        );
    }

    private function addArrayAccessor(PhpMethod $method, Column $column): void
    {
        $clo = $column->getName()->toLowerCase()->toString();
        $cloUnserialized = $clo.'_unserialized';
        $method->appendToBody("
if (null === \$this->$cloUnserialized) {
    \$this->$cloUnserialized = [];
}
if (!\$this->$cloUnserialized && null !== \$this->$clo) {
    \$$cloUnserialized = substr(\$this->$clo, 2, -2);
    \$this->$cloUnserialized = '' !== \$$cloUnserialized ? explode(' | ', \$$cloUnserialized) : [];
}

return \$this->$cloUnserialized;"
        );
    }

    private function addHasArrayElement(Column $column): void
    {
        $columnType = ($column->getType() === PropelTypes::PHP_ARRAY) ? 'array' : 'set';

        $this->addMethod("has{$column->getPhpName()->toSingular()->toString()}", $column->getAccessorVisibility())
            ->setType('bool')
            ->setDescription(
                "Test the presence of a value in the [{$column->getName()->toLowerCase()->toString()}] $columnType column value."
            )
            ->addSimpleParameter('value')
            ->setBody("return in_array(\$value, \$this->get{$column->getMethodName()}("
                . ($column->isLazyLoad() ? "\$con);" : ");")
            )
        ;
    }

    private function addJsonAccessor(PhpMethod $method, Column $column): void
    {
        $method
            ->appendToBody("
return Json::decode(\$this->{$column->getName()->toLowerCase()->toString()});"
            );
        $this->useClass(Json::class);
    }

    private function addEnumAccessor(PhpMethod $method, Column $column): void
    {
        $clo = $column->getName()->toLowerCase()->toString();
        $method->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)));
        $method->appendToBody("
if (null === \$this->$clo) {
    return null;
}
\$valueSet = {$this->getTableMapClassName()}::getValueSet({$column->getConstantName()});
if (!isset(\$valueSet[\$this->$clo])) {
    throw new PropelException('Unknown stored enum key: ' . \$this->$clo);
}

return \$valueSet[\$this->$clo];"
        );
        $this->useClass(PropelException::class);
    }

    private function addSetAccessor(PhpMethod $method, Column $column): void
    {
        $clo = $column->getName()->toLowerCase()->toString();
        $cloConverted = $clo . '_converted';
        $method->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)));
        $this->useClass(PropelException::class);
        $this->useClass(SetColumnConverter::class);
        $this->useClass(SetColumnConverterException::class);

        $method->appendToBody("
if (null === \$this->$cloConverted) {
    \$this->$cloConverted = array();
}
if (!\$this->$cloConverted && null !== \$this->$clo) {
    \$valueSet = " . $this->getTableMapClassName() . "::getValueSet({$column->getConstantName()});
    try {
        \$this->$cloConverted = SetColumnConverter::convertIntToArray(\$this->$clo, \$valueSet);
    } catch (SetColumnConverterException \$e) {
        throw new PropelException('Unknown stored set key: ' . \$e->getValue(), \$e->getCode(), \$e);
    }
}

return \$this->$cloConverted;
"
        );
    }

    private function addBooleanAccessor(PhpMethod $method, Column $column): void
    {
        $name = Text::create($method->getName())->substring(3)->toCamelCase();
        if (!$name->startsWith('is') && !$name->startsWith('has')) {
            $name = $name->toStudlyCase()->prepend('is');
        }

        $method
            ->setName($name->toString())
            ->appendToBody("return \$this->get{$column->getMethodName()}(" . $column->isLazyLoad() ? "\$con);" : ");")
        ;
    }

    private function addLazyLoaderMethod(Column $column): void
    {
        $columnName = $column->getName()->toLowerCase()->toString();
        $platform = $this->getPlatform();
        $context = [
            'column' => $column,
            'columnName' => $columnName,
            'isSqlServer' => ($column->getType() === PropelTypes::BLOB && $platform instanceof SqlsrvPlatform),
            'isOracle' => ($column->getType() === PropelTypes::CLOB && $platform instanceof OraclePlatform),
            'queryName' => $this->getQueryClassName(),
            'lobNoStream' => ($column->isLobType() && !$platform->hasStreamBlobImpl())
        ];

        $method = $this->addMethod("load{$column->getMethodName()}")
            ->setType('void')
            ->setMultilineDescription([
                "Load the value for the lazy-loaded [$columnName] column.",
                "",
                "This method performs an additional query to return the value for",
                "the [$columnName] column, since it is not populated by",
                "the hydrate() method."
            ])
            ->addSimpleDescParameter('con', 'ConnectionInterface', '(optional) The ConnectionInterface connection to use.', null)
            ->setBody($this->renderTemplate($context, 'lazy_load_method'))
        ;
        $method->setDocblock($method->getDocblock()->appendTag(
            ThrowsTag::create(PropelException::class)->setDescription("any underlying error will be wrapped and re-thrown."))
        );
    }
}
