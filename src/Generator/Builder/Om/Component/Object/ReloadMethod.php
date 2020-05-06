<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


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

class ReloadMethod extends BuildComponent
{
    use ForeignKeyTrait, NamingTrait, SimpleTemplateTrait, CrossForeignKeyTrait;

    /**
     * @throws \ReflectionException
     * @throws \Twig\Error\Error
     */
    public function process(): void
    {
        $context = [
            'tableMapName' => $this->useClass($this->getTableMapClassName(true)),
            'queryName' => $this->useClass($this->getQueryClassName(true)),
            'columns' => $this->getTable()->getColumns()->toArray(),
            'foreignKeys' => $this->getTable()->getForeignKeys()->each(function(ForeignKey $element) {
                return $this->getForeignKeyVarName($element);
            }),
            'referrers' => $this->getTable()->getReferrers()->each(function(ForeignKey $element) {
                return $element->isLocalPrimaryKey() ?
                    $this->getRefForeignKeyVarName($element) :
                    $this->getRefForeignKeyCollVarName($element);
            }),
            'crossForeignKeys' => $this->getTable()->getCrossForeignKeys()->each(function(CrossForeignKey $element) {
                return $this->getCrossForeignKeysVarName($element);
            })
        ];

        $method = $this->addMethod('reload')
            ->setType('void')
            ->setMultilineDescription([
                'Reloads this object from datastore based on primary key and (optionally) resets all associated objects.',
                '',
                'This will only work if the object has been saved and has a valid primary key set.'
            ])
            ->addSimpleDescParameter('deep', 'bool', '(optional) Whether to also de-associated any related objects.', false)
            ->addSimpleDescParameter('con', 'ConnectionInterface', '(optional) The ConnectionInterface connection to use.', null)
            ->setBody($this->renderTemplate($context))
        ;

        $this->useClass(ConnectionInterface::class);
        $exception = $this->useClass(PropelException::class);

        $method->setDocblock($method->getDocblock()->appendTag(ThrowsTag::create($exception)));
    }
}
