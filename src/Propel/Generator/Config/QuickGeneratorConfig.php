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

use Propel\Common\Config\ConfigurationManager;
use cristianoc72\Pluralizer\PluralizerInterface;
use cristianoc72\Pluralizer\EnglishPluralizer;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Reverse\SchemaParserInterface;
use \Propel\Runtime\Connection\ConnectionInterface;
use Propel\Generator\Util\BehaviorLocator;

class QuickGeneratorConfig extends ConfigurationManager implements GeneratorConfigInterface
{
    /**
     * @var BehaviorLocator
     */
    protected $behaviorLocator = null;

    public function __construct(array $extraConf = [])
    {
        if (null === $extraConf) {
            $extraConf = [];
        }

        //Creates a GeneratorConfig based on Propel default values plus the following
        $configs = [
           'propel' => [
               'database' => [
                   'connections' => [
                       'default' => [
                           'adapter' => 'sqlite',
                           'classname' => 'Propel\Runtime\Connection\DebugPDO',
                           'dsn' => 'sqlite::memory:',
                           'user' => '',
                           'password' => ''
                       ]
                   ]
               ],
               'runtime' => [
                   'defaultConnection' => 'default',
                   'connections' => ['default']
               ],
               'generator' => [
                   'defaultConnection' => 'default',
                   'connections' => ['default']
               ]
           ]
        ];

        $configs = array_replace_recursive($configs, $extraConf);
        $this->process($configs);
    }

    /**
     * Gets a configured data model builder class for specified table and based
     * on type ('ddl', 'sql', etc.).
     *
     * @param  Table            $table
     * @param  string           $type
     * @return DataModelBuilder
     */
    public function getConfiguredBuilder(Table $table, string $type): DataModelBuilder
    {
        $class = $this->getConfigProperty('generator.objectModel.builders.' . $type);

        if (null === $class) {
            throw new InvalidArgumentException("Invalid data model builder type `$type`");
        }

        $builder = new $class($table);
        $builder->setGeneratorConfig($this);

        return $builder;
    }

    /**
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer(): PluralizerInterface
    {
        return new EnglishPluralizer();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredPlatform(ConnectionInterface $con = null, string $database = null): ?PlatformInterface
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguredSchemaParser(ConnectionInterface $con = null, string $database = null): ?SchemaParserInterface
    {
        return null;
    }

    public function getBehaviorLocator(): BehaviorLocator
    {
        if (!$this->behaviorLocator) {
            $this->behaviorLocator = new BehaviorLocator($this);
        }

        return $this->behaviorLocator;
    }
}
