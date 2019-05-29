<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Manager\BehaviorManager;

/**
 * Class ModelFactory
 *
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 */
class ModelFactory
{
    private $database = ['map' => [
        'name' => 'setName',
        'phpName' => 'setPhpName',
        'baseClass' => 'setBaseClass',
        'defaultIdMethod' => 'setDefaultIdMethod',
        'heavyIndexing' => 'setHeavyIndexing',
        'identifierQuoting' => 'setIdentifierQuoting',
        'scope' => 'setScope',
        'defaultStringFormat' => 'setStringFormat',
        'schema' => 'setSchemaName',
        'namespace' => 'setNamespace',

    ]];

    private $table = ['map' => [
        'name' => 'setName',
        'phpName' => 'setPhpName',
        'description' => 'setDescription',
        'tableName' => 'setTableName',
        'allowPkInsert' => 'setAllowPkInsert',
        'skipSql' => 'setSkipSql',
        'readOnly' => 'setReadOnly',
        'abstract' => 'setAbstract',
        'baseClass' => 'setBaseClass',
        'alias' => 'setAlias',
        'identifierQuoting' => 'setIdentifierQuoting',
        'reloadOnInsert' => 'setReloadOnInsert',
        'reloadOnUpdate' => 'setReloadOnUpdate',
        'isCrossRef' => 'setCrossRef',
        'defaultStringFormat' => 'setStringFormat',
        'heavyIndexing' => 'setHeavyIndexing',
        'schema' => 'setSchemaName',
        'namespace' => 'setNamespace'
    ]];

    private $column = ['map' => [
        'name' => 'setName',
        'phpName' => 'setPhpName',
        'required' => 'setNotNull',
        'primaryKey' => 'setPrimaryKey',
        'type' => 'setType',
        'description' => 'setDescription',
        'columnName' => 'setColumnName',
        'phpType' => 'setPhpType',
        'sqlType' => 'setSqlType',
        'size' => 'setSize',
        'scale' => 'setScale',
        'defaultValue' => 'setDefaultValue',
        'default' => 'setDefaultValue',
        'defaultExpression' => 'setDefaultExpression',
        'autoIncrement' => 'setAutoIncrement',
        'lazyLoad' => 'setLazyLoad',
        'primaryString' => 'setPrimaryString',
        'valueSet' => 'setValueSet',
        'inheritance' => 'setInheritanceType'
    ]];

    private $vendor = ['map' => [
        'type' => 'setType',
        'parameters' => 'setParameters'
    ]];

    private $inheritance = ['map' => [
        'key' => 'setKey',
        'class' => 'setClassName',
        'extends' => 'setAncestor'
    ]];

    private $foreignKey = ['map'=> [
        'target' => 'setForeignTableName',
        'column' => 'setColumn',
        'name' => 'setName',
        'refColumn' => 'setRefColumn',
        'refPhpName' => 'setRefPhpName',
        'onUpdate' => 'setOnUpdate',
        'onDelete' => 'setOnDelete',
        'defaultJoin' => 'setDefaultJoin',
        'skipSql' => 'setSkipSql',
        'foreignSchema' => 'setForeignSchema'
    ]];

    /** @var GeneratorConfigInterface */
    private $config;

    /** @var BehaviorManager */
    private $behaviorManager;

    /**
     * ModelFactory constructor.
     *
     * @param null|GeneratorConfigInterface $config
     */
    public function __construct(?GeneratorConfigInterface $config = null)
    {
        $this->config = $config;
    }

    /**
     * @param GeneratorConfigInterface $config
     */
    public function setGeneratorConfig(GeneratorConfigInterface $config): void
    {
        $this->config = $config;
    }

    /**
     * @param array $attributes
     *
     * @return Vendor
     */
    public function createVendor(array $attributes): Vendor
    {
        $params = [];
        foreach ($attributes['parameters'] as $key => $parameter) {
            $params[$parameter['name']] = $parameter['value'];
        }
        $attributes['parameters'] = $params;

        return $this->load(new Vendor(), $attributes, $this->vendor);
    }

    /**
     * @param array $attributes
     *
     * @return Database
     */
    public function createDatabase(array $attributes): Database
    {
        $database = $this->load(new Database(), $attributes, $this->database);

        if (isset($attributes['platform']) && $this->config) {
            $platform = $this->config->createPlatform($attributes['platform']);
            if ($platform) {
                $database->setPlatform($platform);
            }
        }

        return $database;
    }

    /**
     * @param array $attributes
     *
     * @return Table
     */
    public function createTable(array $attributes): Table
    {
        return $this->load(new Table(), $attributes, $this->table);
    }

    /**
     * @param array $attributes
     *
     * @return Column
     */
    public function createColumn(array $attributes): Column
    {
        return $this->load(new Column(), $attributes, $this->column);
    }

    /**
     * @param array $attributes
     *
     * @return Inheritance
     */
    public function createInheritance(array $attributes): Inheritance
    {
        return $this->load(new Inheritance(), $attributes, $this->inheritance);
    }

    /**
     * @param array $attributes
     *
     * @return ForeignKey
     */
    public function createForeignKey(array $attributes): ForeignKey
    {
        $foreignKey = $this->load(new ForeignKey(), $attributes, $this->foreignKey);

        if (count($attributes['references']) >0) {
            foreach ($attributes['references'] as $reference) {
                $foreignKey->addReference($reference['local'], $reference['foreign']);
            }
        }

        return $foreignKey;
    }

    /**
     * @param array $attributes
     *
     * @return Index
     */
    public function createIndex(array $attributes): Index
    {
        $index = new Index();
        if (isset($attributes['name'])) {
            $index->setName($attributes['name']);
        }

        return $index;
    }

    /**
     * @param array $attributes
     *
     * @return Unique
     */
    public function createUnique(array $attributes): Unique
    {
        $unique = new Unique();
        if (isset($attributes['name'])) {
            $unique->setName($attributes['name']);
        }

        return $unique;
    }

    /**
     * @param array $attributes
     *
     * @return IdMethodParameter
     */
    public function createIdMethodParameter(array $attributes): IdMethodParameter
    {
        $idMethodParam = new IdMethodParameter();
        $idMethodParam->setValue($attributes['value']);

        return $idMethodParam;
    }

    public function createBehavior(array $attributes): Behavior
    {
        $behavior = $this->getBehaviorManager()->create($attributes['name']);
        if (isset($attributes['parameters'])) {
            foreach ($attributes['parameters'] as $name => $value) {
                $behavior->setParameter($name, $value);
            }
        }

        return $behavior;
    }

    /**
     * @return BehaviorManager
     */
    protected function getBehaviorManager(): BehaviorManager
    {
        if (null === $this->behaviorManager) {
            $this->behaviorManager = new BehaviorManager($this->config);
        }

        return $this->behaviorManager;
    }

    /**
     * @param $model
     * @param array $attributes
     * @param array $definition
     *
     * @return mixed
     */
    private function load($model, array $attributes, array $definition)
    {
        if (isset($definition['map'])) {
            $model = $this->loadMapping($model, $attributes, $definition['map']);
        }

        return $model;
    }

    /**
     * @param $model
     * @param array $attributes
     * @param array $map
     *
     * @return mixed
     */
    private function loadMapping($model, array $attributes, array $map)
    {
        foreach ($map as $key => $method) {
            if (isset($attributes[$key]) && method_exists($model, $method)) {
                $model->$method($attributes[$key]);
            }
        }

        return $model;
    }
}
