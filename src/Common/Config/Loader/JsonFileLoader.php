<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Common\Config\Loader;

use phootwork\file\File;
use phootwork\json\Json;
use phootwork\json\JsonException;
use phootwork\lang\Text;
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
     * @param mixed $file The resource
     * @param string $type The resource type
     *
     * @return array
     *
     * @throws JsonException
     */
    public function load($file, string $type = null): array
    {
        $file = new File($this->getLocator()->locate($file));
        $json = $file->read();
        $content = [];

        if (!$json->isEmpty()) {
            $content = Json::decode($json->toString());
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
    public function supports($resource, string $type = null): bool
    {
        $resource = new Text($resource);

        return $resource->endsWith('.json') || $resource->endsWith('.json.dist');
    }
}
