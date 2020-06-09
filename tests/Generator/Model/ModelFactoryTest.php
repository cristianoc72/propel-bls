<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\ModelFactory;

class ModelFactoryTest extends ModelTestCase
{
    private ModelFactory $modelFactory;

    public function setUp(): void
    {
        $this->modelFactory = new ModelFactory();
    }

    /**
     * @dataProvider provideBehaviors
     */
    public function testCreateBehavior(string $name, string $class): void
    {
        $type = sprintf(
            'Propel\Generator\Behavior\%s\%sBehavior',
            $class,
            $class
        );

        $behavior = $this->modelFactory->createBehavior(['name' => $name]);

        $this->assertInstanceOf($type, $behavior);
    }

    public function provideBehaviors(): array
    {
        return [
            ['aggregate_column', 'AggregateColumn'],
            ['auto_add_pk', 'AutoAddPk'],
            ['concrete_inheritance', 'ConcreteInheritance'],
            ['delegate', 'Delegate'],
            ['nested_set', 'NestedSet'],
            ['query_cache', 'QueryCache'],
            ['sluggable', 'Sluggable'],
            ['sortable', 'Sortable'],
            ['timestampable', 'Timestampable'],
        ];
    }
}
