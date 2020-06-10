<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Builder\Om;

use phootwork\file\Directory;
use phootwork\file\File;
use Propel\Generator\Builder\Om\Component\SimpleTemplateTrait;
use Propel\Generator\Exception\BuildException;
use Propel\Tests\TestCase;

class SimpleTemplateTest extends TestCase
{
    use SimpleTemplateTrait;

    /**
     * @var Directory Fixtures directory
     */
    private Directory $dir;

    /**
     * Unfortunately, it's impossible to test via virtual file system
     */
    protected function setUp(): void
    {
        $directory = new Directory(__DIR__ . '/templates');
        $directory->make();
        $tpl1 = new File($directory->getPathname()->append('/my_template.twig'));
        $tpl1->write('This is {{ name }}\'s template. Pass `file_name` and pass `file_name.twig`');
        $tpl2 = new File($directory->getPathname()->append('/simple_template_test.twig'));
        $tpl2->write('This is {{ name }}\'s template. Autoloaded.');
        $this->dir = $directory;
    }

    protected function tearDown(): void
    {
        $this->dir->delete();
    }

    public function testName(): void
    {
        $this->assertEquals(
            'This is John\'s template. Pass `file_name` and pass `file_name.twig`',
            $this->renderTemplate(['name' => 'John'], 'my_template')
        );
    }

    public function testNameDotTwig(): void
    {
        $this->assertEquals(
            'This is John\'s template. Pass `file_name` and pass `file_name.twig`',
            $this->renderTemplate(['name' => 'John'], 'my_template')
        );
    }

    public function testAutoload(): void
    {
        $this->assertEquals(
            'This is John\'s template. Autoloaded.',
            $this->renderTemplate(['name' => 'John'])
        );
    }

    public function testWrongFileNameThrowsException(): void
    {
        $this->expectException(BuildException::class);

        $this->assertEquals(
            'This is John\'s template. autoloaded.',
            $this->renderTemplate(['name' => 'John'], 'wrong_name')
        );
    }

    public function testFullNameThrowsException(): void
    {
        $this->expectException(BuildException::class);

        $this->assertEquals(
            'This is John\'s template. autoloaded.',
            $this->renderTemplate(['name' => 'John'], __DIR__ . '/templates/my_template.twig')
        );
    }
}
