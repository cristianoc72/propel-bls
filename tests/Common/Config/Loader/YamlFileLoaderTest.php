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
use Propel\Common\Config\Loader\YamlFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    use VfsTrait;
    
    protected YamlFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new YamlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testYamlFileCanBeLoaded(): void
    {
        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        $this->newFile('parameters.yaml', $content);

        $test = $this->loader->load('parameters.yaml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testYamlFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage("The file \"inexistent.yaml\" does not exist (in: \"vfs://root\").");

        $this->loader->load('inexistent.yaml');
    }

    public function testYamlFileHasInvalidContent(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Unable to parse");

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        $this->newFile('nonvalid.yaml', $content);
        $this->loader->load('nonvalid.yaml');
    }


    public function testYamlFileIsEmpty(): void
    {
        $this->newFile('empty.yaml', '');

        $actual = $this->loader->load('empty.yaml');

        $this->assertEquals([], $actual);
    }

    public function testYamlFileNotReadableThrowsException(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage("You don't have permissions to access notreadable.yaml file");

        $content = <<<EOF
foo: bar
bar: baz
EOF;
        $this->newFile('notreadable.yaml', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.yaml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
