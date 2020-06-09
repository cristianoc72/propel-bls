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
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the Column methods of the TableComparator service class.
 *
 */
class TablePkColumnComparatorTest extends TestCase
{
    protected MysqlPlatform $platform;

    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareSamePks(): void
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->setPrimaryKey(true);
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->setPrimaryKey(true);
        $t2->addColumn($c2);

        $this->assertNull(TableComparator::computeDiff($t1, $t2));
    }

    public function testCompareNotSamePks(): void
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->setPrimaryKey(true);
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Foo');
        $t2->addColumn($c2);

        $diff = TableComparator::computeDiff($t1, $t2);
        $this->assertTrue($diff instanceof TableDiff);
    }

    public function testCompareAddedPkColumn(): void
    {
        $t1 = new Table();
        $t2 = new Table();
        $c2 = new Column('Foo');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setPrimaryKey(true);
        $t2->addColumn($c2);
        $c3 = new Column('Bar');
        $c3->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $t2->addColumn($c3);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->comparePrimaryKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getAddedPkColumns()->size());
        $this->assertEquals(['Foo' => $c2], $tableDiff->getAddedPkColumns()->toArray());
    }

    public function testCompareRemovedPkColumn(): void
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c1->setPrimaryKey(true);
        $t1->addColumn($c1);
        $c2 = new Column('Bar');
        $c2->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $t1->addColumn($c2);
        $t2 = new Table();

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->comparePrimaryKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRemovedPkColumns()->size());
        $this->assertEquals(['Foo' => $c1], $tableDiff->getRemovedPkColumns()->toArray());
    }

    public function testCompareRenamedPkColumn(): void
    {
        $t1 = new Table();
        $c1 = new Column('Foo');
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->setScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c1->setPrimaryKey(true);
        $t1->addColumn($c1);
        $t2 = new Table();
        $c2 = new Column('Bar');
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->setScale(2);
        $c2->getDomain()->setSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2->setPrimaryKey(true);
        $t2->addColumn($c2);

        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->comparePrimaryKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(1, $nbDiffs);
        $this->assertEquals(1, $tableDiff->getRenamedPkColumns()->size());
        $this->assertEquals([$c1, $c2], $tableDiff->getRenamedPkColumns()->get('Foo'));
        $this->assertTrue($tableDiff->getAddedPkColumns()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedPkColumns()->isEmpty());
    }

    public function testCompareSeveralPrimaryKeyDifferences(): void
    {
        $t1 = new Table();
        $c1 = new Column('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c1->getDomain()->setSize(255);
        $c1->setNotNull(false);
        $t1->addColumn($c1);
        $c2 = new Column('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setNotNull(true);
        $c2->setPrimaryKey(true);
        $t1->addColumn($c2);
        $c3 = new Column('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c3->getDomain()->setSize(255);
        $c3->setPrimaryKey(true);
        $t1->addColumn($c3);

        $t2 = new Table();
        $c4 = new Column('col1');
        $c4->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c4->getDomain()->setScale(2);
        $c4->getDomain()->setSize(3);
        $c4->setNotNull(true);
        $c4->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $t2->addColumn($c4);
        $c5 = new Column('col22');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c5->setNotNull(true);
        $c5->setPrimaryKey(true);
        $t2->addColumn($c5);
        $c6 = new Column('col4');
        $c6->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $c6->getDomain()->setDefaultValue(new ColumnDefaultValue('123', ColumnDefaultValue::TYPE_VALUE));
        $c6->setPrimaryKey(true);
        $t2->addColumn($c6);

        // col2 was renamed, col3 was removed, col4 was added
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->comparePrimaryKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(3, $nbDiffs);
        $this->assertEquals([$c2, $c5], $tableDiff->getRenamedPkColumns()->get('col2'));
        $this->assertEquals(['col4' => $c6], $tableDiff->getAddedPkColumns()->toArray());
        $this->assertEquals(['col3' => $c3], $tableDiff->getRemovedPkColumns()->toArray());
    }

    public function testCompareSeveralRenamedSamePrimaryKeys(): void
    {
        $t1 = new Table();
        $c1 = new Column('col1');
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c1->setNotNull(true);
        $c1->setPrimaryKey(true);
        $t1->addColumn($c1);
        $c2 = new Column('col2');
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->setNotNull(true);
        $c2->setPrimaryKey(true);
        $t1->addColumn($c2);
        $c3 = new Column('col3');
        $c3->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c3->setNotNull(true);
        $c3->setPrimaryKey(true);
        $t1->addColumn($c3);

        $t2 = new Table();
        $c4 = new Column('col4');
        $c4->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c4->setNotNull(true);
        $c4->setPrimaryKey(true);
        $t2->addColumn($c4);
        $c5 = new Column('col5');
        $c5->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c5->setNotNull(true);
        $c5->setPrimaryKey(true);
        $t2->addColumn($c5);
        $c6 = new Column('col3');
        $c6->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c6->setNotNull(true);
        $c6->setPrimaryKey(true);
        $t2->addColumn($c6);

        // col1 and col2 were renamed
        $tc = new TableComparator();
        $tc->setFromTable($t1);
        $tc->setToTable($t2);
        $nbDiffs = $tc->comparePrimaryKeys();
        $tableDiff = $tc->getTableDiff();
        $this->assertEquals(2, $nbDiffs);
        $this->assertEquals(['col1' => [$c1, $c4], 'col2' => [$c2, $c5]], $tableDiff->getRenamedPkColumns()->toArray());
        $this->assertTrue($tableDiff->getAddedPkColumns()->isEmpty());
        $this->assertTrue($tableDiff->getRemovedPkColumns()->isEmpty());
    }
}
