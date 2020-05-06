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

use cristianoc72\codegen\model\PhpParameter;
use phootwork\lang\Text;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Builder\Om\StubQueryBuilder;
use Propel\Generator\Model\Table;

/**
 * This trait provides some useful getters for php class names from various builders.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 * @author Cristiano Cinotti
 */
trait NamingTrait
{

    /**
     * @return AbstractBuilder
     */
    abstract protected function getBuilder();

    /**
     * This declares the class use and returns the correct name to use (short class name, Alias, or FQCN)
     *
     * @param  AbstractBuilder $builder
     * @param  boolean         $fqcn true to return the $fqcn class name
     *
     * @return string ClassName, Alias or FQCN
     */
    public function getClassNameFromBuilder(AbstractBuilder $builder, bool $fqcn = false): string
    {
        if ($fqcn) {
            return $builder->getFullClassName()->toString();
        }

        return $this->getBuilder()->getDefinition()->declareUse($builder->getFullClassName()->toString());
    }

    /**
     * This declares the class use and returns the correct name to use
     *
     * @param Table $table
     * @param bool   $fqcn
     *
     * @return string
     */
    public function getClassNameFromTable(Table $table, bool $fqcn = false): string
    {
        $fullClassName = $table->getFullName()->toString();

        return $fqcn ? $fullClassName : $this->extractClassName($fullClassName);
    }

    /**
     * Shortcut method to return the [stub] query class name for current table.
     * This is the class name that is used whenever object or tableMap classes want
     * to invoke methods of the query classes.
     *
     * @param  boolean $fqcn
     *
     * @return string  (e.g. 'MyQuery')
     */
    public function getQueryClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getStubQueryBuilder(), $fqcn);
    }

    /**
     * @param Table $table
     * @param bool   $fqcn
     *
     * @return string
     */
    public function getQueryClassNameForTable(Table $table, bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder(
            $this->getBuilder()->getNewBuilder($table, StubQueryBuilder::class),
            $fqcn
        );
    }

    /**
     * Returns the object class name for current table.
     * This is the class name that is used whenever object or tablemap classes want
     * to invoke methods of the object classes.
     *
     * @param  boolean $fqcn
     *
     * @return string  (e.g. 'MyTable' or 'ChildMyTable')
     */
    public function getObjectClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getObjectBuilder(), $fqcn);
    }

    /**
     * Returns the tableMap class name for current table.
     *
     * This is the class name that is used whenever object or tableMap classes want
     * to invoke methods of the object classes.
     *
     * @param  boolean $fqcn
     *
     * @return string (e.g. 'My')
     */
    public function getTableMapClassName(bool $fqcn = false): string
    {
        return $this->getClassNameFromBuilder($this->getBuilder()->getTableMapBuilder(), $fqcn);
    }

    /**
     * @param PhpParameter[] $params
     * @param string         $glue
     *
     * @return string
     */
    protected function parameterToString(array $params, string $glue = ', '): string
    {
        $names = [];
        /** @var PhpParameter $param */
        foreach ($params as $param) {
            $names[] = '$' . $param->getName();
        }

        return implode($glue, $names);
    }

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    protected function extractNamespace(string $fullClassName): string
    {
        $namespace = explode('\\', trim($fullClassName, '\\'));
        array_pop($namespace);

        return implode('\\', $namespace);
    }

    /**
     * @param string $fullClassName
     *
     * @return string
     */
    protected function extractClassName(string $fullClassName): string
    {
        $namespace = explode('\\', trim($fullClassName, '\\'));

        return array_pop($namespace);
    }
}
