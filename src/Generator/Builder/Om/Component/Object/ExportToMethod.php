<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Builder\Om\Component\Object;


use cristianoc72\codegen\model\PhpParameter;
use Propel\Generator\Builder\Om\Component\BuildComponent;

class ExportToMethod extends BuildComponent
{
    public function process(): void
    {
        $this->addMethod('exportTo')
            ->setType('string', 'The exported data')
            ->setMultilineDescription([
                'Export the current object properties to a string, using a given parser format',
                '<code>',
                '$book = BookQuery::create()->findPk(9012);',
                'echo $book->exportTo(\'JSON\');',
                '=> {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}\');',
                '</code>'
            ])
            ->addParameter(PhpParameter::create('parser')
                ->setDescription('A AbstractParser instance, or a format name (\'XML\', \'YAML\', \'JSON\', \'CSV\')')
            )
            ->addParameter(PhpParameter::create('includeLazyLoadColumns')
                ->setType('bool')
                ->setDescription('(optional) Whether to include lazy load(ed) columns. Defaults to TRUE.')
                ->setValue(true)
            )
            ->setBody('
if (!$parser instanceof AbstractParser) {
    $parser = AbstractParser::getParser($parser);
}

return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, [], true));
'
            )
        ;
    }

}