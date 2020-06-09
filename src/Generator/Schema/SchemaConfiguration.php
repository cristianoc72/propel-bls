<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Schema;

use Propel\Generator\Model\Model;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class SchemaConfiguration
 *
 * This class performs validation of schema array and assign default values
 *
 */
class SchemaConfiguration implements ConfigurationInterface
{
    /**
     * Generates the schema tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('database');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
                ->fixXmlConfig('table')
                ->fixXmlConfig('behavior')
                ->fixXmlConfig('external_schema', 'external-schemas')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('phpName')->end()
                    ->enumNode('defaultIdMethod')
                        ->values([Model::ID_METHOD_NONE, Model::ID_METHOD_NATIVE])
                        ->defaultValue(Model::ID_METHOD_NATIVE)
                    ->end()
                    ->scalarNode('namespace')->end()
                    ->booleanNode('identifierQuoting')->end()
                    ->scalarNode('defaultStringFormat')->end()
                    ->booleanNode('heavyIndexing')->defaultFalse()->end()
                    ->scalarNode('baseClass')->end()
                    ->scalarNode('schema')->end()
                    ->append($this->getExternalSchemasNode())
                    ->append($this->getBehaviorsNode())
                    ->append($this->getVendorNode())
                    ->append($this->getTablesNode())
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function getParametersNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('parameters');

        return $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('name')->end()
                    ->scalarNode('value')->end()
                ->end()
            ->end()
        ;
    }

    private function getVendorNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('vendor');

        return $treeBuilder->getRootNode()
            ->fixXmlConfig('parameter')
            ->children()
                ->enumNode('type')->values(['mysql', 'MYSQL', 'oracle', 'ORACLE', 'pgsql', 'PGSQL'])->end()
                ->append($this->getParametersNode())
            ->end()
        ;
    }

    private function getBehaviorsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('behaviors');
        $node = $treeBuilder->getRootNode();
        $node
            ->beforeNormalization()
                ->always(function ($behaviors) {
                    foreach ($behaviors as $key => $behavior) {
                        if (!isset($behavior['id'])) {
                            $behaviors[$key]['id'] = $behavior['name'];
                        }
                    }

                    return $behaviors;
                })
            ->end()
            ->useAttributeAsKey('id')
            ->arrayPrototype()
                ->fixXmlConfig('parameter')
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->append($this->getParametersNode())
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getIndicesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('indices');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->fixXmlConfig('index-column', 'index-columns')
            ->normalizeKeys(false)
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('index-columns')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->integerNode('size')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }

    private function getUniquesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('uniques');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
            ->fixXmlConfig('unique-column', 'unique-columns')
            ->normalizeKeys(false)
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('unique-columns')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->integerNode('size')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;
    }

    private function getForeignKeysNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('foreign-keys');
        $node = $treeBuilder->getRootNode();
        $node
            ->arrayPrototype()
            ->fixXmlConfig('reference')
                ->children()
                    ->scalarNode('foreignTable')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('name')->end()
                    ->scalarNode('phpName')->end()
                    ->scalarNode('refColumn')->end()
                    ->scalarNode('refPhpName')->end()
                    ->scalarNode('foreignSchema')->end()
                    ->enumNode('onUpdate')
                        ->beforeNormalization()->always(function ($variable) {
                                return strtoupper($variable);
                            })
                        ->end()
                        ->values([Model::FK_CASCADE, Model::FK_SETNULL, Model::FK_RESTRICT, Model::FK_NONE])
                    ->end()
                    ->enumNode('onDelete')
                        ->beforeNormalization()->always(function ($variable) {
                                return strtoupper($variable);
                            })
                        ->end()
                        ->values([Model::FK_CASCADE, Model::FK_SETNULL, Model::FK_RESTRICT, Model::FK_NONE])
                    ->end()
                    ->enumNode('defaultJoin')
                        ->beforeNormalization()->always(function ($variable) {
                                return strtoupper($variable);
                            })
                        ->end()
                        ->values(['INNER JOIN', 'LEFT JOIN'])
                    ->end()
                    ->booleanNode('skipSql')->defaultFalse()->end()
                    ->arrayNode('references')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('local')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('foreign')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getInheritancesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('inheritances');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('key')->isRequired()->end()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('extends')->end()
                ->end()
            ->end()
        ;
    }

    private function getColumnsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('columns');
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->fixXmlconfig('inheritance')
            ->arrayPrototype()
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('phpName')->end()
                    ->booleanNode('primaryKey')->defaultFalse()->end()
                    ->booleanNode('required')->defaultFalse()->end()
                    ->enumNode('type')
                        ->beforeNormalization()->always(function ($variable) {
                            return strtoupper($variable);
                        })
                        ->end()
                        ->values(['BIT', 'TINYINT', 'SMALLINT', 'INTEGER', 'BIGINT', 'FLOAT',
                            'REAL', 'NUMERIC', 'DECIMAL', 'CHAR', 'VARCHAR', 'LONGVARCHAR',
                            'DATE', 'TIME', 'TIMESTAMP', 'BINARY', 'VARBINARY', 'LONGVARBINARY',
                            'NULL', 'OTHER', 'PHP_OBJECT', 'DISTINCT', 'STRUCT', 'ARRAY',
                            'BLOB', 'CLOB', 'REF', 'BOOLEANINT', 'BOOLEANCHAR', 'DOUBLE',
                            'BOOLEAN', 'OBJECT', 'ENUM'
                        ])
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('VARCHAR')
                    ->end()
                    ->scalarNode('phpType')->end()
                    ->scalarNode('sqlType')->end()
                    ->integerNode('size')->end()
                    ->integerNode('scale')->end()
                    ->scalarNode('default')->end()
                    ->scalarNode('defaultValue')->end()
                    ->scalarNode('defaultExpr')->end()
                    ->booleanNode('autoIncrement')->defaultFalse()->end()
                    ->enumNode('inheritance')->values(['single', 'none'])->defaultValue('none')->end()
                    ->scalarNode('description')->end()
                    ->booleanNode('lazyLoad')->defaultFalse()->end()
                    ->booleanNode('primaryString')->defaultFalse()->end()
                    ->scalarNode('valueSet')->end()
                    ->append($this->getInheritancesNode())
                    ->append($this->getVendorNode())
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getTablesNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('tables');
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->arrayPrototype()
                ->addDefaultsIfNotSet()
                ->fixXmlConfig('column')
                ->fixXmlConfig('behavior')
                ->fixXmlConfig('foreignKey')
                ->fixXmlConfig('index', 'indices')
                ->fixXmlConfig('unique', 'uniques')
                ->children()
                    ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('phpName')->end()
                    ->scalarNode('tableName')->end()
                    ->enumNode('idMethod')
                        ->values(['native', 'autoincrement', 'sequence', 'none', null])
                        ->defaultNull()
                    ->end()
                    ->booleanNode('skipSql')->defaultFalse()->end()
                    ->booleanNode('readOnly')->defaultFalse()->end()
                    ->booleanNode('abstract')->defaultFalse()->end()
                    ->booleanNode('isCrossRef')->defaultFalse()->end()
                    ->scalarNode('schema')->end()
                    ->scalarNode('namespace')->end()
                    ->booleanNode('identifierQuoting')->end()
                    ->scalarNode('description')->end()
                    ->booleanNode('reloadOnInsert')->defaultFalse()->end()
                    ->booleanNode('reloadOnUpdate')->defaultFalse()->end()
                    ->booleanNode('allowPkInsert')->defaultFalse()->end()
                    ->booleanNode('heavyIndexing')->end()
                    ->scalarNode('defaultStringFormat')->end()
                    ->append($this->getColumnsNode())
                    ->append($this->getForeignKeysNode())
                    ->append($this->getIndicesNode())
                    ->append($this->getUniquesNode())
                    ->append($this->getBehaviorsNode())
                    ->append($this->getVendorNode())
                    ->arrayNode('id_method_parameter')
                        ->children()
                            ->scalarNode('value')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function getExternalSchemasNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('external-schemas');

        return $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('filename')->end()
                    ->booleanNode('referenceOnly')->defaultTrue()->end()
                ->end()
            ->end()
        ;
    }
}
