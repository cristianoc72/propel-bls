<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om;

use Propel\Generator\Model\Table;

/**
 * Tools to support class & package inclusion and referencing.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 */
class ClassTools
{

    /**
     * Gets just classname, given a dot-path to class.
     * @param  string $qualifiedName
     * @return string
     */
    public static function classname($qualifiedName)
    {
        if (false !== $pos = strrpos($qualifiedName, '.')) {
            return substr($qualifiedName, $pos + 1); // start just after '.'
        } elseif (false !== $pos = strrpos($qualifiedName, '\\')) {
            return substr($qualifiedName, $pos + 1);
        } else {
            return $qualifiedName;  // there is no '.' in the qualified name
        }
    }

    /**
     * This method replaces the `getFilePath()` method in OMBuilder as we consider `$path` as
     * a real path instead of a dot-notation value. `$path` is generated by  the `getPackagePath()`
     * method.
     *
     * @param  string $path      path to class or to package prefix.
     * @param  string $classname class name
     * @param  string $extension The extension to use on the file.
     * @return string The constructed file path.
     */
    public static function createFilePath($path, $classname = null, $extension = '.php')
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
     * Gets the baseClass path if specified for table/db.
     *
     * @return string
     */
    public static function getBaseClass(Table $table)
    {
        return $table->getBaseClass();
    }

    /**
     * Gets the interface path if specified for table.
     *
     * @return string
     */
    public static function getInterface(Table $table)
    {
        return $table->getInterface();
    }

    /**
     * Gets a list of PHP reserved words.
     *
     * @return string[]
     */
    public static function getPhpReservedWords()
    {
        return [
            'and', 'or', 'xor', 'exception', '__FILE__', '__LINE__',
            'array', 'as', 'break', 'case', 'class', 'const', 'continue',
            'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
            'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
            'eval', 'exit', 'extends', 'for', 'foreach', 'function', 'global',
            'if', 'include', 'include_once', 'isset', 'list', 'new', 'print', 'require',
            'require_once', 'return', 'static', 'switch', 'unset', 'use', 'var', 'while',
            '__FUNCTION__', '__CLASS__', '__METHOD__', '__DIR__', '__NAMESPACE__', 'final', 'php_user_filter', 'interface',
            'implements', 'extends', 'public', 'protected', 'private', 'abstract', 'clone', 'try', 'catch',
            'throw', 'this', 'namespace'
        ];
    }

    public static function getPropelReservedMethods()
    {
        return [
            'isModified', 'isColumnModified', 'isNew', 'isDeleted',
        ];
    }
}
