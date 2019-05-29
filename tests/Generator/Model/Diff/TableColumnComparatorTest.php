<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\ColumnDefaultValue;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Diff\TableComparator;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Column methods of the TableComparator service class.
 *
 */
class TableColumnComparatorTest extends TestCase
{

    /**
     * @var MysqlPlatform
     */
    protected $platform;

    public function setUp()
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSameColumns()
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c2);

        $this->assertNull(TableComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSameColumns()
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Bar');
        $t2->addColumn($c2);

        $diff = TableComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof TableDiff);
    }

    public function testCompareAddedColumn()
    {
        $t1 = new Table();
        $t2 = new Table();
        $c2 = new Column('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getAddedColumns()->size());
        $this->assertEquals(['Foo' => $c2], $tableDiff->getAddedColumns()->toArray());
    }

    public function testCompareRemovedColumn()
    {
        $t1 = new Table();
        $c1 = new Column('Bar');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $t2 = new Table();

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRemovedColumns()->size());
        $this->assertEquals(['Bar' => $c1], $tableDiff->getRemovedColumns()->toArray());
    }

    public function testCompareModifiedColumn()
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getModifiedColumns()->size());
        $columnDiff = ColumnComparator::computeDiff($c1, $c2);
        $this->assertEquals(['Foo' => $columnDiff], $tableDiff->getModifiedColumns()->toArray());
    }

    public function testCompareRenamedColumn()
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->replaceScale(2);
        $c1->getDomain()->replaceSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Bar');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->replaceScale(2);
        $c2->getDomain()->replaceSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRenamedColumns()->size());
        $this->assertEquals([$c1, $c2], $tableDiff->getRenamedColumns()->get('Foo'));
        $this->assertTrue($tableDiff->getAddedColumns()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedColumns()->isEmpty());
    }

    public function testCompareSeveralColumnDifferences()
    {
        $t1 = new Table();
        $c1 = new Column('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $c1->setNotNull(false);
        $t1->addColumn($c1);
        $c2 = new Column('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setNotNull(true);
        $t1->addColumn($c2);
        $c3 = new Column('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addColumn($c3);

        $t2 = new Table();
        $c4 = new Column('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c4->getDomain()->replaceScale(2);
        $c4->getDomain()->replaceSize(3);
        $c4->setNotNull(true);
        $c4->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c4);
        $c5 = new Column('col22');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c5->setNotNull(true);
        $t2->addColumn($c5);
        $c6 = new Column('col4');
        $c6->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $c6->getDomain()->setDefaultValue(new ColumnDefaultValue('123', ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c6);

        // col1 was modified, col2 was renamed, col3 was removed, col4 was added
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(4, $nbDiffs);
        $this->assertEquals([$c2, $c5], $tableDiff->getRenamedColumns()->get('col2'));
        $this->assertEquals($c6, $tableDiff->getAddedColumns()->get('col4'));
        $this->assertEquals($c3, $tableDiff->getRemovedColumns()->get('col3'));
        $columnDiff = ColumnComparator::computeDiff($c1, $c4);
        $this->assertEquals($columnDiff, $tableDiff->getModifiedColumns()->get('col1'));
    }

    public function testCompareSeveralRenamedSameColumns()
    {
        $t1 = new Table();
        $c1 = new Column('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->replaceSize(255);
        $t1->addColumn($c1);
        $c2 = new Column('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2->getDomain()->replaceSize(255);
        $t1->addColumn($c2);
        $c3 = new Column('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->replaceSize(255);
        $t1->addColumn($c3);

        $t2 = new Table();
        $c4 = new Column('col4');
        $c4->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c4->getDomain()->replaceSize(255);
        $t2->addColumn($c4);
        $c5 = new Column('col5');
        $c5->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c5->getDomain()->replaceSize(255);
        $t2->addColumn($c5);
        $c6 = new Column('col3');
        $c6->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c6->getDomain()->replaceSize(255);
        $t2->addColumn($c6);

        // col1 and col2 were renamed
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->compareColumns();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(2, $nbDiffs);
        $this->assertEquals(['col1' => [$c1, $c4], 'col2' => [$c2, $c5]], $tableDiff->getRenamedColumns()->toArray());
        $this->assertTrue($tableDiff->getAddedColumns()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedColumns()->isEmpty());
        $this->assertTrue($tableDiff->getModifiedColumns()->isEmpty());
    }
}
