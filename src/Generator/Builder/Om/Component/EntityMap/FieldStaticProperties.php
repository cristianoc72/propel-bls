<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use gossi\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Model\NamingTool;
use Propel\Runtime\Map\EntityMap;

/**
 * Adds fieldNames & fieldKeys properties.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class FieldStaticProperties extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $entity = $this->getEntity();

        $phpNames = $fullColNames = $colNames = $fieldNames = $nums = $numStrings = [];

        foreach ($entity->getFields() as $idx => $field) {
            $phpNames[] = NamingTool::toUpperCamelCase($field->getName());
            $colNames[] = $field->getColumnName();
            $fullColNames[] = $entity->getTableName() . '.' . $field->getColumnName();
            $fieldNames[] = $field->getName();
            $nums[] = $idx;
            $numStrings[$field->getName()] = $idx;
        }

        $fieldNamesProperty = [
            EntityMap::TYPE_PHPNAME => $phpNames,
            EntityMap::TYPE_COLNAME => $colNames,
            EntityMap::TYPE_FULLCOLNAME => $fullColNames,
            EntityMap::TYPE_FIELDNAME => $fieldNames,
            EntityMap::TYPE_NUM => $nums,
        ];

        $fieldKeysProperty = [
            EntityMap::TYPE_PHPNAME => array_flip($phpNames),
            EntityMap::TYPE_COLNAME => array_flip($colNames),
            EntityMap::TYPE_FULLCOLNAME => array_flip($fullColNames),
            EntityMap::TYPE_FIELDNAME => array_flip($fieldNames),
            EntityMap::TYPE_NUM => array_flip($nums),
        ];

        $constant = new PhpProperty('fieldNames');
        $constant->setExpression(var_export($fieldNamesProperty, true));
        $this->getDefinition()->setProperty($constant);

        $constant = new PhpProperty('fieldKeys');
        $constant->setExpression(var_export($fieldKeysProperty, true));
        $this->getDefinition()->setProperty($constant);
    }
}
