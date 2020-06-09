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
use Propel\Generator\Model\Diff\ColumnComparator;
use Propel\Generator\Platform\MysqlPlatform;
use \Propel\Tests\TestCase;

/**
 * Tests for the ColumnComparator service class.
 *
 */
class ColumnComparatorTest extends TestCase
{
    private MysqlPlatform $platform;

    public function setUp(): void
    {
        $this->platform = new MysqlPlatform();
    }

    public function testCompareNoDifference(): void
    {
        $c1 = new Column();
        $c1->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c1->getDomain()->setScale(2);
        $c1->getDomain()->setSize(3);
        $c1->setNotNull(true);
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column();
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->setScale(2);
        $c2->getDomain()->setSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $this->assertTrue(ColumnComparator::compareColumns($c1, $c2)->isEmpty());
    }

    public function testCompareType(): void
    {
        $c1 = new Column();
        $c1->getDomain()->copy($this->platform->getDomainForType('VARCHAR'));
        $c2 = new Column();
        $c2->getDomain()->copy($this->platform->getDomainForType('LONGVARCHAR'));
        $expectedChangedProperties = [
            'type'    => ['VARCHAR', 'LONGVARCHAR'],
            'sqlType' => ['VARCHAR', 'TEXT'],
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareScale(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setScale(2);
        $c2 = new Column();
        $c2->getDomain()->setScale(3);
        $expectedChangedProperties = ['scale' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareSize(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setSize(2);
        $c2 = new Column();
        $c2->getDomain()->setSize(3);
        $expectedChangedProperties = ['size' => [2, 3]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareSqlType(): void
    {
        $c1 = new Column();
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2 = new Column();
        $c2->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c2->getDomain()->setSqlType('INTEGER(10) UNSIGNED');
        $expectedChangedProperties = ['sqlType' => ['INTEGER', 'INTEGER(10) UNSIGNED']];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareNotNull(): void
    {
        $c1 = new Column();
        $c1->setNotNull(true);
        $c2 = new Column();
        $c2->setNotNull(false);
        $expectedChangedProperties = ['notNull' => [true, false]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueToNull(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column();
        $expectedChangedProperties = [
            'defaultValueType' => [ColumnDefaultValue::TYPE_VALUE, null],
            'defaultValueValue' => [123, null]
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueFromNull(): void
    {
        $c1 = new Column();
        $c2 = new Column();
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueType' => [null, ColumnDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123]
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueValue(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column();
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(456, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'defaultValueValue' => [123, 456]
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareDefaultValueType(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $c2 = new Column();
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_EXPR));
        $expectedChangedProperties = [
            'defaultValueType' => [ColumnDefaultValue::TYPE_VALUE, ColumnDefaultValue::TYPE_EXPR]
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    /**
     * @see http://www.propelorm.org/ticket/1141
     */
    public function testCompareDefaultExrpCurrentTimestamp(): void
    {
        $c1 = new Column();
        $c1->getDomain()->setDefaultValue(new ColumnDefaultValue("NOW()", ColumnDefaultValue::TYPE_EXPR));
        $c2 = new Column();
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue("CURRENT_TIMESTAMP", ColumnDefaultValue::TYPE_EXPR));
        $this->assertTrue(ColumnComparator::compareColumns($c1, $c2)->isEmpty());
    }

    public function testCompareAutoincrement(): void
    {
        $c1 = new Column();
        $c1->setAutoIncrement(true);
        $c2 = new Column();
        $c2->setAutoIncrement(false);
        $expectedChangedProperties = ['autoIncrement' => [true, false]];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }

    public function testCompareMultipleDifferences(): void
    {
        $c1 = new Column();
        $c1->getDomain()->copy($this->platform->getDomainForType('INTEGER'));
        $c1->setNotNull(false);
        $c2 = new Column();
        $c2->getDomain()->copy($this->platform->getDomainForType('DOUBLE'));
        $c2->getDomain()->setScale(2);
        $c2->getDomain()->setSize(3);
        $c2->setNotNull(true);
        $c2->getDomain()->setDefaultValue(new ColumnDefaultValue(123, ColumnDefaultValue::TYPE_VALUE));
        $expectedChangedProperties = [
            'type' => ['INTEGER', 'DOUBLE'],
            'sqlType' => ['INTEGER', 'DOUBLE'],
            'scale' => [null, 2],
            'size' => [null, 3],
            'notNull' => [false, true],
            'defaultValueType' => [null, ColumnDefaultValue::TYPE_VALUE],
            'defaultValueValue' => [null, 123]
        ];
        $this->assertEquals($expectedChangedProperties, ColumnComparator::compareColumns($c1, $c2)->toArray());
    }
}
