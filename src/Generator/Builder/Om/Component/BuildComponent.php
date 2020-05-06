<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

namespace Propel\Generator\Builder\Om\Component;

use cristianoc72\codegen\model\PhpMethod;
use cristianoc72\codegen\model\PhpProperty;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PlatformInterface;

abstract class BuildComponent
{
    /**
     * @var AbstractBuilder
     */
    protected $builder;

    /**
     * @var ClassDefinition
     */
    protected $definition;

    /**
     * @var Behavior
     */
    protected $behavior;

    /**
     * BuildComponent constructor.
     *
     * @param AbstractBuilder $builder
     * @param Behavior|null $behavior
     */
    public function __construct(AbstractBuilder $builder, Behavior $behavior = null)
    {
        $this->builder = $builder;
        $this->behavior = $behavior;
        $this->definition = $builder->getDefinition();
    }

    /**
     * @return Behavior|null
     */
    public function getBehavior(): ?Behavior
    {
        return $this->behavior;
    }

    /**
     * @return ClassDefinition
     */
    public function getDefinition(): ClassDefinition
    {
        return $this->definition;
    }

    /**
     * @return AbstractBuilder
     */
    protected function getBuilder(): AbstractBuilder
    {
        return $this->builder;
    }

    /**
     * @return Table
     */
    protected function getTable(): Table
    {
        return $this->builder->getTable();
    }


    /**
     * @return PlatformInterface
     */
    protected function getPlatform(): PlatformInterface
    {
        return $this->builder->getPlatform();
    }

    /**
     * Add a property to a class
     *
     * @param string $name
     * @param string $type
     * @param mixed  $value
     * @param string $description
     * @param string $visibility
     *
     * @return PhpProperty
     */
    protected function addProperty(
        string $name, string $type, $value = null, string $description = '', string $visibility = 'protected'
    ): PhpProperty
    {
        $property = new PhpProperty($name);
        $property->setValue($value);
        $property->setVisibility($visibility);
        $property->setDescription($description);

        $this->getDefinition()->setProperty($property);

        return $property;
    }

    /**
     * @param string $name
     * @param string $visibility
     *
     * @return PhpMethod
     */
    protected function addMethod(string $name, string $visibility = 'public'): PhpMethod
    {
        $method = new PhpMethod($name);
        $method->setVisibility($visibility);
        $this->getDefinition()->setMethod($method);

        return $method;
    }

    /**
     * Adds a "use $fullClassName" and returns the class name you can use. It ads automatically "use x as y" when necessary.
     *
     * @param string $fullClassName
     * @return string
     */
    public function useClass(string $fullClassName): string
    {
        if ($this->getDefinition()->getQualifiedName() === $fullClassName) {
            return $this->getDefinition()->getName();
        }

        if ($this->getDefinition()->hasUseStatement($fullClassName)) {
            //this full class is already registered, so return its name/alias.
            return $this->getDefinition()->getUseAlias($fullClassName);
        }

        if ($this->classNameInUse($fullClassName)) {
            //name already in use, so use full qualified name and dont place a "use $fullClassName".
            return '\\' . $fullClassName;
        }

        return $this->getDefinition()->declareUse($fullClassName);
    }

    /**
     * If the className (without namespace) of $fullClassName is already in "use" directly or as alias.
     *
     * @param string $fullClassName
     *
     * @return boolean
     */
    public function classNameInUse(string $fullClassName): bool
    {
        $className = basename(str_replace('\\', '/', $fullClassName));

        if ($className === $this->getDefinition()->getName()) {
            //when the request fullClassName is current definition we return true,
            //because its not possible to use a same class name in the current namespace.
            return true;
        }

        $statements = $this->getDefinition()->getUseStatements();
        return isset($statements[$className]);
    }

    protected function addConstructorBody(string $bodyPart): void
    {
        $this->getDefinition()->addConstructorBody($bodyPart);
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return $this->getBuilder()->quoteIdentifier($identifier);
    }
}
