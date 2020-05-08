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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws ParseException if something goes wrong in parsing file
     * @throws FileException if configuration file is not found or not readable
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->getLocator()->locate($file));

        $content = Yaml::parse($file->read()->toString()) ?? [];

        if (!is_array($content)) {
            throw new ParseException('Unable to parse the configuration file: wrong yaml content.');
        }

        return $this->resolveParams($content);
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, string $type = null): bool
    {
        $fileName = new Text($resource);

        return $fileName->endsWith('yaml') || $fileName->endsWith('yml') ||
            $fileName->endsWith('yaml.dist') || $fileName->endsWith('yml.dist');
    }
}
