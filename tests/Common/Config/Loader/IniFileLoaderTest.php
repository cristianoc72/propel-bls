<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use phootwork\file\exception\FileException;
use Propel\Common\Config\Exception\IniParseException;
use Propel\Common\Config\Exception\InvalidArgumentException;
use Propel\Common\Config\Loader\IniFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class IniFileLoaderTest extends TestCase
{
    use VfsTrait;

    protected IniFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new IniFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.ini'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.ini.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.properties.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testIniFileCanBeLoaded(): void
    {
        $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
        $this->newFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testIniFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The file \"inexistent.ini\" does not exist (in");

        $this->loader->load('inexistent.ini');
    }

    public function testIniFileHasInvalidContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The configuration file 'vfs://root/nonvalid.ini' has invalid content.");

        $content = <<<EOF
{not ini content}
only plain
text
EOF;
        $this->newFile('nonvalid.ini', $content);
        @$this->loader->load('nonvalid.ini');
    }

    public function testIniFileIsEmpty(): void
    {
        $this->newFile('empty.ini');

        $actual = $this->loader->load('empty.ini');

        $this->assertEquals([], $actual);
    }

    public function testWithSections(): void
    {
        $content = <<<EOF
[Cartoons]
Dog          = Pluto
Donald[]     = Huey
Donald[]     = Dewey
Donald[]     = Louie
Mickey[love] = Minnie
EOF;
        $this->newFile('section.ini', $content);
        $actual = $this->loader->load('section.ini');

        $this->assertEquals('Pluto', $actual['Cartoons']['Dog']);
        $this->assertEquals('Huey', $actual['Cartoons']['Donald'][0]);
        $this->assertEquals('Dewey', $actual['Cartoons']['Donald'][1]);
        $this->assertEquals('Louie', $actual['Cartoons']['Donald'][2]);
        $this->assertEquals('Minnie', $actual['Cartoons']['Mickey']['love']);
    }

    public function testNestedSections(): void
    {
        $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
        $this->newFile('nested.ini', $content);
        $actual = $this->loader->load('nested.ini');

        $this->assertEquals('foobar', $actual['foo']['bar']['baz']);
        $this->assertEquals('foobabar', $actual['foo']['bar']['babaz']);
        $this->assertEquals('blafoo', $actual['bla']['foo']);
        $this->assertEquals('blabar', $actual['bla']['bar']);
    }

    public function testMixedNestedSections(): void
    {
        $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
        $this->newFile('mixnested.ini', $content);
        $actual = $this->loader->load('mixnested.ini');

        $this->assertEquals('foobar', $actual['bla']['foo']['bar']);
        $this->assertEquals('foobarArray', $actual['bla']['foobar'][0]);
        $this->assertEquals('foobaz1', $actual['bla']['foo']['baz'][0]);
        $this->assertEquals('foobaz2', $actual['bla']['foo']['baz'][1]);
    }

    public function testInvalidSectionThrowsException(): void
    {
        $this->expectException(IniParseException::class);
        $this->expectExceptionMessage("Invalid key \".foo\"");

        $content = <<<EOF
.foo = bar
bar = baz
EOF;
        $this->newFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testInvalidParamThrowsException(): void
    {
        $this->expectException(IniParseException::class);
        $this->expectExceptionMessage("Invalid key \"foo.\"");

        $content = <<<EOF
foo. = bar
bar = baz
EOF;
        $this->newFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testAlreadyExistentParamThrowsException(): void
    {
        $this->expectException(IniParseException::class);
        $this->expectExceptionMessage("Cannot create sub-key for \"foo\", as key already exists");

        $content = <<<EOF
foo = bar
foo.babar = baz
EOF;
        $this->newFile('parameters.ini', $content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testSectionZero(): void
    {
        $content = <<<EOF
foo = bar
0.babar = baz
EOF;
        $this->newFile('parameters.ini', $content);

        $this->assertEquals(['0' => ['foo' => 'bar', 'babar' => 'baz']], $this->loader->load('parameters.ini'));
    }

    public function testIniFileNotReadableThrowsException()
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.ini file");

        $content = <<<EOF
foo = bar
bar = baz
EOF;
        $this->newFile('notreadable.ini', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.ini');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
