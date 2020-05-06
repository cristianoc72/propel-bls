<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use Propel\Common\Collection\Map;
use Propel\Common\Collection\Set;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossForeignKeyTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\PropelTypes;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Map\ColumnMap;

class Properties extends BuildComponent
{
    use ForeignKeyTrait;

    public function process(): void
    {
        $this->addCommonProperties();
        $this->addColumnProperties();
        $this->addForeignKeyProperties();
    }

    /**
     * Add common (non column) properties.
     */
    private function addCommonProperties(): void
    {
        $this->addProperty('new', 'bool', true, 'Attribute to determine if this object has previously been saved.');
        $this->addProperty('deleted', 'bool', false, 'Attribute to determine whether this object has been deleted.');
        $this->addProperty('alreadyInSave', 'bool', false)->setMultilineDescription([
            "Flag to prevent endless save loop, if this object is referenced",
            "by another object which falls in this transaction."
        ]);

        $this->addProperty('modifiedColumns', 'Map')->setMultilineDescription([
            'The columns that have been modified in current object.',
            'Tracking modified columns allows us to only update modified columns.'
        ]);
        $this->addProperty('virtualColumns', 'Map')->setMultilineDescription([
            'The (virtual) columns that are added at runtime',
            'The formatters can add supplementary columns based on a resultset'
        ]);
        $this->addConstructorBody('$this->modifiedColumns = new Map();');
        $this->addConstructorBody('$this->virtualColumns = new Map();');
        $this->useClass(Map::class);
    }

    /**
     * Add properties mapping columns on the database
     */
    private function addColumnProperties(): void
    {
        /** @var Column $column */
        foreach ($this->getTable()->getColumns()->toArray() as $column) {
            $columnName = $column->getName()->toLowerCase()->toString();

            $this->addProperty($columnName, $column->isTemporalType() ?
                    $this->getBuilder()->getBuildProperty('generator.dateTime.dateTimeClass') :
                    $column->getPhpType()
            )
            ->setMultilineDescription([
                "The value for the $columnName field.",
                $column->getDescription(),
                $column->hasDefaultValue() ?
                    "Note: this column has a database default value of:" .
                    ($column->getDefaultValue()->isExpression() ?
                        "(expression) {$column->getDefaultValue()->getValue()}" :
                        $column->getDefaultValueString()
                    ) : ''

            ])
            ;

            if ($column->isLazyLoad() ) {
                $this
                    ->addProperty($columnName . '_isLoaded', 'bool', false)
                    ->setMultilineDescription([
                        "Whether the lazy-loaded \$$columnName value has been loaded from database.",
                        "This is necessary to avoid repeated lookups if \$$columnName column is NULL in the db."
                    ])
                ;
            }
            if ($column->getType() == PropelTypes::OBJECT || $column->getType() == PropelTypes::PHP_ARRAY) {
                $this->addProperty($columnName . '_unserialized', 'object')->setMultilineDescription([
                    "The unserialized \$$columnName value - i.e. the persisted object.",
                    "This is necessary to avoid repeated calls to unserialize() at runtime."
                ]);
            }
            if (PropelTypes::isSetType($column->getType())) {
                $this->addProperty($columnName . '_converted', 'string'); //@todo is the type really string?
            }
        }
    }

    /**
     * Adds the class attributes that are needed to store fkey related objects.
     */
    private function addForeignKeyProperties(): void
    {
        $this->getTable()->getForeignKeys()->each(function (ForeignKey $foreignKey) {
            $className = $this->useClass($foreignKey->getForeignTable()->getFullName()->toString());
            $varName = $this->getForeignKeyVarName($foreignKey)->toString();

            $this
                ->addProperty($varName, $className)
                ->setTypeDescription($foreignKey->isLocalPrimaryKey() ?
                    "one-to-one related $className object" :
                    "many-to-one related $className object"
                );
            }
        );
    }
}
