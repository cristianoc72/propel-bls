<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Common\Config\Loader;

use org\bovigo\vfs\vfsStream;
use Propel\Common\Config\Loader\YamlFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\vfsTrait;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    use vfsTrait;
    
    protected $loader;

    protected function setUp()
    {
        $this->loader = new YamlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testYamlFileCanBeLoaded()
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

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.yaml" does not exist (in:
     */
    public function testYamlFileDoesNotExist()
    {
        $this->loader->load('inexistent.yaml');
    }

    /**
     * @expectedException        Symfony\Component\Yaml\Exception\ParseException
     * @expectedExceptionMessage Unable to parse
     */
    public function testYamlFileHasInvalidContent()
    {
        $content = <<<EOF
not yaml content
only plain
text
EOF;
        $this->newFile('nonvalid.yaml', $content);
        $this->loader->load('nonvalid.yaml');
    }


    public function testYamlFileIsEmpty()
    {
        $this->newFile('empty.yaml', '');

        $actual = $this->loader->load('empty.yaml');

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.yaml.
     */
    public function testYamlFileNotReadableThrowsException()
    {
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
