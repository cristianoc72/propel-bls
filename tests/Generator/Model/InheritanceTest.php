<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Inheritance;
use Propel\Tests\TestCase;

/**
 * Unit test suite for the Inheritance model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class InheritanceTest extends TestCase
{
    public function testCreateNewInheritance(): void
    {
        $column = $this
            ->getMockBuilder('Propel\Generator\Model\Column')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $inheritance = new Inheritance();
        $inheritance->setAncestor('BaseObject');
        $inheritance->setKey('baz');
        $inheritance->setClassName('Foo\Bar');
        $inheritance->setColumn($column);

        $this->assertInstanceOf('Propel\Generator\Model\Column', $inheritance->getColumn());
        $this->assertSame('BaseObject', $inheritance->getAncestor());
        $this->assertSame('baz', $inheritance->getKey());
        $this->assertSame('Foo\Bar', $inheritance->getClassName());
    }
}
