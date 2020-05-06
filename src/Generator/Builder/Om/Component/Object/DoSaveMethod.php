<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;

use gossi\docblock\tags\SeeTag;
use gossi\docblock\tags\ThrowsTag;
use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\CrossForeignKeyTrait;
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Model\CrossForeignKey;
use Propel\Generator\Model\ForeignKey;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

class DoSaveMethod extends BuildComponent
{
    use NamingTrait, SimpleTemplateTrait, ForeignKeyTrait, CrossForeignKeyTrait;

    /**
     * @throws \ReflectionException
     * @throws \Twig\Error\Error
     */
    public function process(): void
    {
        $reloadOnInsert = $this->getTable()->isReloadOnInsert();
        $reloadOnUpdate = $this->getTable()->isReloadOnUpdate();

        $this->useClass(ConnectionInterface::class);
        $this->useClass($this->getTableMapClassName(true));
        $exception = $this->useClass(PropelException::class);

        $method = $this->addMethod('doDelete')
            ->setType('int', 'The number of rows affected by this insert/update and any referring fk objects save() operations.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Optional connection object', null)
            ->addSimpleDescParameter('skipReload', 'bool', 'Whether to skip the reload for this object from database.',
                $reloadOnInsert | $reloadOnUpdate ? false : true
            )
            ->setMultilineDescription([
                'Performs the work of inserting or updating the row in the database.',
                '',
                'If the object is new, it inserts it; otherwise an update is performed.',
                'All related objects are also updated in this method.'
            ])
        ;

        $docblock = $method->getDocblock()
            ->appendTag(ThrowsTag::create($exception))
            ->appendTag(SeeTag::create("save()"))
        ;
        $method->setDocblock($docblock);

        $method->setBody($this->renderTemplate([
            'reloadOnInsert' => $reloadOnInsert,
            'reloadOnUpdate' => $reloadOnUpdate,
            'tableMap' => $this->getTableMapClassName(),
            'columns' => $this->getTable()->getColumns(),
            'foreignKeys' => $this->getTable()->getForeignKeys()->each(function(ForeignKey $element) {
                return $this->getForeignKeyVarName($element);
            }),
            'referrerPks' => $this->getTable()->getReferrers()->each(function(ForeignKey $element) {
                if ($element->isLocalPrimaryKey()) {
                    return $this->getRefForeignKeyVarName($element);
                }
            }),
            'referrers' => $this->getTable()->getReferrers()->each(function(ForeignKey $element) {
                if (!$element->isLocalPrimaryKey()) {
                    return $this->getRefForeignKeyCollVarName($element);
                }
            }),
            'crossForeignKeys' => $this->getTable()->getCrossForeignKeys()->each(function(CrossForeignKey $element) {
                return $this->getCrossForeignKeysVarName($element);
            })
        ]));
    }
}
