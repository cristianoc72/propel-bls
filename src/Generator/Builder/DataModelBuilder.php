<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder;

use cristianoc72\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\InterfaceBuilder;
use Propel\Generator\Builder\Om\MultiExtendBuilder;
use Propel\Generator\Builder\Om\MultiExtendObjectBuilder;
use Propel\Generator\Builder\Om\ObjectBuilder;
use Propel\Generator\Builder\Om\QueryBuilder;
use Propel\Generator\Builder\Om\QueryInheritanceBuilder;
use Propel\Generator\Builder\Om\StubObjectBuilder;
use Propel\Generator\Builder\Om\StubQueryBuilder;
use Propel\Generator\Builder\Om\StubQueryInheritanceBuilder;
use Propel\Generator\Builder\Om\TableMapBuilder;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;

/**
 * This is the base class for any builder class that is using the data model.
 *
 * This could be extended by classes that build SQL DDL, PHP classes, configuration
 * files, input forms, etc.
 *
 * The GeneratorConfig needs to be set on this class in order for the builders
 * to be able to access the propel generator build properties.  You should be
 * safe if you always use the GeneratorConfig to get a configured builder class
 * anyway.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
abstract class DataModelBuilder
{

    /**
     * The current table.
     *
     * @var Table
     */
    private $table;

    /**
     * The generator config object holding build properties, etc.
     *
     * @var GeneratorConfigInterface
     */
    private $generatorConfig;

    /**
     * An array of warning messages that can be retrieved for display.
     *
     * @var array string[]
     */
    private $warnings = [];

    /**
     * Object builder class for current table.
     *
     * @var ObjectBuilder
     */
    private $objectBuilder;

    /**
     * Stub Object builder class for current table.
     *
     * @var StubObjectBuilder
     */
    private $stubObjectBuilder;

    /**
     * Query builder class for current table.
     *
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Stub Query builder class for current table.
     *
     * @var StubQueryBuilder
     */
    private $stubQueryBuilder;

    /**
     * TableMap builder class for current table.
     *
     * @var TableMapBuilder
     */
    protected $tableMapBuilder;

    /**
     * Stub Interface builder class for current table.
     *
     * @var InterfaceBuilder
     */
    private $interfaceBuilder;

    /**
     * Stub child object for current table.
     *
     * @var MultiExtendObjectBuilder
     */
    private $multiExtendObjectBuilder;

    /**
     * The Pluralizer class to use.
     *
     * @var PluralizerInterface
     */
    private $pluralizer;

    /**
     * The platform class
     *
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * Creates new instance of DataModelBuilder subclass.
     *
     * @param Table $table The Table which we are using to build [OM, DDL, etc.].
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns new or existing Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getPluralizer(): PluralizerInterface
    {
        if (!isset($this->pluralizer)) {
            $this->pluralizer = $this->getGeneratorConfig()->getConfiguredPluralizer();
        }

        return $this->pluralizer;
    }

    /**
     * Returns new or existing Object builder class for this table.
     *
     * @return ObjectBuilder
     */
    public function getObjectBuilder(): ObjectBuilder
    {
        if (!isset($this->objectBuilder)) {
            $this->objectBuilder = $this->getNewBuilder($this->getTable(), ObjectBuilder::class);
        }

        return $this->objectBuilder;
    }

    /**
     * Returns new or existing stub Object builder class for this table.
     *
     * @return StubObjectBuilder
     */
    public function getStubObjectBuilder(): StubObjectBuilder
    {
        if (!isset($this->stubObjectBuilder)) {
            $this->stubObjectBuilder = $this->getNewBuilder($this->getTable(), StubObjectBuilder::class);
        }

        return $this->stubObjectBuilder;
    }

    /**
     * Returns new or existing Query builder class for this table.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        if (!isset($this->queryBuilder)) {
            $this->queryBuilder = $this->getNewBuilder($this->getTable(), QueryBuilder::class);
        }

        return $this->queryBuilder;
    }

    /**
     * Returns new or existing stub Query builder class for this table.
     *
     * @return StubQueryBuilder
     */
    public function getStubQueryBuilder(): StubQueryBuilder
    {
        if (!isset($this->stubQueryBuilder)) {
            $this->stubQueryBuilder = $this->getNewBuilder($this->getTable(), StubQueryBuilder::class);
        }

        return $this->stubQueryBuilder;
    }

    /**
     * Returns new or existing Object builder class for this table.
     * @return TableMapBuilder
     */
    public function getTableMapBuilder(): TableMapBuilder
    {
        if (!isset($this->tableMapBuilder)) {
            $this->tableMapBuilder = $this->getNewBuilder($this->getTable(), TableMapBuilder::class);
        }

        return $this->tableMapBuilder;
    }

    /**
     * Returns new or existing stub Interface builder class for this table.
     * @return InterfaceBuilder
     */
    public function getInterfaceBuilder(): InterfaceBuilder
    {
        if (!isset($this->interfaceBuilder)) {
            $this->interfaceBuilder = $this->getNewBuilder($this->getTable(), InterfaceBuilder::class);
        }

        return $this->interfaceBuilder;
    }

    /**
     * Returns new or existing stub child object builder class for this table.
     *
     * @return MultiExtendObjectBuilder
     */
    public function getMultiExtendObjectBuilder(): MultiExtendObjectBuilder
    {
        if (!isset($this->multiExtendObjectBuilder)) {
            $this->multiExtendObjectBuilder = $this->getNewBuilder($this->getTable(), MultiExtendObjectBuilder::class);
        }

        return $this->multiExtendObjectBuilder;
    }

    /**
    * Gets a new data model builder class for specified table and classname.
     *
     * @param  Table            $table
     * @param  string           $classname The class of builder
     * @return DataModelBuilder
     */
    public function getNewBuilder(Table $table, string $classname): DataModelBuilder
    {
        /** @var DataModelBuilder $builder */
        $builder = new $classname($table);
        $builder->setGeneratorConfig($this->getGeneratorConfig());

        return $builder;
    }

    /**
     * Convenience method to return a NEW Object class builder instance.
     *
     * This is used very frequently from the tableMap and object builders to get
     * an object builder for a RELATED table.
     *
     * @param  Table         $table
     *
     * @return ObjectBuilder
     * @deprecated use getNewBuilder instead
     */
    public function getNewObjectBuilder(Table $table): ObjectBuilder
    {
        return $this->getNewBuilder($table, ObjectBuilder::class);
    }

    /**
     * Convenience method to return a NEW Object stub class builder instance.
     *
     * This is used from the query builders to get
     * an object builder for a RELATED table.
     *
     * @param  Table         $table
     * @return StubObjectBuilder
     * @deprecated use getNewBuilder instead
     */
    public function getNewStubObjectBuilder(Table $table): StubObjectBuilder
    {
        return $this->getNewBuilder($table, StubObjectBuilder::class);
    }

    /**
     * Convenience method to return a NEW query class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param  Table        $table
     * @return QueryBuilder
     * @deprecated use getNewBuilder instead
     */
    public function getNewQueryBuilder(Table $table): QueryBuilder
    {
        return $this->getNewBuilder($table, QueryBuilder::class);
    }

    /**
     * Convenience method to return a NEW query stub class builder instance.
     *
     * This is used from the query builders to get
     * a query builder for a RELATED table.
     *
     * @param  Table        $table
     * @return StubQueryBuilder
     * @deprecated use getNewBuilder instead
     */
    public function getNewStubQueryBuilder(Table $table): StubQueryBuilder
    {
        return $this->getNewBuilder($table, StubQueryBuilder::class);
    }

    /**
     * Returns new Query Inheritance builder class for this table.
     *
     * @param  Inheritance   $child
     * @return QueryInheritanceBuilder
     * @deprecated use getNewBuilderInstead
     */
    public function getNewQueryInheritanceBuilder(Inheritance $child): QueryInheritanceBuilder
    {
        return $this->getNewBuilder($child, QueryInheritanceBuilder::class);
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     *
     * @param  Inheritance   $child
     * @return StubQueryInheritanceBuilder
     * @deprecated use getNewBuilderInstead
     */
    public function getNewStubQueryInheritanceBuilder(Inheritance $child): StubQueryInheritanceBuilder
    {
        return $this->getNewBuilder($child, StubQueryInheritanceBuilder::class);
    }

    /**
     * Returns new stub Query Inheritance builder class for this table.
     * @return TableMapBuilder
     * @deprecated use getNewBuilderInstead
     */
    public function getNewTableMapBuilder(Table $table)
    {
        return $this->getNewBuilder($table, TableMapBuilder::class);
    }

    /**
     * Gets the GeneratorConfig object.
     *
     * @return GeneratorConfigInterface
     */
    public function getGeneratorConfig(): GeneratorConfigInterface
    {
        return $this->generatorConfig;
    }

    /**
     * Get a specific configuration property.
     *
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     *
     * @param  string $name
     *
     * @return mixed
     */
    public function getBuildProperty(string $name)
    {
        if ($this->getGeneratorConfig()) {
            return $this->getGeneratorConfig()->getConfigProperty($name);
        }

        return null; // just to be explicit
    }

    /**
     * Sets the GeneratorConfig object.
     *
     * @param GeneratorConfigInterface $v
     */
    public function setGeneratorConfig(GeneratorConfigInterface $v): void
    {
        $this->generatorConfig = $v;
    }

    /**
     * Sets the table for this builder.
     *
     * @param Table $table
     */
    public function setTable(Table $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns the current Table object.
     *
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Convenience method to returns the Platform class for this table (database).
     * @return PlatformInterface
     */
    public function getPlatform(): PlatformInterface
    {
        if (null === $this->platform) {
            // try to load the platform from the table
            $table = $this->getTable();
            if ($table && $database = $table->getDatabase()) {
                $this->setPlatform($database->getPlatform());
            }
        }

        if (!$this->table->isIdentifierQuotingEnabled()) {
            $this->platform->setIdentifierQuoting(false);
        }

        return $this->platform;
    }

    /**
     * Platform setter
     *
     * @param PlatformInterface $platform
     */
    public function setPlatform(PlatformInterface $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Quotes identifier based on $this->getTable()->isIdentifierQuotingEnabled.
     *
     * @param string $text
     * @return string
     */
    public function quoteIdentifier(string $text): string
    {
        if ($this->getTable()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($text);
        }

        return $text;
    }

    /**
     * Convenience method to returns the database for current table.
     * @return Database
     * @deprecated use getTable()->getDatabase()
     */
    public function getDatabase(): Database
    {
        if ($this->getTable()) {
            return $this->getTable()->getDatabase();
        }
    }

    /**
     * Pushes a message onto the stack of warnings.
     *
     * @param string $msg The warning message.
     */
    protected function warn(string $msg): void
    {
        $this->warnings[] = $msg;
    }

    /**
     * Gets array of warning messages.
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns the name of the current class being built, with a possible prefix.
     *
     * @param string $identifier
     * @return string
     * @see OMBuilder#getClassName()
     */
    public function prefixClassName(string $identifier): string
    {
        return $this->getBuildProperty('generator.objectModel.classPrefix') . $identifier;
    }
}
