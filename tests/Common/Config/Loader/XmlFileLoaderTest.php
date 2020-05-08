<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use org\bovigo\vfs\vfsStream;
use phootwork\file\exception\FileException;
use Propel\Common\Config\Exception\XmlParseException;
use Propel\Common\Config\FileLocator;
use Propel\Common\Config\Loader\XmlFileLoader;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class XmlFileLoaderTest extends TestCase
{
    use VfsTrait;
    
    protected XmlFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new XmlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testXmlFileCanBeLoaded(): void
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        $file = $this->newFile('parameters.xml', $content);

        $test = $this->loader->load($file->url()); //if we pass 'parameters.xml' the test fails on Windows (maybe due to a directory separator problem)
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testXmlFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage("The file \"inexistent.xml\" does not exist (in: \"vfs://root\").");

        $this->loader->load('inexistent.xml');
    }

    public function testXmlFileHasInvalidContent(): void
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("An error occurred while parsing XML configuration file");
        $content = <<<EOF
not xml content
only plain
text
EOF;
        $this->newFile('nonvalid.xml', $content);

        @$this->loader->load('nonvalid.xml');
    }

    public function testXmlFileIsEmpty(): void
    {
        $this->newFile('empty.xml', '');

        $actual = $this->loader->load('empty.xml');

        $this->assertEquals([], $actual);
    }

    public function testXmlFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.xml file");

        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;

        $this->newFile('notreadable.xml', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.xml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
