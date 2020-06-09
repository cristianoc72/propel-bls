<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model\Diff;

use Propel\Generator\Model\ForeignKey;

/**
 * Service class for comparing ForeignKey objects
 * Heavily inspired by Doctrine2's Migrations
 * (see http://github.com/doctrine/dbal/tree/master/lib/Doctrine/DBAL/Schema/)
 *
 */
class ForeignKeyComparator
{
    /**
     * Compute the difference between two Foreign key objects
     *
     * @param ForeignKey $fromFk
     * @param ForeignKey $toFk
     *
     * @return boolean false if the two fks are similar, true if they have differences
     */
    public static function computeDiff(ForeignKey $fromFk, ForeignKey $toFk)
    {
        if ($fromFk->getTableName() !== $toFk->getTableName()) {
            return true;
        }

        if ($fromFk->getForeignTableName() !== $toFk->getForeignTableName()) {
            return true;
        }

        // compare columns
        $fromFkLocalColumns = $fromFk->getLocalColumns();
        $fromFkLocalColumns = $fromFkLocalColumns->sort();
        $toFkLocalColumns = $toFk->getLocalColumns();
        $toFkLocalColumns = $toFkLocalColumns->sort();
        //Why case insensitive comparison?
        $fromFkLocalColumns = $fromFkLocalColumns->map(function (string $element) {
            return strtolower($element);
        });
        $toFkLocalColumns = $toFkLocalColumns->map(function (string $element) {
            return strtolower($element);
        });

        if ($fromFkLocalColumns !== $toFkLocalColumns) {
            return true;
        }

        $fromFkForeignColumns = $fromFk->getForeignColumns();
        $fromFkForeignColumns = $fromFkForeignColumns->sort();
        $toFkForeignColumns = $toFk->getForeignColumns();
        $toFkForeignColumns = $toFkForeignColumns->sort();
        //Why case insensitive comparison?
        $fromFkForeignColumns = $fromFkForeignColumns->map(function (string $element) {
            return strtolower($element);
        });
        $toFkForeignColumns = $toFkForeignColumns->map(function (string $element) {
            return strtolower($element);
        });



        if ($fromFkForeignColumns !== $toFkForeignColumns) {
            return true;
        }

        // compare on
        if ($fromFk->normalizeFKey($fromFk->getOnUpdate()) !== $toFk->normalizeFKey($toFk->getOnUpdate())) {
            return true;
        }
        if ($fromFk->normalizeFKey($fromFk->getOnDelete()) !== $toFk->normalizeFKey($toFk->getOnDelete())) {
            return true;
        }

        // compare skipSql
        return $fromFk->isSkipSql() !== $toFk->isSkipSql();
    }
}
