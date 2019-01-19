<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Generator\Config;

use Propel\Common\Pluralizer\PluralizerInterface;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Generator\Util\BehaviorLocator;

interface GeneratorConfigInterface
{
    /**
     * Returns a configured data model builder class for specified table and
     * based on type ('ddl', 'sql', etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, string $type): DataModelBuilder;

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer(): PluralizerInterface;


    /**
     * Creates and configures a new Platform class.
     *
     * @param  ConnectionInterface $con
     * @param  string              $database
     * @return PlatformInterface
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException if the platform class doesn't exists
     * @throws \Propel\Generator\Exception\BuildException         if the class isn't an implementation of PlatformInterface
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, string $database = null): ?PlatformInterface;

    /**
     * Creates and configures a new SchemaParser class for a specified platform.
     *
     * @param  ConnectionInterface $con
     * @param  string              $database
     *
     * @return SchemaParserInterface
     *
     * @throws \Propel\Generator\Exception\ClassNotFoundException if the class doesn't exist
     * @throws \Propel\Generator\Exception\BuildException         if the class isn't an implementation of SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null, string $database = null): ?SchemaParserInterface;

    /**
     * Returns the behavior locator.
     *
     * @return BehaviorLocator
     */
    public function getBehaviorLocator(): BehaviorLocator;

    /**
     * Return a specific configuration property.
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     *
     * @param string $name The name of property, expressed as a dot separated level hierarchy
     * @throws \Propel\Common\Config\Exception\InvalidArgumentException
     * @return mixed The configuration property
     */
    public function getConfigProperty(string $name);
}
