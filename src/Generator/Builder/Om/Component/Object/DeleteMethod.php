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

class DeleteMethod extends BuildComponent
{
    use NamingTrait;
    use SimpleTemplateTrait;

    public function process(): void
    {
        $method = $this->addMethod('delete')
            ->setType('void')
            ->setDescription('Removes this object from datastore and sets delete attribute.')
            ->addSimpleParameter('con', ConnectionInterface::class, null)
            ;

        $this->useClass(ConnectionInterface::class);
        $this->useClass($this->getTableMapClassName(true));
        $this->useClass($this->getQueryClassName(true));
        $exception = $this->useClass(PropelException::class);
        $docblock = $method->getDocblock()
            ->appendTag(ThrowsTag::create($exception))
            ->appendTag(SeeTag::create("{$this->getObjectClassName()}::setDeleted()"))
            ->appendTag(SeeTag::create("{$this->getObjectClassName()}::isDeleted()"))
        ;
        $method->setDocblock($docblock);
        $method->setBody($this->renderTemplate([
                    'tableMap' => $this->getTableMapClassName(),
                    'query'    => $this->getQueryClassName()
                ]
        ));
    }
}
