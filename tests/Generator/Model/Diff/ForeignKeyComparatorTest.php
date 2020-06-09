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
use Propel\Generator\Model\Model;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Diff\ForeignKeyComparator;
use \Propel\Tests\TestCase;

/**
 * Tests for the ColumnComparator service class.
 *
 */
class ForeignKeyComparatorTest extends TestCase
{
    public function testCompareNoDifference(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareLocalColumn(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo2');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareForeignColumn(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar2');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareColumnMappings(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $c5 = new Column('Foo2');
        $c6 = new Column('Bar2');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->addReference($c5, $c6);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareOnUpdate(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $fk1->setOnUpdate(Model::FK_SETNULL);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->setOnUpdate(Model::FK_RESTRICT);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareOnDelete(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c2);
        $fk1->setOnDelete(Model::FK_SETNULL);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $c3 = new Column('Foo');
        $c4 = new Column('Bar');
        $fk2 = new ForeignKey();
        $fk2->addReference($c3, $c4);
        $fk2->setOnDelete(Model::FK_RESTRICT);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertTrue(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }

    public function testCompareSort(): void
    {
        $c1 = new Column('Foo');
        $c2 = new Column('Bar');
        $c3 = new Column('Baz');
        $c4 = new Column('Faz');
        $fk1 = new ForeignKey();
        $fk1->addReference($c1, $c3);
        $fk1->addReference($c2, $c4);
        $t1 = new Table('Baz');
        $t1->addForeignKey($fk1);
        $fk2 = new ForeignKey();
        $fk2->addReference($c2, $c4);
        $fk2->addReference($c1, $c3);
        $t2 = new Table('Baz');
        $t2->addForeignKey($fk2);
        $this->assertFalse(ForeignKeyComparator::computeDiff($fk1, $fk2));
    }
}
