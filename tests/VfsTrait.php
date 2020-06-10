<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use phootwork\file\File;

/**
 * Useful methods to manipulate virtual filesystem, via vfsStream library
 *
 * @author Cristiano Cinotti
 */
trait VfsTrait
{
    private vfsStreamDirectory $root;

    public function getRoot(): vfsStreamDirectory
    {
        if (!isset($this->root)) {
            $this->root = vfsStream::setup();
        }

        return $this->root;
    }

    /**
     * Add a new file to the filesystem.
     * If the path of the file contains a directory structure, or a directory not present in
     * the virtual file system, it'll be created.
     *
     * @param string $filename
     * @param string $content
     *
     * @return vfsStreamFile
     */
    public function newFile(string $filename, string $content = ''): vfsStreamFile
    {
        $file = new File($filename);

        return vfsStream::newFile($file->getFilename()->toString())->at($this->getDir($file))->setContent($content);
    }

    /**
     * Return the directory on which append a file.
     * If the directory does not exist, it'll be created. If the directory name represents
     * a structure (e.g. dir/sub_dir/sub_sub_dir) the structure is created.
     *
     * @param File $file
     *
     * @return vfsStreamDirectory
     */
    private function getDir(File $file): vfsStreamDirectory
    {
        if ($file->getDirname()->toString() === '.') {
            return $this->getRoot();
        }

        $dirs = $file->getDirname()->split('/');
        $parent = $this->getRoot();
        foreach ($dirs as $dir) {
            $current = $parent->getChild($dir);
            if (null === $current) {
                $current = vfsStream::newDirectory($dir)->at($parent);
            }
            $parent = $current;
        }

        return $parent;
    }
}
