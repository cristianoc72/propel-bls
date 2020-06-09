<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use phootwork\collection\Map;
use Propel\Generator\Model\Column;

/**
 * Service class for comparing Column objects.
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 */
class ColumnComparator
{
    /**
     * Compute and return the difference between two column objects
     *
     * @param  Column             $fromColumn
     * @param  Column             $toColumn
     * @return ColumnDiff|boolean return false if the two columns are similar
     */
    public static function computeDiff(Column $fromColumn, Column $toColumn): ?ColumnDiff
    {
        $changedProperties = self::comparecolumns($fromColumn, $toColumn);
        if (!$changedProperties->isEmpty()) {
            $platform = $fromColumn->getPlatform() ?? $toColumn->getPlatform();
            if (null !== $platform) {
                if ($platform->getcolumnDDL($fromColumn) == $platform->getcolumnDDl($toColumn)) {
                    return null;
                }
            }

            $columnDiff = new ColumnDiff($fromColumn, $toColumn);
            $columnDiff->setChangedProperties($changedProperties);

            return $columnDiff;
        }

        return null;
    }

    /**
     * @param Column $fromColumn
     * @param Column $toColumn
     *
     * @return Map
     */
    public static function comparecolumns(Column $fromColumn, Column $toColumn): Map
    {
        $changedProperties = new Map();

        // compare column types
        $fromDomain = $fromColumn->getDomain();
        $toDomain = $toColumn->getDomain();

        if ($fromDomain->getScale() !== $toDomain->getScale()) {
            $changedProperties->set('scale', [$fromDomain->getScale(), $toDomain->getScale()]);
        }
        if ($fromDomain->getSize() !== $toDomain->getSize()) {
            $changedProperties->set('size', [$fromDomain->getSize(), $toDomain->getSize()]);
        }

        if (strtoupper($fromDomain->getSqlType() ?? '') !== strtoupper($toDomain->getSqlType() ?? '')) {
            $changedProperties->set('sqlType', [$fromDomain->getSqlType(), $toDomain->getSqlType()]);

            if ($fromDomain->getType() !== $toDomain->getType()) {
                $changedProperties->set('type', [$fromDomain->getType(), $toDomain->getType()]);
            }
        }

        if ($fromColumn->isNotNull() !== $toColumn->isNotNull()) {
            $changedProperties->set('notNull', [$fromColumn->isNotNull(), $toColumn->isNotNull()]);
        }

        // compare column default value
        $fromDefaultValue = $fromColumn->getDefaultValue();
        $toDefaultValue = $toColumn->getDefaultValue();
        if ($fromDefaultValue && !$toDefaultValue) {
            $changedProperties->set('defaultValueType', [$fromDefaultValue->getType(), null]);
            $changedProperties->set('defaultValueValue', [$fromDefaultValue->getValue(), null]);
        } elseif (!$fromDefaultValue && $toDefaultValue) {
            $changedProperties->set('defaultValueType', [null, $toDefaultValue->getType()]);
            $changedProperties->set('defaultValueValue', [null, $toDefaultValue->getValue()]);
        } elseif ($fromDefaultValue && $toDefaultValue) {
            if (!$fromDefaultValue->equals($toDefaultValue)) {
                if ($fromDefaultValue->getType() !== $toDefaultValue->getType()) {
                    $changedProperties->set('defaultValueType', [$fromDefaultValue->getType(), $toDefaultValue->getType()]);
                }
                if ($fromDefaultValue->getValue() !== $toDefaultValue->getValue()) {
                    $changedProperties->set('defaultValueValue', [$fromDefaultValue->getValue(), $toDefaultValue->getValue()]);
                }
            }
        }

        if ($fromColumn->isAutoIncrement() !== $toColumn->isAutoIncrement()) {
            $changedProperties->set('autoIncrement', [$fromColumn->isAutoIncrement(), $toColumn->isAutoIncrement()]);
        }

        return $changedProperties;
    }
}
