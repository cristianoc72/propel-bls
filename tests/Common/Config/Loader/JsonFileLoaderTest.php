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
use Propel\Common\Config\Loader\JsonFileLoader;
use Propel\Common\Config\FileLocator;
use Propel\Tests\TestCase;
use Propel\Tests\VfsTrait;

class JsonFileLoaderTest extends TestCase
{
    use VfsTrait;

    protected JsonFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new JsonFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testJsonFileCanBeLoaded()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $this->newFile('parameters.json', $content);

        $actual = $this->loader->load('parameters.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The file "inexistent.json" does not exist (in:
     */
    public function testJsonFileDoesNotExist()
    {
        $this->loader->load('inexistent.json');
    }

    /**
     * @expectedException phootwork\json\JsonException
     */
    public function testJsonFileHasInvalidContent()
    {
        $content = <<<EOF
not json content
only plain
text
EOF;
        $this->newFile('nonvalid.json', $content);

        $this->loader->load('nonvalid.json');
    }

    public function testJsonFileIsEmpty()
    {
        $this->newFile('empty.json');

        $actual = $this->loader->load('empty.json');

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException Propel\Common\Config\Exception\InputOutputException
     * @expectedExceptionMessage You don't have permissions to access configuration file notreadable.json.
     */
    public function testJsonFileNotReadableThrowsException()
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        $this->newFile('notreadable.json', $content)->chmod(200);

        $actual = $this->loader->load('notreadable.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
