<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\PhpModel;

use cristianoc72\codegen\model\PhpClass;

class ClassDefinition extends PhpClass
{
    /** @var string */
    protected $constructorBodyExtras = '';

    /**
     * Add some code to the body of the class constructor, without overwrite it.
     *
     * @param string $code
     */
    public function addConstructorBody(string $code): void
    {
        $this->constructorBodyExtras .= "\n$code";
    }

    /**
     * @return string
     */
    public function getConstructorBodyExtras(): string
    {
        return $this->constructorBodyExtras;
    }
}
