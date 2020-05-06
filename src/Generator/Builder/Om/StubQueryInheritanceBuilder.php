<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use phootwork\lang\Text;
use Propel\Generator\Builder\Om\Component\NamingTrait;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Exception\BuildException;

/**
 * Generates the empty PHP stub query class for use with single table inheritance.
 *
 * This class produces the empty stub class that can be customized with
 * application business logic, custom behavior, etc.
 *
 * @author François Zaninotto
 */
class StubQueryInheritanceBuilder extends AbstractBuilder
{
    use NamingTrait;

    /**
     * The current child "object" we are operating on.
     */
    protected $child;

    /**
     * @param string $injectNamespace
     * @param string $classPrefix
     *
     * @return Text
     */
    public function getFullClassName(string $injectNamespace = '', string $classPrefix = ''): Text
    {
        return new Text($this->getChild()->getClassName() . 'Query');
    }

    public function buildClass(): void
    {
        $baseBuilder = $this->getNewBuilder($this->getChild(), QueryInheritanceBuilder::class);
        $parentClass = $this->getClassNameFromBuilder($baseBuilder, true);
        $this->getDefinition()->setParentClassName($parentClass);

        if ($this->getBuildProperty('generator.objectModel.addClassLevelComment')) {
            $description[] = "Skeleton subclass for representing a query for one of the subclasses of the '{$this->getTable()->getName()->toString()}' table.";
            $description[] = "";
            $description[] = $this->getTable()->getDescription();
            $description[] = "";
            if ($this->getBuildProperty('generator.objectModel.addTimeStamp')) {
                $now = strftime('%c');
                $description[] = "This class was autogenerated by Propel {$this->getBuildProperty('general.version')} on: $now";
                $description[] = "";
                $description[] = "You should add additional methods to this class to meet the";
                $description[] = "application requirements.  This class will only be generated as";
                $description[] = "long as it does not already exist in the output directory.";
            }

            $this->getDefinition()->setMultilineDescription($description);
        }
    }

    /**
     * Set the child object that we're operating on currently.
     *
     * @param Inheritance $child Inheritance
     */
    public function setChild(Inheritance $child): void
    {
        $this->child = $child;
    }

    /**
     * Returns the child object we're operating on currently.
     *
     * @return Inheritance
     * @throws BuildException
     */
    public function getChild(): Inheritance
    {
        if (!$this->child) {
            throw new BuildException("The MultiExtendObjectBuilder needs to be told which child class to build (via setChild() method) before it can build the stub class.");
        }

        return $this->child;
    }
}
