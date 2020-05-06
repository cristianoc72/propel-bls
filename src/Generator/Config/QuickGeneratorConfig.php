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

use cristianoc72\Pluralizer\PluralizerInterface;
use cristianoc72\Pluralizer\EnglishPluralizer;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Manager\BehaviorManager;
use Propel\Generator\Model\Table;

/**
 * Class QuickGeneratorConfig
 *
 * Simple generator config class. It's usually used with QuickBuilder, for testing purpose
 */
class QuickGeneratorConfig extends GeneratorConfig implements GeneratorConfigInterface
{
    /**
     * @var BehaviorManager
     */
    protected $behaviorManager = null;

    /**
     * QuickGeneratorConfig constructor.
     *
     * @param array $extraConf
     */
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
     * Returns a configured Pluralizer class.
     *
     * @return PluralizerInterface
     */
    public function getConfiguredPluralizer(): PluralizerInterface
    {
        return new EnglishPluralizer();
    }

    public function getBehaviorManager(): BehaviorManager
    {
        if (!$this->behaviorManager) {
            $this->behaviorManager = new BehaviorManager($this);
        }

        return $this->behaviorManager;
    }
}
