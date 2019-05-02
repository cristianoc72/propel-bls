<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

declare(strict_types=1);

namespace Propel\Common\Config\Loader;

use phootwork\json\Json;
use phootwork\json\JsonException;
use Propel\Common\Config\Exception\JsonParseException;

/**
 * JsonFileLoader loads configuration parameters from json file.
 *
 * @author Cristiano Cinotti
 */
class JsonFileLoader extends FileLoader
{
    /**
     * Loads an Json file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws \InvalidArgumentException                            if configuration file not found
     * @throws \Propel\Common\Config\Exception\InputOutputException if configuration file is not readable
     * @throws JsonException
     */
    public function load($file, $type = null): array
    {
        $json = file_get_contents($this->getPath($file));
        $content = [];
        if ('' !== $json) {
            $content = Json::decode($json);
            $content = $this->resolveParams($content); //Resolve parameter placeholders (%name%)
        }

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return $this->checkSupports('json', $resource);
    }
}
