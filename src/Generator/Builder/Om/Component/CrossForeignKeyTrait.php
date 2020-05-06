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

use cristianoc72\codegen\model\PhpParameter;
use phootwork\lang\ArrayObject;
use phootwork\lang\Text;
use Propel\Generator\Model\CrossForeignKey;
use Propel\Generator\Model\ForeignKey;

/**
 * This trait provied usefull helper methods for handling cross foreignKeys (CrossForeignKey).
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
trait CrossForeignKeyTrait
{
    use ForeignKeyTrait;
    use NamingTrait;

    /**
     * @param CrossForeignKey $crossForeignKeys
     *
     * @return array
     */
    protected function getCrossForeignKeysInformation(CrossForeignKey $crossForeignKeys): array
    {
        $names = [];
        $signatures = [];
        $shortSignature = [];
        $phpDoc = [];

        foreach ($crossForeignKeys->getForeignKeys() as $foreignKey) {
            $crossObjectName = '$' . $this->getForeignKeyVarName($foreignKey);
            $crossObjectClassName = $this
                ->getBuilder()
                ->getNewBuilder($foreignKey->getForeignTable(), 'Object')
                ->getClassName();

            $names[] = $crossObjectClassName;
            $signatures[] = "$crossObjectClassName $crossObjectName"
                . ($foreignKey->isAtLeastOneLocalColumnRequired() ? '' : ' = null');

            $shortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName The object to relate";
        }

        $names = implode(', ', $names) . (1 < count($names) ? ' combination' : '');
        $phpDoc = implode($phpDoc);
        $signatures = implode(', ', $signatures);
        $shortSignature = implode(', ', $shortSignature);

        return [
            $names,
            $phpDoc,
            $signatures,
            $shortSignature
        ];
    }

    /**
     * @param  CrossForeignKey $crossForeignKeys
     *
     * @return Text
     * @deprecated use getForeignKeyVarName instead with $crossForeignKeys->getOutgoingForeignKey()
     *
     */
    protected function getCrossForeignKeysVarName(CrossForeignKey $crossForeignKeys, bool $plural = true): Text
    {
        return $this->getCrossForeignKeysPhpName($crossForeignKeys, $plural)->toLowerCaseFirst();
    }

    /**
     * @param CrossForeignKey $crossForeignKeys
     * @param  bool          $plural
     *
     * @return Text
     *@deprecated use getForeignKeyPhpName instead with $crossForeignKeys->getOutgoingForeignKey()
     *
     */
    protected function getCrossForeignKeysPhpName(CrossForeignKey $crossForeignKeys, bool $plural = true): Text
    {
        /**
         * @var ArrayObject $names A collection of Text objects
         */
        $names = new ArrayObject();

        if ($plural) {
            if ($pks = $crossForeignKeys->getUnclassifiedPrimaryKeys()) {
                //we have a non fk as pk as well, so we need to make pluralisation on our own and can't
                //rely on getForeignKeyPhpName`s pluralisation
                foreach ($crossForeignKeys->getForeignKeys() as $foreignKey) {
                    $names[] = $this->getForeignKeyPhpName($foreignKey, false);
                }
            } else {
                //we have only fks, so give us names with plural and return those
                $lastIdx = count($crossForeignKeys->getForeignKeys()) - 1;
                foreach ($crossForeignKeys->getForeignKeys() as $idx => $foreignKey) {
                    $needPlural = $idx === $lastIdx; //only last fk should be plural
                    $names[] = $this->getForeignKeyPhpName($foreignKey, $needPlural);
                }

                return $names->join();
            }
        } else {
            // no plural, so $plural=false
            foreach ($crossForeignKeys->getForeignKeys() as $foreignKey) {
                $names[] = $this->getForeignKeyPhpName($foreignKey, false);
            }
        }

        foreach ($crossForeignKeys->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        $name = $names->join();

        return (true === $plural ? $name->toPlural() : $name);
    }

    /**
     * Returns the foreignKey name for a foreignKey of a CrossForeignKey.
     *
     * @param ForeignKey $foreignKey
     *
     * @return Text
     */
    protected function getCrossForeignKeysForeignKeyVarName(ForeignKey $foreignKey): Text
    {
        return $this->getForeignKeyVarName($foreignKey, true);
    }

    /**
     * @param CrossForeignKey $crossForeignKeys
     * @param  ForeignKey      $excludeForeignKey
     *
     * @return Text
     */
    protected function getCrossRefForeignKeyGetterName(CrossForeignKey $crossForeignKeys, ForeignKey $excludeForeignKey): Text
    {
        /**
         * @var ArrayObject $name A collection of Text objects
         */
        $names = new ArrayObject();

        $fks = $crossForeignKeys->getForeignKeys();

        foreach ($crossForeignKeys->getMiddleTable()->getForeignKeys() as $foreignKey) {
            if ($foreignKey !== $excludeForeignKey && ($foreignKey === $crossForeignKeys->getIncomingForeignKey() || in_array(
                $foreignKey,
                $fks
                    ))
            ) {
                $names[] = $this->getForeignKeyPhpName($foreignKey, false);
            }
        }

        foreach ($crossForeignKeys->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName();
        }

        return $names->join()->toPlural();
    }

    /**
     * Returns a function signature comma separated.
     *
     * @param CrossForeignKey $crossForeignKeys
     * @param  string        $excludeSignatureItem Which variable to exclude.
     *
     * @return string
     */
    protected function getCrossFKGetterSignature(CrossForeignKey $crossForeignKeys, string $excludeSignatureItem): string
    {
        list(, $getSignature) = $this->getCrossForeignKeysAddMethodInformation($crossForeignKeys);
        $getSignature = explode(', ', $getSignature);

        if (false !== ($pos = array_search($excludeSignatureItem, $getSignature))) {
            unset($getSignature[$pos]);
        }

        return implode(', ', $getSignature);
    }

    /**
     * @param CrossForeignKey $crossForeignKeys
     * @param  ForeignKey      $excludeForeignKey
     *
     * @return Text
     */
    protected function getCrossRefFKRemoveObjectNames(CrossForeignKey $crossForeignKeys, ForeignKey $excludeForeignKey): Text
    {
        $names = new ArrayObject();

        $fks = $crossForeignKeys->getForeignKeys();

        foreach ($crossForeignKeys->getMiddleTable()->getForeignKeys() as $foreignKey) {
            if ($foreignKey !== $excludeForeignKey && ($foreignKey === $crossForeignKeys->getIncomingForeignKey() || in_array(
                $foreignKey,
                $fks
                    ))
            ) {
                if ($foreignKey === $crossForeignKeys->getIncomingForeignKey()) {
                    $names[] = '$this';
                } else {
                    $names[] = $this->getForeignKeyPhpName($foreignKey, false)->toLowerCaseFirst()->prepend('$');
                }
            }
        }

        foreach ($crossForeignKeys->getUnclassifiedPrimaryKeys() as $pk) {
            $names[] = $pk->getName()->toLowerCaseFirst()->prepend('$');
        }

        return $names->join(', ');
    }

    /**
     * Extracts some useful information from a CrossForeignKeyss object.
     *
     * @param CrossForeignKey  $crossForeignKeys
     * @param array|ForeignKey $foreignKeyToIgnore
     * @param PhpParameter[] $signature
     * @param array          $shortSignature
     * @param array          $normalizedShortSignature
     * @param array          $phpDoc
     */
    protected function extractCrossInformation(
        CrossForeignKey $crossForeignKeys,
        $foreignKeyToIgnore = null,
        &$signature,
        &$shortSignature,
        &$normalizedShortSignature,
        &$phpDoc
    ) {
        foreach ($crossForeignKeys->getForeignKeys() as $fk) {
            if (is_array($foreignKeyToIgnore) && in_array($fk, $foreignKeyToIgnore)) {
                continue;
            } else {
                if ($fk === $foreignKeyToIgnore) {
                    continue;
                }
            }

            $phpType = $typeHint = $this->getClassNameFromTable($fk->getForeignTable());
            $name = $this->getForeignKeyPhpName($fk)->toLowerCaseFirst()->prepend('$');

            $normalizedShortSignature[] = $name;

            $parameter = new PhpParameter($this->getForeignKeyPhpName($fk)->toLowerCaseFirst()->toString());
            if ($typeHint) {
                $parameter->setType($typeHint);
            }
            $signature[] = $parameter;

            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }

        foreach ($crossForeignKeys->getUnclassifiedPrimaryKeys() as $primaryKey) {
            //we need to add all those $primaryKey s as additional parameter as they are needed
            //to create the entry in the middle-table.
            $defaultValue = $primaryKey->getDefaultValueString();

            $phpType = $primaryKey->getPhpType();
            $typeHint = $primaryKey->isPhpArrayType() ? 'array' : '';
            $name = $primaryKey->getName()->toLowerCaseFirst()->prepend('$');

            $normalizedShortSignature[] = $name;


            $parameter = new PhpParameter($primaryKey->getName()->toLowerCaseFirst()->toString());
            if ($typeHint) {
                $parameter->setType($typeHint);
            }
            if ('null' !== $defaultValue) {
                $parameter->setValue($defaultValue);
            }
            $signature[] = $parameter;

            $shortSignature[] = $name;
            $phpDoc[] = "
     * @param $phpType $name";
        }
    }

    /**
     * @param CrossForeignKey  $crossForeignKeys
     * @param  array|ForeignKey $foreignKey will be the first variable defined
     *
     * @return array [$signature, $shortSignature, $normalizedShortSignature, $phpDoc]
     */
    protected function getCrossForeignKeysAddMethodInformation(CrossForeignKey $crossForeignKeys, $foreignKey = null): array
    {
        if ($foreignKey instanceof ForeignKey) {
            $crossObjectName = '$' . $this->getForeignKeyVarName($foreignKey);
            $crossObjectClassName = $this->getClassNameFromTable($foreignKey->getForeignTable());

            $parameter = new PhpParameter($this->getForeignKeyVarName($foreignKey)->toString());
            $parameter->setType($crossObjectClassName);
            if ($foreignKey->isAtLeastOneLocalColumnRequired()) {
                $parameter->setValue(null);
            }
            $signature[] = $parameter;

            $shortSignature[] = $crossObjectName;
            $normalizedShortSignature[] = $crossObjectName;
            $phpDoc[] = "
     * @param $crossObjectClassName $crossObjectName";
        }

        $this->extractCrossInformation(
            $crossForeignKeys,
            $foreignKey,
            $signature,
            $shortSignature,
            $normalizedShortSignature,
            $phpDoc
        );

        $shortSignature = implode(', ', $shortSignature);
        $normalizedShortSignature = implode(', ', $normalizedShortSignature);
        $phpDoc = implode(', ', $phpDoc);

        return [$signature, $shortSignature, $normalizedShortSignature, $phpDoc];
    }

    protected function getCrossScheduledForDeletionVarName(CrossForeignKey $crossFKs): string
    {
        if (1 < count($crossFKs->getForeignKeys()) || $crossFKs->getUnclassifiedPrimaryKeys()) {
            return 'combination' . $this->getCrossForeignKeysVarName($crossFKs)->toUpperCaseFirst() . "ScheduledForDeletion";
        } else {
            return "{$this->getForeignKeyPhpName($crossFKs->getForeignKeys()[0], true)->toLowerCaseFirst()}ScheduledForDeletion";
        }
    }
}
