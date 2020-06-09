<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\ClassNotFoundException;
use Propel\Generator\Manager\BehaviorManager;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Connection\ConnectionInterface;

interface GeneratorConfigInterface
{
    /**
     * Creates and configures a new Platform class.
     *
     * @param  string              $platform full or short class name
     * @param  ConnectionInterface $con
     *
     * @return PlatformInterface
     */
    public function createPlatform(string $platform, ConnectionInterface $con = null): PlatformInterface;

    /**
     * @param string|null $name returns default platform if null
     * @param ConnectionInterface $con
     *
     * @return PlatformInterface
     */
    public function createPlatformForDatabase(string $name = '', ConnectionInterface $con = null): PlatformInterface;

    /**
     * Returns the behavior locator.
     *
     * @return BehaviorManager
     */
    public function getBehaviorManager(): BehaviorManager;

    /**
     * Creates and configures a new SchemaParser class for a specified platform.
     *
     * @param  ConnectionInterface $con
     *
     * @return SchemaParserInterface
     *
     * @throws ClassNotFoundException if the class doesn't exist
     * @throws BuildException         if the class isn't an implementation of SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null): ?SchemaParserInterface;

    /**
     * Return a configuration property.
     * The name of the requested property must be given as a string, representing its hierarchy in the configuration
     * array, with each level separated by a dot. I.e.:
     * <code> $config['database']['adapter']['mysql']['tableType']</code>
     * is expressed by:
     * <code>'database.adapter.mysql.tableType</code>
     * If nothing is passed, then return all the configuration array.
     *
     * @param string $name The name of property, expressed as a dot separated level hierarchy
     * @throws InvalidArgumentException
     * @return mixed The configuration property
     */
    public function get(string $name);
}
