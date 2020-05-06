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
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

class SaveMethod extends BuildComponent
{
    use SimpleTemplateTrait;
    use NamingTrait;

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

        $method = $this->addMethod('delete')
            ->setType('int', 'The number of rows affected by this insert/update and any referring fk objects\' save() operations.')
            ->addSimpleDescParameter('con', 'ConnectionInterface', 'Optional connection object', null)
            ->addSimpleDescParameter('skipReload', 'bool', 'Whether to skip the reload for this object from database.',
                $reloadOnInsert | $reloadOnUpdate ? false : true
            )
            ->setDescription($this->renderTemplate([
                    'reloadOnInsert' => $reloadOnInsert,
                    'reloadOnUpdate' => $reloadOnUpdate
                ], "partials/save_description.twig")
            )
        ;

        $docblock = $method->getDocblock()
            ->appendTag(ThrowsTag::create($exception))
            ->appendTag(SeeTag::create("doSave()"))
        ;
        $method->setDocblock($docblock);

        $method->setBody($this->renderTemplate([
            'reloadOnInsert' => $reloadOnInsert,
            'reloadOnUpdate' => $reloadOnUpdate,
            'tableMap' => $this->getTableMapClassName()
        ]));
    }
}
