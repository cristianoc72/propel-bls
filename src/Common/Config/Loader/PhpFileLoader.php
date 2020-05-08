<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use phootwork\file\exception\FileException;
use phootwork\file\File;
use phootwork\lang\Text;
use Propel\Common\Config\Exception\InvalidArgumentException;

/**
 * PhpFileLoader loads configuration values from a PHP file.
 *
 * The configuration values are expected to be in form of array. I.e.
 * <code>
 *     <?php
 *         return array(
 *                    'property1' => 'value1',
 *                    .......................
 *                );
 * </code>
 *
 * @author Cristiano Cinotti
 */
class PhpFileLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     *
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws FileException if the configuration file not found or not readable
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->getLocator()->locate($file));

        if (!$file->isReadable()) {
            throw new FileException("Impossible to read the configuration file: do you have the right permissions?");
        }

        //Use output buffering because in case $file contains invalid non-php content (i.e. plain text), include() function
        //write it on stdoutput
        ob_start();
        $content = include $file->getPathname()->toString();
        ob_end_clean();

        if (!is_array($content)) {
            throw new InvalidArgumentException("The configuration file '$file' has invalid content.");
        }

        return $this->resolveParams($content);
    }

    /**
     * Returns true if this class supports the given resource.
     * It supports both .php and .inc extensions.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, string $type = null): bool
    {
        $resource = new Text($resource);

        return $resource->endsWith('.php') || $resource->endsWith('.php.dist') ||
            $resource->endsWith('.inc') || $resource->endsWith('.inc.dist');
    }
}
