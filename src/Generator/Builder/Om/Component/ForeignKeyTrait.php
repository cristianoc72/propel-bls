<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component;

use phootwork\lang\Text;
use Propel\Generator\Exception\BuildException;
use Propel\Generator\Model\ForeignKey;
use Susina\Codegen\Model\PhpConstant;

trait ForeignKeyTrait
{
    /**
     * Gets the PHP name for the given foreignKey.
     *
     * @param  ForeignKey $foreignKey The local ForeignKey that we need a name for.
     * @param  boolean  $plural   Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     *
     * @return Text
     */
    public function getForeignKeyPhpName(ForeignKey $foreignKey, bool $plural = false): Text
    {
        if (!$foreignKey->getPhpName()->isEmpty()) {
            return $foreignKey->getPhpName();
        }

        $fkName = new Text($foreignKey->getColumn());
        if (!$fkName->isEmpty()) {
            if ($plural) {
                $fkName = $fkName->toPlural();
            }

            return $fkName->toUpperCaseFirst();
        }

        if ($foreignKey->hasName()) {
            if ($plural) {
                return $foreignKey->getName()->toPlural()->toUpperCaseFirst();
            }

            return $foreignKey->getName()->toUpperCaseFirst();
        }

        $className = $foreignKey->getForeignTable()->getName();
        if ($plural) {
            $className = $className->toPlural();
        }

        return $className->append($this->getRelatedBySuffix($foreignKey));
    }

    /**
     * @param ForeignKey $fk
     * @param bool $plural
     *
     * @return Text
     * @deprecated Use getForeignKeyPhpName instead
     */
    public function getFKPhpNameAffix(ForeignKey $fk, $plural = false): Text
    {
        return $this->getForeignKeyPhpName($fk, $plural);
    }

    /**
     * Convenience method to get the default Join Type for a foreignKey.
     * If the key is required, an INNER JOIN will be returned, else a LEFT JOIN will be suggested,
     * unless the schema is provided with the DefaultJoin attribute, which overrules the default Join Type
     *
     * @param  ForeignKey $foreignKey
     * @return string|PhpConstant
     */
    protected function getJoinType(ForeignKey $foreignKey)
    {
        if ($defaultJoin = $foreignKey->getDefaultJoin()) {
            return "'" . $defaultJoin . "'";
        }

        if ($foreignKey->isLocalColumnsRequired()) {
            return PhpConstant::create('Criteria::INNER_JOIN');
        }

        return PhpConstant::create('Criteria::LEFT_JOIN');
    }

    /**
     * Gets the "RelatedBy*" suffix (if needed) that is attached to method and variable names.
     *
     * The related by suffix is based on the local columns of the foreign key.  If there is more than
     * one column in a table that points to the same foreign table, then a 'RelatedByLocalColName' suffix
     * will be appended.
     *
     * @param ForeignKey $foreignKey
     *
     * @throws BuildException
     * @return string
     */
    protected function getRelatedBySuffix(ForeignKey $foreignKey): string
    {
        $relColumn = '';
        foreach ($foreignKey->getLocalForeignMapping() as $localColumnName => $foreignColumnName) {
            $localTable = $foreignKey->getTable();
            $localColumn = $localTable->getColumn($localColumnName);
            if (!$localColumn) {
                throw new BuildException(
                    sprintf('Could not fetch column: %s in table %s.', $localColumnName, $localTable->getName())
                );
            }

            if ($localTable->getForeignKeysReferencingTable($foreignKey->getForeignTableName())->size() > 1
                || $foreignKey->getForeignTable()->getForeignKeysReferencingTable($foreignKey->getTableName())->size() > 0
                || $foreignKey->getForeignTableName() == $foreignKey->getTableName()
            ) {
                // self referential foreign key, or several foreign keys to the same table, or cross-reference fkey
                $relColumn .= $localColumn->getName();
            }
        }

        if (!empty($relColumn)) {
            $relColumn = 'RelatedBy' . $relColumn;
        }

        return $relColumn;
    }

    /**
     * Constructs variable name for fkey-related objects.
     *
     * @param  ForeignKey $foreignKey
     * @param  boolean  $plural
     *
     * @return Text
     */
    public function getForeignKeyVarName(ForeignKey $foreignKey, bool $plural = false): Text
    {
        return $this->getForeignKeyPhpName($foreignKey, $plural)->toLowerCaseFirst();
    }

    /**
     * @param ForeignKey $foreignKey
     * @param bool     $plural
     *
     * @return Text
     */
    public function getRefForeignKeyVarName(ForeignKey $foreignKey, bool $plural = false): Text
    {
        return $this->getRefForeignKeyPhpName($foreignKey, $plural)->toLowerCaseFirst();
    }

    /**
     * Constructs variable name for single object which references current table by specified foreign key
     * which is ALSO a primary key (hence one-to-one foreignKeyship).
     *
     * @param  ForeignKey $foreignKey
     *
     * @return Text
     */
    public function getPKRefForeignKeyVarName(ForeignKey $foreignKey): Text
    {
        return $this->getRefForeignKeyPhpName($foreignKey, false)->toLowerCaseFirst();
    }

    /**
     * Gets the PHP  name affix to be used for referencing foreignKey.
     *
     * @param  ForeignKey $foreignKey The referrer ForeignKey that we need a name for.
     * @param  boolean  $plural   Whether the php name should be plural (e.g. initRelatedObjs() vs. addRelatedObj()
     *
     * @return Text
     */
    public function getRefForeignKeyPhpName(ForeignKey $foreignKey, bool $plural = false): Text
    {
        $fkRefColumn = new Text($foreignKey->getRefColumn());

        if (!$fkRefColumn->isEmpty()) {
            if ($plural) {
                return $fkRefColumn->toPlural()->toUpperCaseFirst();
            }

            return $fkRefColumn->toUpperCaseFirst();
        }

        $className = $foreignKey->getTable()->getName();
        if ($plural) {
            $className = $className->toPlural();
        }

        //since we have no refColumn name we need to generate one based on the table.
        //this can go wrong when we have two foreignKeys to the same table, as it would generate
        //same foreignKey name again. so we need to affix the name with the actual $foreignKey->getColumn()
        //this is what getRefRelatedBySuffix is doing.

        return $className->append($this->getRefRelatedBySuffix($foreignKey))->toUpperCaseFirst();
    }

    /**
     * Constructs variable name for objects which referencing current table by specified foreign key.
     *
     * @param  ForeignKey $foreignKey
     *
     * @return Text
     */
    public function getRefForeignKeyCollVarName(ForeignKey $foreignKey): Text
    {
        return $this->getRefForeignKeyPhpName($foreignKey, true)->toLowerCaseFirst();
    }

    /**
     * Returns a prefix 'RelatedBy*' if needed.
     *
     * @param ForeignKey $foreignKey
     *
     * @return string
     */
    protected static function getRefRelatedBySuffix(ForeignKey $foreignKey): string
    {
        $hasOtherForeignKeyToSameTableAndName = false;
        foreach ($foreignKey->getTable()->getForeignKeys() as $otherForeignKey) {
            if ($otherForeignKey !== $foreignKey && $otherForeignKey->getForeignTable() === $foreignKey->getForeignTable()) {
                $hasOtherForeignKeyToSameTableAndName = true;
                break;
            }
        }

        if (!$hasOtherForeignKeyToSameTableAndName) {
            return '';
        }

        return 'By' . ucfirst($foreignKey->getColumn());
    }
}
