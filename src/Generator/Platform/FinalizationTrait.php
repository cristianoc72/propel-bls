<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Platform;

use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Domain;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Schema;

/**
 * Trait FinalizationTrait
 *
 * Methods to perform the final initialization of model objects
 *
 */
trait FinalizationTrait
{
    /**
     * Do final initialization of the whole schema.
     *
     * @param Schema $schema
     */
    public function doFinalInitialization(Schema $schema)
    {
        foreach ($schema->getDatabases() as $database) {
            // execute database behaviors
            foreach ($database->getBehaviors() as $behavior) {
                $behavior->modifyDatabase();
            }
            // execute table behaviors (may add new tables and new behaviors)
            while ($behavior = $database->getNextTableBehavior()) {
                $behavior->getTableModifier()->modifyTable();
                $behavior->setTableModified(true);
            }

            $this->finalizeDefinition($database);
        }
    }

    /**
     * Finalize this table.
     *
     * @param Database $database
     */
    public function finalizeDefinition(Database $database)
    {
        foreach ($database->getTables() as $table) {

            // Heavy indexing must wait until after all columns composing
            // a table's primary key have been parsed.
            if ($table->isHeavyIndexing()) {
                $this->doHeavyIndexing($table);
            }

            // if idMethod is "native" and in fact there are no autoIncrement
            // columns in the table, then change it to "none"
            $anyAutoInc = false;
            foreach ($table->getColumns() as $column) {
                if ($column->isAutoIncrement()) {
                    $anyAutoInc = true;
                }
            }

            if (Model::ID_METHOD_NATIVE === $table->getIdMethod() && !$anyAutoInc) {
                $table->setIdMethod(Model::ID_METHOD_NONE);
            }

            $this->setupForeignKeyReferences($table);
            $this->setupReferrers($table);

            //MyISAM engine doesn't create foreign key indices automatically
            if ($this instanceof MysqlPlatform) {
                if ('MyISAM' === $this->getMysqlTableType($table)) {
                    $this->addExtraIndices($table);
                }
            }
        }
    }

    /**
     * Browses the foreign keys and creates referrers for the foreign table.
     * This method can be called several times on the same table. It only
     * adds the missing referrers and is non-destructive.
     * Warning: only use when all the tables were created.
     *
     * @param  Table $table
     *
     * @throws BuildException
     */
    protected function setupReferrers(Table $table)
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->setupReferrer($foreignKey);
        }
    }

    /**
     * @param ForeignKey $foreignKey
     */
    protected function setupReferrer(ForeignKey $foreignKey)
    {
        $table = $foreignKey->getTable();
        // table referrers
        $hasTable = $table->getDatabase()->hasTableByName($foreignKey->getForeignTableName()) ?
            true :
            $table->getDatabase()->hasTableByFullName($foreignKey->getForeignTableName())
        ;
        if (!$hasTable) {
            throw new BuildException(
                sprintf(
                    'Table "%s" contains a foreignKey to nonexistent table "%s". [%s]',
                    $table->getName(),
                    $foreignKey->getForeignTableName(),
                    implode(', ', $table->getDatabase()->getTableNames())
                )
            );
        }

        $foreignTable = $table->getDatabase()->getTableByName($foreignKey->getForeignTableName()) ??
            $table->getDatabase()->getTableByFullName($foreignKey->getForeignTableName())
        ;
        $referrers = $foreignTable->getReferrers();
        if (null === $referrers || !in_array($foreignKey, $referrers, true)) {
            $foreignTable->addReferrer($foreignKey);
        }

        // foreign pk's
        $localColumnNames = $foreignKey->getLocalColumns();
        foreach ($localColumnNames as $localColumnName) {
            $localColumn = $table->getColumnByName($localColumnName);
            if (null !== $localColumn) {
                if ($localColumn->isPrimaryKey() && !$table->getContainsForeignPK()) {
                    $table->setContainsForeignPK(true);
                }

                continue;
            }

            throw new BuildException(
                sprintf(
                    'Table "%s" contains a foreign key with nonexistent local column "%s"',
                    $table->getName(),
                    $localColumnName
                )
            );
        }

        // foreign column references
        $foreignColumns = $foreignKey->getForeignColumnObjects();
        foreach ($foreignColumns as $foreignColumn) {
            if (null === $foreignTable) {
                continue;
            }
            if (null !== $foreignColumn) {
                if (!$foreignColumn->hasReferrer($foreignKey)) {
                    $foreignColumn->addReferrer($foreignKey);
                }

                continue;
            }
            // if the foreign column does not exist, we may have an
            // external reference or a misspelling
            throw new BuildException(
                sprintf(
                    'Table "%s" contains a foreign key to table "%s" with nonexistent column "%s"',
                    $table->getName(),
                    $foreignTable->getName(),
                    $foreignColumn->getName()
                )
            );
        }
    }

    /**
     * @param Table $table
     */
    protected function setupForeignKeyReferences(Table $table)
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getColumn()) {
                $foreignKeyName = $foreignKey->getColumn();
            } else {
                $foreignKeyName = $foreignKey->getForeignTableName();
            }

            if (!$foreignKey->getLocalColumnObjects()) {
                //no references defined: set it
                $pks = $foreignKey->getForeignTable()->getPrimaryKey();
                if (!$pks) {
                    throw new BuildException(sprintf(
                        'Can not set up foreignKey references since target table `%s` has no primary keys.',
                        $foreignKey->getForeignTable()->getName()
                    ));
                }

                /** @var Column $pk */
                foreach ($pks as $pk) {
                    $localColumnName = lcfirst($foreignKeyName) . ucfirst($pk->getName());
                    $column = new Column();
                    $column->setName($localColumnName);
                    $column->setType($pk->getType());
                    $column->setDomain($pk->getDomain());

                    if ($table->hasColumn($localColumnName)) {
                        throw new BuildException(sprintf(
                            'Unable to setup automatic foreignKey from %s to %s due to no unique column name. Please specify <foreignKey column="here"> a name'
                        ), $table->getName(), $foreignKey->getForeignTable()->getName());
                    }
                    $table->addColumn($column);

                    $foreignKey->addReference($localColumnName, $pk->getName());
                }
            }
        }
    }

    /**
     * Adds extra indices for multi-part primary key columns.
     *
     * For databases like MySQL, values in a where clause much
     * match key part order from the left to right. So, in the key
     * definition <code>PRIMARY KEY (FOO_ID, BAR_ID)</code>,
     * <code>FOO_ID</code> <i>must</i> be the first element used in
     * the <code>where</code> clause of the SQL query used against
     * this table for the primary key index to be used. This feature
     * could cause problems under MySQL with heavily indexed tables,
     * as MySQL currently only supports 16 indices per table (i.e. it
     * might cause too many indices to be created).
     *
     * See the mysql manual http://www.mysql.com/doc/E/X/EXPLAIN.html
     * for a better description of why heavy indexing is useful for
     * quickly searchable database tables.
     *
     * @param Table $table
     */
    protected function doHeavyIndexing(Table $table)
    {
        //@todo refactor with collections
        $pk = $table->getPrimaryKey()->toArray();
        $size = count($pk);

        // We start at an offset of 1 because the entire column
        // list is generally implicitly indexed by the fact that
        // it's a primary key.
        for ($i = 1; $i < $size; $i++) {
            $idx = new Index();
            $idx->addColumns(array_slice($pk, $i, $size));
            $table->addIndex($idx);
        }
    }
}
