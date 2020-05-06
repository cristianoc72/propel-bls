<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component;

use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\FilesystemLoader;

trait SimpleTemplateTrait
{

    /**
     * Renders a template and returns it output.
     *
     * Searches for a template following this scheme:
     *   $curDir/template/{$template}.twig
     *
     * where $curDir is the current directory the get_called_class is living and
     * $template is your given value or the underscore version of your get_called_class name.
     *
     * @param array  $context
     * @param string $template relative to current Component directory + ./template/.
     *
     * @return string
     * @throws \ReflectionException
     * @throws Error If something went wrong in loading and processing template
     */
    protected function renderTemplate(array $context = [], string $template = ''): string
    {
        $classReflection = new \ReflectionClass(get_called_class());
        $currentDir = dirname($classReflection->getFileName());

        if (!$template) {
            $template = "{$classReflection->getShortName()}.twig";
        }

        $loader = new FilesystemLoader($currentDir);
        $twig = new Environment($loader, ['cache' => false, 'strict_variables' => true, 'autoescape' => false]);

        return $twig->render($template, $context);
    }
}
