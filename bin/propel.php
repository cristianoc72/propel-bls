<?php declare(strict_types=1);

use Symfony\Component\Finder\Finder;

use Propel\Runtime\Propel;
use Propel\Generator\Application;

$finder = new Finder();
$finder->files()->name('*.php')->in(__DIR__ . '/../src/Propel/Generator/Command')->depth(0);

$app = new Application('Propel', Propel::VERSION);

$ns = '\\Propel\\Generator\\Command\\';

foreach ($finder as $file) {
    $r  = new \ReflectionClass($ns . $file->getBasename('.php'));
    if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract()) {
        $app->add($r->newInstance());
    }
}

$app->run();
