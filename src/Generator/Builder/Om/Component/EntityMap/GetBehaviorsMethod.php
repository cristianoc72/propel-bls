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
 * Adds getBehaviors method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class GetBehaviorsMethod extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        $body = "
return array(";

        foreach ($this->getEntity()->getBehaviors() as $behavior) {
            $body .= "
            '{$behavior->getId()}' => array(";
            foreach ($behavior->getParameters() as $key => $value) {
                $body .= "'$key' => ";
                if (is_array($value)) {
                    $string = var_export($value, true);
                    $string = str_replace("\n", '', $string);
                    $string = str_replace('  ', '', $string);
                    $body .= $string . ", ";
                } else {
                    $body .= "'$value', ";
                }
            }
            $body .= "),";
        }
        $body .= "
);";

        $this->addMethod('getBehaviors')
            ->setBody($body)
            ->setType('array');
    }
}
