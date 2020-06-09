<?php declare(strict_types=1);

/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Susina\Codegen\Generator\CodeFileGenerator;
use phootwork\lang\Text;
use Propel\Generator\Builder\DataModelBuilder;
use Propel\Generator\Builder\Om\Component\ComponentTrait;
use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Runtime\Exception\PropelException;

/**
 * Abstract class for all builders.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Cristiano Cinotti
 */
abstract class AbstractBuilder extends DataModelBuilder
{
    use ComponentTrait;

    /**
     * @var ClassDefinition
     */
    protected $definition;

    /**
     * In this method the actual builder will define the class definition in $this->definition.
     */
    abstract protected function buildClass(): void;

    protected function getBuilder(): AbstractBuilder
    {
        return $this;
    }

    /**
     * Builds the PHP source for current class and returns it as a string.
     *
     * This is the main entry point and defines a basic structure that classes should follow.
     * In most cases this method will not need to be overridden by subclasses.  This method
     * does assume that the output language is PHP code, so it will need to be overridden if
     * this is not the case.
     *
     * @return string The resulting PHP sourcecode.
     * @throws PropelException If the table has no primary key
     */
    public function build(): string
    {
        $this->definition = new ClassDefinition($this->getFullClassName());

        if (!$this->getTable()->getPrimaryKey()) {
            throw new PropelException(sprintf('The table %s does not have a primary key.', $this->getFullClassName()));
        }

        if (false === $this->buildClass()) {
            return '';
        }

        $this->applyBehaviorModifier();

        $generator = new CodeFileGenerator();

        return $generator->generate($this->getDefinition());
    }

    /**
     * @return ClassDefinition
     */
    public function getDefinition(): ClassDefinition
    {
        return $this->definition;
    }

    /**
     * @param ClassDefinition $definition
     */
    public function setDefinition(ClassDefinition $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * @param string $identifier
     *
     * @return string
     */
    public function quoteIdentifier(string $identifier): string
    {
        if ($this->getTable()->isIdentifierQuotingEnabled()) {
            return $this->getPlatform()->doQuoting($identifier);
        }

        return $identifier;
    }

    /**
     * Returns the full class name with namespace. Overwrite this method if you need
     * to have a different class name.
     *
     * @param string $injectNamespace will be inject in the namespace between namespace and className
     * @param string $classPrefix     will be inject in the class name between namespace and className
     *
     * @return Text
     */
    public function getFullClassName(string $injectNamespace = '', string $classPrefix = ''): Text
    {
        $fullClassName = $this->getTable()->getFullName();
        $namespace = $fullClassName->split('\\');
        $className = $namespace->pop()->prepend($classPrefix);
        $namespace->add(trim($injectNamespace, '\\'));

        if (!$namespace->isEmpty()) {
            return $namespace->join('\\')->ensureEnd('\\')->append($className)->ensureEnd('\\');
        } else {
            return $fullClassName;
        }
    }

    /**
     * Gets the full path to the file for the current class.
     *
     * @return Text
     */
    public function getClassFilePath(): Text
    {
        return $this->getFullClassName()->replace('\\', '/')->ensureEnd('.php');
    }

    /**
     * Whether to add the generic mutator methods (setByName(), setByPosition(), fromArray()).
     * This is based on the build property propel.addGenericMutators, and also whether the
     * table is read-only or an alias.
     *
     * @return bool
     */
    protected function isAddGenericMutators(): bool
    {
        $table = $this->getTable();

        return
            !$table->isAlias() &&
            $this->getBuildProperty('generator.objectModel.addGenericMutators') &&
            !$table->isReadOnly();
    }

    /**
     * Whether to add the mutator methods.
     *
     * @return bool
     */
    protected function isAddMutators(): bool
    {
        $table = $this->getTable();

        return
            !$table->isAlias() &&
            !$table->isReadOnly();
    }

    /**
     * Whether to add the accessor methods.
     *
     * @return bool
     */
    protected function isAddAccessors(): bool
    {
        $table = $this->getTable();

        return
            !$table->isAlias() &&
            !$table->isReadOnly();
    }

    /**
     * Whether to add the generic accessor methods (getByName(), getByPosition(), toArray()).
     * This is based on the build property propel.addGenericAccessors, and also whether the
     * table is an alias.
     *
     * @return bool
     */
    protected function isAddGenericAccessors(): bool
    {
        $table = $this->getTable();

        return
            !$table->isAlias() &&
            $this->getBuildProperty('generator.objectModel.addGenericAccessors');
    }

    /**
     * Returns default key type.
     *
     * If not presented in configuration default will be 'TYPE_PHPNAME'
     *
     * @return string
     */
    public function getDefaultKeyType(): string
    {
        $defaultKeyType = $this->getBuilder()->getBuildProperty('generator.objectModel.defaultKeyType')
            ? $this->getBuilder()->getBuildProperty('generator.objectModel.defaultKeyType')
            : 'phpName';

        return "TYPE_".strtoupper($defaultKeyType);
    }

    /**
     * Returns the className without namespace that is being built by the current class.
     *
     * @return Text
     */
    public function getClassName(): Text
    {
        $fullClassName = $this->getFullClassName();

        return $fullClassName->split('\\')->pop();
    }

    /**
     * Checks whether any registered behavior on that table has a modifier for a hook
     */
    public function applyBehaviorModifier(): void
    {
        $className = explode('\\', get_called_class());
        $className = array_pop($className);
        $modifierGetter = 'get' . $className . 'Modifier';

        $hookName = lcfirst($className) . 'Modification';

        foreach ($this->getTable()->getBehaviors() as $behavior) {
            if (method_exists($behavior, $modifierGetter)) {
                $modifier = $behavior->$modifierGetter();
            } else {
                $modifier = $behavior;
            }
            if (method_exists($modifier, $hookName)) {
                $modifier->$hookName($this);
            }
        }
    }

//    /**
//     * @param string $hookName
//     *
//     * @return string
//     */
//    public function applyBehaviorHooks(string $hookName): string
//    {
//        $body = '';
//        foreach ($this->getTable()->getBehaviors() as $behavior) {
//            if (method_exists($behavior, $hookName)) {
//                $code = $behavior->$hookName($this);
//
//                $hookBehaviorMethodName = $hookName . ucfirst(NamingTool::toCamelCase($behavior->getId()));
//
//                if ($code) {
//                    $body .= "\n//behavior hook {$behavior->getName()}#{$behavior->getId()}";
//
//                    $method = new PhpMethod($hookBehaviorMethodName);
//                    $method->setVisibility('protected');
//                    $method->addSimpleParameter('event');
//                    $method->setType('boolean|null', 'Returns false to cancel the event hook');
//                    $method->setBody($code);
//
//                    $this->getDefinition()->setMethod($method);
//                    $body .= "
//if (false === \$this->$hookBehaviorMethodName(\$event)) {
//    return false;
//}
//";
//                }
//            }
//        }
//
//        if ($body) {
//            $body = "parent::{$hookName}(\$event);\n" . $body;
//        }
//
//        return $body;
//    }

    /**
     * Checks whether any registered behavior content creator on that table exists a contentName
     *
     * @param string $contentName The name of the content as called from one of this class methods, e.g.
     *                            "parentClassName"
     * @param string $modifier    The name of the modifier object providing the method in the behavior
     *
     * @return mixed
     */
    public function getBehaviorContentBase(string $contentName, string $modifier)
    {
        $modifierGetter = 'get' . ucfirst($modifier);
        foreach ($this->getTable()->getBehaviors() as $behavior) {
            $modifier = $behavior->$modifierGetter();
            if (method_exists($modifier, $contentName)) {
                return $modifier->$contentName($this);
            }
        }
    }
}
