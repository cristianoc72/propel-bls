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
use Propel\Generator\Builder\Om\Component\BuildComponent;

class LogMethod extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('log')
            ->setType('bool')
            ->setDescription('Logs a message using Propel::log().')
            ->addParameter(PhpParameter::create('message')->setType('string'))
            ->addParameter(PhpParameter::create('priority')->setType('int')->setDescription('One of the Propel::LOG_* logging levels'))
            ->setBody('return Propel::log(get_class($this) . \': \' . $msg, $priority);')
        ;
    }
}
