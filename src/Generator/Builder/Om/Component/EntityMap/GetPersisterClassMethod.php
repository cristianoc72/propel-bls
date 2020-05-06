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
use Propel\Generator\Builder\Om\Component\ForeignKeyTrait;

/**
 * Adds getPersisterClass method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetPersisterClassMethod extends BuildComponent
{
    use NamingTrait;
    use ForeignKeyTrait;

    public function process()
    {
        $body = "
return parent::getPersisterClass();
";

        $this->addMethod('getPersisterClass')
            ->setBody($body);
    }
}
