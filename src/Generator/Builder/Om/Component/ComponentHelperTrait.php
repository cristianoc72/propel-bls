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

use Propel\Generator\Builder\PhpModel\ClassDefinition;
use Propel\Generator\Builder\Om\AbstractBuilder;
use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;
use Propel\Generator\Platform\MysqlPlatform;

/**
 * Trait ComponentHelperTrait
 *
 * This trait is a little helper.
 *
 * @author Marc J. Schmidt <marc@marcjschmidt.de>
 */
trait ComponentHelperTrait
{
    /**
     * @return AbstractBuilder
     */
    abstract protected function getBuilder(): AbstractBuilder;

    /**
     * @return ClassDefinition
     */
    abstract protected function getDefinition(): ClassDefinition;

    /**
     * Returns the type-casted and stringified default value for the specified
     * Column. This only works for scalar default values currently.
     *
     * @param  Column $column
     *
     * @throws EngineException
     * @return string
     * @deprecated Use Column::getDefaultValueString() instead
     */
    protected function getDefaultValueString(Column $column): string
    {
        $defaultValue = var_export(null, true);
        $val = $column->getPhpDefaultValue();
        if (null === $val) {
            return $defaultValue;
        }

        if ($column->isTemporalType()) {
            $fmt = $this->getTemporalFormatter($column);
            try {
                if (!($this->getBuilder()->getPlatform() instanceof MysqlPlatform &&
                    ($val === '0000-00-00 00:00:00' || $val === '0000-00-00'))
                ) {
                    // while technically this is not a default value of NULL,
                    // this seems to be closest in meaning.
                    $defDt = new \DateTime($val);
                    $defaultValue = var_export($defDt->format($fmt), true);
                }
            } catch (\Exception $exception) {
                // prevent endless loop when timezone is undefined
                date_default_timezone_set('America/Los_Angeles');
                throw new EngineException(
                    sprintf(
                        'Unable to parse default temporal value "%s" for column "%s"',
                        $column->getDefaultValueString(),
                        $column->getFullyQualifiedName()
                    ),
                    0,
                    $exception
                );
            }
        } elseif ($column->isEnumType()) {
            $valueSet = $column->getValueSet();
            if (!$valueSet->contains($val)) {
                throw new EngineException(sprintf('Default Value "%s" is not among the enumerated values', $val));
            }
            $defaultValue = $valueSet->find($val, function ($element, $query) {
                return $element === $query;
            } );
        } elseif (PropelTypes::isPhpPrimitiveType($column->getType())) {
            settype($val, $column->getPhpType());
            $defaultValue = var_export($val, true);
        } elseif (PropelTypes::isPhpObjectType($column->getType())) {
            $defaultValue = 'new ' . $column->getPhpType() . '(' . var_export($val, true) . ')';
        } elseif (PropelTypes::isPhpArrayType($column->getType())) {
            $defaultValue = $val;
        } else {
            throw new EngineException("Cannot get default value string for " . $column->getFullyQualifiedName());
        }

        return $defaultValue;
    }

    /**
     * Returns the appropriate formatter (from platform) for a date/time column.
     *
     * @param  Column $column
     *
     * @return string
     */
    protected function getTemporalFormatter(Column $column): string
    {
        $fmt = null;
        if ($column->getType() === PropelTypes::DATE) {
            $fmt = $this->getBuilder()->getPlatform()->getDateFormatter();
        } elseif ($column->getType() === PropelTypes::TIME) {
            $fmt = $this->getBuilder()->getPlatform()->getTimeFormatter();
        } elseif ($column->getType() === PropelTypes::TIMESTAMP) {
            $fmt = $this->getBuilder()->getPlatform()->getTimestampFormatter();
        }

        return $fmt;
    }

    /**
     * Gets the path to be used in include()/require() statement.
     *
     * Supports multiple function signatures:
     *
     * (1) getFilePath($dotPathClass);
     * (2) getFilePath($dotPathPrefix, $className);
     * (3) getFilePath($dotPathPrefix, $className, $extension);
     *
     * @param  string $path      dot-path to class or to package prefix.
     * @param  string $classname class name
     * @param  string $extension The extension to use on the file.
     *
     * @return string The constructed file path.
     */
    public function getFilePath(string $path, ?string $classname = null, string $extension = '.php'): string
    {
        $path = strtr(ltrim($path, '.'), '.', '/');

        return $this->createFilePath($path, $classname, $extension);
    }

    /**
     * This method replaces the `getFilePath()` method in OMBuilder as we consider `$path` as
     * a real path instead of a dot-notation value. `$path` is generated by  the `getPackagePath()`
     * method.
     *
     * @param  string $path      path to class or to package prefix.
     * @param  string $classname class name
     * @param  string $extension The extension to use on the file.
     *
     * @return string The constructed file path.
     */
    public function createFilePath(string $path, ?string $classname = null, string $extension = '.php'): string
    {
        if (null === $classname) {
            return $path . $extension;
        }

        if (!empty($path)) {
            $path .= '/';
        }

        return $path . $classname . $extension;
    }

    /**
     * Gets a list of PHP reserved words.
     *
     * @return string[]
     */
    public function getPhpReservedWords(): array
    {
        return [
            'and',
            'or',
            'xor',
            'exception',
            '__FILE__',
            '__LINE__',
            'array',
            'as',
            'break',
            'case',
            'class',
            'const',
            'continue',
            'declare',
            'default',
            'die',
            'do',
            'echo',
            'else',
            'elseif',
            'empty',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'eval',
            'exit',
            'extends',
            'for',
            'foreach',
            'function',
            'global',
            'if',
            'include',
            'include_once',
            'isset',
            'list',
            'new',
            'print',
            'require',
            'require_once',
            'return',
            'static',
            'switch',
            'unset',
            'use',
            'var',
            'while',
            '__FUNCTION__',
            '__CLASS__',
            '__METHOD__',
            '__TRAIT__',
            '__DIR__',
            '__NAMESPACE__',
            'final',
            'php_user_filter',
            'interface',
            'implements',
            'extends',
            'public',
            'protected',
            'private',
            'abstract',
            'clone',
            'try',
            'catch',
            'throw',
            'this',
            'trait',
            'namespace'
        ];
    }
}
