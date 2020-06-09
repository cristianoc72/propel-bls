<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Config;

use Propel\Common\Config\ConfigurationManager;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Exception\ClassNotFoundException;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Manager\BehaviorManager;
use Propel\Generator\Platform\SqlDefaultPlatform;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\AbstractSchemaParser;
use Propel\Generator\Reverse\SchemaParserInterface;
use Propel\Runtime\Adapter\AdapterFactory;
use Propel\Runtime\Connection\ConnectionFactory;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * A class that holds build properties and provide a class loading mechanism for
 * the generator.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Cristiano Cinotti
 */
class GeneratorConfig extends ConfigurationManager implements GeneratorConfigInterface
{
    protected BehaviorManager $behaviorManager;

    /**
     * Connections configured in the `generator` section of the configuration file
     *
     * @var array
     */
    protected array $buildConnections = [];

    /**
     * Creates and configures a new Platform class.
     *
     * @param  string              $platform
     * @param  ConnectionInterface $con
     *
     * @return PlatformInterface
     *
     * @throws ClassNotFoundException if the platform class doesn't exists
     * @throws BuildException         if the class isn't an implementation of PlatformInterface
     */
    public function createPlatform(string $platform, ConnectionInterface $con = null): PlatformInterface
    {
        $classes = [
            $platform,
            '\\Propel\\Generator\\Platform\\' . $platform,
            '\\Propel\\Generator\\Platform\\' . ucfirst($platform),
            '\\Propel\\Generator\\Platform\\' . ucfirst(strtolower($platform)) . 'Platform',
        ];

        $platformClass = '';

        foreach ($classes as $class) {
            if (class_exists($class)) {
                $platformClass = $class;
                break;
            }
        }

        if ('' === $platformClass) {
            throw new BuildException(sprintf('Platform `%s` not found.', $platform));
        }

        /** @var SqlDefaultPlatform $platform */
        $platform = new $platformClass;
        $platform->setConnection($con);
        $platform->setGeneratorConfig($this);

        return $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function createPlatformForDatabase(string $name = '', ConnectionInterface $con = null): PlatformInterface
    {
        return $this->get('generator.platformClass') ??
            $this->createPlatform($this->getBuildConnection($name)['adapter'], $con);
    }

    /**
     * Creates and configures a new SchemaParser class for specified platform.
     *
     * @param  ConnectionInterface $con
     *
     * @return SchemaParserInterface
     *
     * @throws ClassNotFoundException if the class doesn't exists
     * @throws BuildException         if the class isn't an implementation of
     *                                                            SchemaParserInterface
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null): SchemaParserInterface
    {
        $clazz = $this->get('migrations.parserClass') ?? '\\Propel\\Generator\\Reverse\\' .
            ucfirst($this->getBuildConnection()['adapter']) .
            'SchemaParser';

        /** @var SchemaParserInterface $parser */
        $parser = $this->getInstance($clazz, null, '\\Propel\\Generator\\Reverse\\SchemaParserInterface');
        $parser->setConnection($con);
        if ($parser instanceof AbstractSchemaParser) {
            $parser->setMigrationTable($this->get('migrations.tableName'));
        }
        $parser->setGeneratorConfig($this);

        return $parser;
    }

    /**
     * Return an array of all configured connection properties, from `generator` and `reverse`
     * sections of the configuration.
     *
     * @return array
     */
    public function getBuildConnections(): array
    {
        if ([] === $this->buildConnections) {
            $connectionNames = $this->get('generator.connections');

            $reverseConnection = $this->get('reverse.connection');
            if (null !== $reverseConnection && !in_array($reverseConnection, $connectionNames)) {
                $connectionNames[] = $reverseConnection;
            }

            foreach ($connectionNames as $name) {
                if ($definition = $this->get("database.connections.$name")) {
                    $this->buildConnections[$name] = $definition;
                }
            }
        }

        return $this->buildConnections;
    }

    /**
     * Return the connection properties array, of a given database name.
     * If the database name is null, it returns the default connection properties
     *
     * @param  string $databaseName
     *
     * @return array
     *
     * @throws InvalidArgumentException if wrong database name
     */
    public function getBuildConnection(string $databaseName = ''): array
    {
        if (!$databaseName && isset($this->get()['generator']['defaultConnection'])) {
            $databaseName = $this->get()['generator']['defaultConnection'];
        }

        if (!array_key_exists($databaseName, $this->getBuildConnections())) {
            throw new InvalidArgumentException(
                "Invalid database name: no configured connection named `$databaseName`."
            );
        }

        return $this->getBuildConnections()[$databaseName];
    }

    /**
     * Return a connection object of a given database name
     *
     * @param  string $database
     *
     * @return ConnectionInterface
     */
    public function getConnection(string $database = null): ConnectionInterface
    {
        $buildConnection = $this->getBuildConnection($database);

        //Still useful ?
        //$dsn = str_replace("@DB@", $database, $buildConnection['dsn']);
        $dsn = $buildConnection['dsn'];

        // Set user + password to null if they are empty strings or missing
        $username = $buildConnection['user'] ?? null;
        $password = $buildConnection['password'] ?? null;

        $con = ConnectionFactory::create(
            ['dsn' => $dsn, 'user' => $username, 'password' => $password],
            AdapterFactory::create($buildConnection['adapter'])
        );

        return $con;
    }

    public function getBehaviorManager(): BehaviorManager
    {
        if (!isset($this->behaviorManager)) {
            $this->behaviorManager = new BehaviorManager($this);
        }

        return $this->behaviorManager;
    }

    /**
     * Return an instance of $className
     *
     * @param string $className     The name of the class to return an instance
     * @param array  $arguments     The name of the interface to be implemented by the returned class
     * @param string $interfaceName The name of the interface to be implemented by the returned class
     *
     * @return object
     * @throws InvalidArgumentException if the interface doesn't exists
     * @throws BuildException           if the class isn't an implementation of the given
     *                                                              interface
     *
     * @throws ClassNotFoundException   if the class doesn't exists
     */
    private function getInstance(string $className, array $arguments = null, string $interfaceName = null)
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException("Class $className not found.");
        }

        $object = new $className($arguments);

        if (null !== $interfaceName) {
            if (!interface_exists($interfaceName)) {
                throw new InvalidArgumentException("Interface $interfaceName does not exist.");
            }

            if (!$object instanceof $interfaceName) {
                throw new BuildException("Specified class ($className) does not implement $interfaceName interface.");
            }
        }

        return $object;
    }
}
