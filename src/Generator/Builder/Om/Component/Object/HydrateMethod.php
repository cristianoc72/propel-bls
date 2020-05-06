<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;

use cristianoc72\codegen\model\PhpParameter;
use gossi\docblock\tags\ThrowsTag;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MysqlPlatform;
use Propel\Generator\Platform\OraclePlatform;
use Propel\Runtime\Exception\PropelException;

class HydrateMethod extends BuildComponent
{
    use NamingTrait, SimpleTemplateTrait;

    /**
     * @throws \ReflectionException
     * @throws \Twig\Error\Error
     */
    public function process(): void
    {
        $context = [
            'columns' => $this->getBuilder()->getTable()->getColumns(),
            'tableMapName' => $this->useClass($this->getTableMapClassName(true)),
            'isOracle' => ($this->getPlatform() instanceof OraclePlatform),
            'clob_emu' => PropelTypes::CLOB_EMU,
            'hasStreamBlob' => $this->getPlatform()->hasStreamBlobImpl(),
            'isMysql' => ($this->getPlatform() instanceof MysqlPlatform),
            'dateTimeClass' => $this->useClass($this->getBuilder()->getBuildProperty('generator.dateTime.DateTimeClass')),
            'isAddSaveMethod' => $this->getBuilder()->getBuildProperty("generator.objectModel.addSaveMethod"),
            'populatedObject' => var_export($this->getBuilder()->getStubObjectBuilder()->getClassName(), true)
        ];

        $method = $this->addMethod('hydrate')
            ->setType('int', 'next starting column')
            ->setMultilineDescription([
                'Hydrates (populates) the object variables with values from the database resultset.',
                '',
                'An offset (0-based "start column") is specified so that objects can be hydrated',
                'with a subset of the columns in the resultset rows.  This is needed, for example,',
                'for results of JOIN queries where the resultset row includes columns from two or',
                'more tables.'
            ])
            ->addSimpleDescParameter('row', 'array', 'The row returned by DataFetcher->fetch().')
            ->addSimpleDescParameter('startcol', 'int','0-based offset column which indicates which resultset column to start with.', 0)
            ->addSimpleDescParameter('rehydrate','bool', 'Whether this object is being re-hydrated from the database.', false)
            ->addParameter(PhpParameter::create('indexType')
                ->setType('string')
                ->setExpression('TableMap::TYPE_NUM')
                ->setMultilineDescription([
                    'The index type of $row. Mostly DataFetcher->getIndexType().',
                    'One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME',
                    'TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.'
                ])
            )
            ->setBody($this->renderTemplate($context));

        $method->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create(PropelException::class)
            ->setDescription('Any caught Exception will be rewrapped as a PropelException.')))
        ;
    }
}