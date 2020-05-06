<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\EntityMap;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;

/**
 * Adds getPropReader method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPropReaderMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $className = $this->getObjectClassName(true);

        $body = "
return \$this->getClassPropReader('$className');
        ";

        $this->addMethod('getPropReader')
            ->setBody($body);
    }
}
