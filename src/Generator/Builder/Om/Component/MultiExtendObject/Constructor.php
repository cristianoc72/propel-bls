<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component\MultiExtendObject;

use Propel\Generator\Builder\Om\Component\BuildComponent;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Inheritance;

/**
 * Adds the __construct method.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
class Constructor extends BuildComponent
{
    use NamingTrait;

    public function process()
    {
        /** @var Inheritance $child */
        $child = $this->getBuilder()->getChild();
        $col = $child->getColumn();
        $cfc = $col->getName();

        $this->getDefinition()->declareUse($this->getTableMapClassName(true));

        $const = "CLASSKEY_".strtoupper($child->getKey());

        $body = <<<EOF
parent::__construct();
\$this->set$cfc({$this->getTableMapClassName()}::$const);
EOF;

        $this->addMethod('__construct')
            ->setDescription("Constructs a new {$child->getClassName()} class, setting the {$col->getName()} column to {$this->getTableMapClassName()}::$const.")
            ->setBody($body);
    }
}
