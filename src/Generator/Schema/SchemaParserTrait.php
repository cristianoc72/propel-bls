<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Schema;

use phootwork\file\File;
use Propel\Generator\Config\GeneratorConfigInterface;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\ModelFactory;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Unique;

/**
 * A parser trait that visits elements on a schema array
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author Jason van Zyl <jvanzyl@apache.org> (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @author Daniel Rall <dlr@collab.net> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 * @author Thomas Gossmann
 * @author Cristiano Cinotti
 *
 */
trait SchemaParserTrait
{
    private ModelFactory $modelFactory;

    abstract public function getGeneratorConfig(): GeneratorConfigInterface;

    private function getModelFactory(): ModelFactory
    {
        if (!isset($this->modelFactory)) {
            $this->modelFactory = new ModelFactory($this->getGeneratorConfig());
        }

        return $this->modelFactory;
    }

    /**
     * @param array $schemaContent
     * @param Schema $schema
     */
    private function parseDatabase(array $schemaContent, Schema $schema): void
    {
        $database = $this->getModelFactory()->createDatabase($schemaContent);
        $schema->addDatabase($database);

        $this->addExternalSchemas($schemaContent['external-schemas'], $schema);
        $this->addBehaviors($schemaContent['behaviors'], $database);
        $this->addVendor($schemaContent, $database);
        $this->addTables($schemaContent['tables'], $database);
    }

    /**
     * @param array $tables
     * @param Database $database
     */
    private function addTables(array $tables, Database $database): void
    {
        foreach ($tables as $table) {
            $tableObj = $this->getModelFactory()->createTable($table);
            $database->addTable($tableObj);

            if ($database->getSchema()->isExternalSchema()) {
                $table->setForReferenceOnly(true);
            }

            $this->addColumns($table['columns'], $tableObj);
            $this->addForeignKeys($table['foreignKeys'], $tableObj);
            $this->addIndices($table['indices'], $tableObj);
            $this->addUniques($table['uniques'], $tableObj);
            $this->addBehaviors($table['behaviors'], $tableObj);
            $this->addVendor($table, $tableObj);
            $this->addIdMethodParameter($table, $tableObj);
        }
    }

    /**
     * @param array $columns
     * @param Table $table
     */
    private function addColumns(array $columns, Table $table): void
    {
        foreach ($columns as $column) {
            $columnObj = $this->getModelFactory()->createColumn($column);
            $this->addInheritance($column['inheritances'], $columnObj);
            $this->addVendor($column, $columnObj);

            $table->addColumn($columnObj);
        }
    }

    /**
     * @param array $foreignKeys
     * @param Table $table
     */
    private function addForeignKeys(array $foreignKeys, Table $table): void
    {
        if (count($foreignKeys) <= 0) {
            return;
        }

        foreach ($foreignKeys as $foreignKey) {
            $foreignKeyObj = $this->getModelFactory()->createForeignKey($foreignKey);
            $this->addVendor($foreignKey, $foreignKeyObj);
            $table->addForeignKey($foreignKeyObj);
        }
    }

    /**
     * @param array $indices
     * @param Table $table
     */
    private function addIndices(array $indices, Table $table): void
    {
        if (count($indices) <= 0) {
            return;
        }

        foreach ($indices as $index) {
            $indexObj = $this->getModelFactory()->createIndex($index);
            foreach ($index['index-columns'] as $indexColumn) {
                $column = $table->getColumn($indexColumn['name']);
                if (isset($indexColumn['size'])) {
                    $index->getColumnSizes()->set($column->getName(), $indexColumn['size']);
                }
                $indexObj->addColumn($column);
            }
            $this->addVendor($index, $indexObj);
            $table->addIndex($indexObj);
        }
    }

    /**
     * @param array $uniques
     * @param Table $table
     */
    private function addUniques(array $uniques, Table $table): void
    {
        if (count($uniques) <= 0) {
            return;
        }

        foreach ($uniques as $unique) {
            $uniqueObj = $this->getModelFactory()->createUnique($unique);
            foreach ($unique['unique-columns'] as $uniqueColumn) {
                $column = $table->getColumn($uniqueColumn['name']);
                if (isset($uniqueColumn['size'])) {
                    $column->setSize($uniqueColumn['size']);
                }
                $uniqueObj->addColumn($column);
            }
            $this->addVendor($unique, $uniqueObj);
            $table->addUnique($uniqueObj);
        }
    }

    /**
     * @param array $externalSchemas
     * @param Schema $schema
     */
    private function addExternalSchemas(array $externalSchemas, Schema $schema): void
    {
        if (count($externalSchemas) <= 0) {
            return;
        }

        foreach ($externalSchemas as $externalSchema) {
            $filename = $this->getExternalFilename($externalSchema['filename'], $schema);
            /** @var Schema $extSchema */
            $extSchema = $this->parse($filename);
            $extSchema->setReferenceOnly($externalSchema['referenceOnly']);
            $schema->addExternalSchema($extSchema);
        }
    }

    /**
     * @param array $behaviors
     * @param Database|Table $parent
     */
    private function addBehaviors(array $behaviors, $parent): void
    {
        if (count($behaviors) <= 0) {
            return;
        }

        foreach ($behaviors as $id => $behavior) {
            $behaviorObj = $this->getModelFactory()->createBehavior($behavior);
            $behaviorObj->setId($id);
            $parent->addBehavior($behaviorObj);
        }
    }

    /**
     * @param array $parent
     * @param Database|Table|Column|Index|Unique|ForeignKey $parentObj
     */
    private function addVendor(array $parent, $parentObj): void
    {
        if (!isset($parent['vendor'])) {
            return;
        }

        $obj = $this->getModelFactory()->createVendor($parent['vendor']);
        $parentObj->addVendor($obj);
    }

    /**
     * @param array $inheritances
     * @param Column $column
     */
    private function addInheritance(array $inheritances, Column $column): void
    {
        if (count($inheritances) <= 0) {
            return;
        }

        foreach ($inheritances as $inheritance) {
            $inheritObj = $this->getModelFactory()->createInheritance($inheritance);
            $column->addInheritance($inheritObj);
        }
    }

    /**
     * @param array $attributes
     * @param Table $table
     */
    private function addIdMethodParameter(array $attributes, Table $table): void
    {
        if (!isset($attributes['id_method_parameter'])) {
            return;
        }

        $idObj = $this->getModelFactory()->createIdMethodParameter($attributes['id_method_parameter']);
        $table->addIdMethodParameter($idObj);
    }

    /**
     * If the external schema filename is not an absolute path,
     * make it relative to the current schema directory.
     *
     * @param string $filename
     * @param Schema $schema
     *
     * @return string
     */
    private function getExternalFilename(string $filename, Schema $schema): string
    {
        $file = new File($filename);
        if (!$file->toPath()->isAbsolute()) {
            $schemaFile = new File($schema->getFilename());

            return $schemaFile->getDirname() . DIRECTORY_SEPARATOR . $file->getPathname();
        }

        return $file->getPathname()->toString();
    }
}
