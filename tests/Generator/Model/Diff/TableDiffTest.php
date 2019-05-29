<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model\Diff;

use Propel\Common\Collection\Map;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\ColumnDiff;
use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\SqlDefaultPlatform;
use Propel\Tests\TestCase;

class TableDiffTest extends TestCase
{
    public function testDefaultObjectState()
    {
        $fromTable = new Table('article');
        $toTable   = new Table('article');

        $diff = $this->createTableDiff($fromTable, $toTable);
        
        $this->assertSame($fromTable, $diff->getFromTable());
        $this->assertSame($toTable, $diff->getToTable());
        $this->assertFalse($diff->hasAddedColumns());
        $this->assertFalse($diff->hasAddedFks());
        $this->assertFalse($diff->hasAddedIndices());
        $this->assertFalse($diff->hasAddedPkColumns());
        $this->assertFalse($diff->hasModifiedColumns());
        $this->assertFalse($diff->hasModifiedFks());
        $this->assertFalse($diff->hasModifiedIndices());
        $this->assertFalse($diff->hasModifiedPk());
        $this->assertFalse($diff->hasRemovedColumns());
        $this->assertFalse($diff->hasRemovedFks());
        $this->assertFalse($diff->hasRemovedIndices());
        $this->assertFalse($diff->hasRemovedPkColumns());
        $this->assertFalse($diff->hasRenamedColumns());
        $this->assertFalse($diff->hasRenamedPkColumns());
    }

    public function testSetAddedColumns()
    {
        $column = new Column('is_published', 'boolean');

        $diff = $this->createTableDiff();
        $diff->setAddedColumns(new Map(['is_published' => $column]));

        $this->assertCount(1, $diff->getAddedColumns());
        $this->assertSame($column, $diff->getAddedColumns()->get('is_published'));
        $this->assertTrue($diff->hasAddedColumns());
    }

    public function testSetRemovedColumns()
    {
        $column = new Column('is_active');

        $diff = $this->createTableDiff();
        $diff->setRemovedColumns(new Map(['is_active' => $column]));

        $this->assertEquals(1, $diff->getRemovedColumns()->size());
        $this->assertSame($column, $diff->getRemovedColumns()->get('is_active'));
        $this->assertTrue($diff->hasRemovedColumns());
    }

    public function testSetModifiedColumns()
    {
        $columnDiff = new ColumnDiff();

        $diff = $this->createTableDiff();
        $diff->setModifiedColumns(new Map(['title' => $columnDiff]));

        $this->assertEquals(1, $diff->getModifiedColumns()->size());
        $this->assertTrue($diff->hasModifiedColumns());
    }

    public function testAddRenamedColumn()
    {
        $fromColumn = new Column('is_published', 'boolean');
        $toColumn   = new Column('is_active', 'boolean');

        $diff = $this->createTableDiff();
        $diff->setRenamedColumns(new Map(['is_published' => [$fromColumn, $toColumn]]));

        $this->assertEquals(1, $diff->getRenamedColumns()->size());
        $this->assertTrue($diff->hasRenamedColumns());
    }

    public function testSetAddedPkColumns()
    {
        $column = new Column('id', 'integer', 7);
        $column->setPrimaryKey();

        $diff = $this->createTableDiff();
        $diff->setAddedPkColumns(new Map(['id' => [$column]]));

        $this->assertEquals(1, $diff->getAddedPkColumns()->size());
        $this->assertTrue($diff->hasAddedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetRemovedPkColumns()
    {
        $column = new Column('id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createTableDiff();
        $diff->setRemovedPkColumns(new Map(['id' => [$column]]));

        $this->assertCount(1, $diff->getRemovedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetRenamedPkColumns()
    {
        $diff = $this->createTableDiff();
        $diff->setRenamedPkColumns(new Map(['id' => [new Column('id', 'integer'), new Column('post_id', 'integer')]]));

        $this->assertCount(1, $diff->getRenamedPkColumns());
        $this->assertTrue($diff->hasModifiedPk());
    }

    public function testSetAddedIndices()
    {
        $table = new Table();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->setAddedIndices(new Map(['username_unique_idx' => $index]));

        $this->assertCount(1, $diff->getAddedIndices());
        $this->assertTrue($diff->hasAddedIndices());
    }

    public function testSetRemovedIndices()
    {
        $table = new Table();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->setRemovedIndices(new Map(['username_unique_idx' => $index]));

        $this->assertCount(1, $diff->getRemovedIndices());
        $this->assertTrue($diff->hasRemovedIndices());
    }

    public function testSetModifiedIndices()
    {
        $table = new Table('users');
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('username_unique_idx');
        $fromIndex->setTable($table);
        $fromIndex->addColumns([new Column('username')]);

        $toIndex = new Index('username_unique_idx');
        $toIndex->setTable($table);
        $toIndex->addColumns([new Column('client_id'), new Column('username')]);

        $diff = $this->createTableDiff();
        $diff->setModifiedIndices(new Map(['username_unique_idx' => [$fromIndex, $toIndex]]));

        $this->assertEquals(1, $diff->getModifiedIndices()->size());
        $this->assertTrue($diff->hasModifiedIndices());
    }

    public function testSetAddedFks()
    {
        $fk = new ForeignKey('fk_blog_author');

        $diff = $this->createTableDiff();
        $diff->setAddedFks(new Map(['fk_blog_author' => $fk]));

        $this->assertEquals(1, $diff->getAddedFks()->size());
        $this->assertTrue($diff->hasAddedFks());
    }

    public function testSetRemovedFk()
    {
        $diff = $this->createTableDiff();
        $diff->setRemovedFks(new Map(['fk_blog_post_author' => new ForeignKey('fk_blog_post_author')]));

        $this->assertEquals(1, $diff->getRemovedFks()->size());
        $this->assertTrue($diff->hasRemovedFks());
    }

    public function testSetModifiedFks()
    {
        $diff = $this->createTableDiff();
        $diff->setModifiedFks(new Map(['blog_post_author' => [new ForeignKey('blog_post_author'), new ForeignKey('blog_post_has_author')]]));

        $this->assertEquals(1, $diff->getModifiedFks()->size());
        $this->assertTrue($diff->hasModifiedFks());
    }

    public function testGetSimpleReverseDiff()
    {
        $tableA = new Table('users');
        $tableB = new Table('users');

        $diff = $this->createTableDiff($tableA, $tableB);
        $reverseDiff = $diff->getReverseDiff();

        $this->assertInstanceOf('Propel\Generator\Model\Diff\TableDiff', $reverseDiff);
        $this->assertSame($tableA, $reverseDiff->getToTable());
        $this->assertSame($tableB, $reverseDiff->getFromTable());
    }

    public function testReverseDiffHasModifiedColumns()
    {
        $c1 = new Column('title', 'varchar', 50);
        $c2 = new Column('title', 'varchar', 100);

        $columnDiff = new ColumnDiff($c1, $c2);
        $reverseColumnDiff = $columnDiff->getReverseDiff();

        $diff = $this->createTableDiff();
        $diff->getModifiedColumns()->set('title', $columnDiff);
        
        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedColumns());
        $this->assertEquals(['title' => $reverseColumnDiff], $reverseDiff->getModifiedColumns()->toArray());
    }

    public function testReverseDiffHasRemovedColumns()
    {
        $column = new Column('slug', 'varchar', 100);

        $diff = $this->createTableDiff();
        $diff->getAddedColumns()->set('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame(['slug' => $column], $reverseDiff->getRemovedColumns()->toArray());
        $this->assertSame($column, $reverseDiff->getRemovedColumns()->get('slug'));
    }

    public function testReverseDiffHasAddedColumns()
    {
        $column = new Column('slug', 'varchar', 100);

        $diff = $this->createTableDiff();
        $diff->getRemovedColumns()->set('slug', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame(['slug' => $column], $reverseDiff->getAddedColumns()->toArray());
        $this->assertSame($column, $reverseDiff->getAddedColumns()->get('slug'));
    }

    public function testReverseDiffHasRenamedColumns()
    {
        $columnA = new Column('login', 'varchar', 15);
        $columnB = new Column('username', 'varchar', 15);

        $diff = $this->createTableDiff();
        $diff->getRenamedColumns()->set('login', [$columnA, $columnB]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertSame([$columnB, $columnA], $reverseDiff->getRenamedColumns()->get('username'));
    }

    public function testReverseDiffHasAddedPkColumns()
    {
        $column = new Column('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createTableDiff();
        $diff->getRemovedPkColumns()->set('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertEquals(1, $reverseDiff->getAddedPkColumns()->size());
        $this->assertTrue($reverseDiff->hasAddedPkColumns());
    }

    public function testReverseDiffHasRemovedPkColumns()
    {
        $column = new Column('client_id', 'integer');
        $column->setPrimaryKey();

        $diff = $this->createTableDiff();
        $diff->getAddedPkColumns()->set('client_id', $column);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertEquals(1, $reverseDiff->getRemovedPkColumns()->size());
        $this->assertTrue($reverseDiff->hasRemovedPkColumns());
    }

    public function testReverseDiffHasRenamedPkColumn()
    {
        $fromColumn = new Column('post_id', 'integer');
        $fromColumn->setPrimaryKey();

        $toColumn = new Column('id', 'integer');
        $toColumn->setPrimaryKey();

        $diff = $this->createTableDiff();
        $diff->getRenamedPkColumns()->set('post_id', [$fromColumn, $toColumn]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRenamedPkColumns());
        $this->assertEquals([$toColumn, $fromColumn], $reverseDiff->getRenamedPkColumns()->get('id'));
    }

    public function testReverseDiffHasAddedIndices()
    {
        $table = new Table();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->getRemovedIndices()->set('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedIndices());
        $this->assertCount(1, $reverseDiff->getAddedIndices());
    }

    public function testReverseDiffHasRemovedIndices()
    {
        $table = new Table();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $index = new Index('username_unique_idx');
        $index->setTable($table);

        $diff = $this->createTableDiff();
        $diff->getAddedIndices()->set('username_unique_idx', $index);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedIndices());
        $this->assertCount(1, $reverseDiff->getRemovedIndices());
    }

    public function testReverseDiffHasModifiedIndices()
    {
        $table = new Table();
        $table->setDatabase(new Database('foo', new SqlDefaultPlatform()));

        $fromIndex = new Index('i1');
        $fromIndex->setTable($table);

        $toIndex = new Index('i1');
        $toIndex->setTable($table);

        $diff = $this->createTableDiff();
        $diff->getModifiedIndices()->set('i1', [$fromIndex, $toIndex]);

        $reverseDiff = $diff->getReverseDiff();

        $this->assertTrue($reverseDiff->hasModifiedIndices());
        $this->assertSame(['i1' => [$toIndex, $fromIndex]], $reverseDiff->getModifiedIndices()->toArray());
    }

    public function testReverseDiffHasRemovedFks()
    {
        $diff = $this->createTableDiff();
        $diff->getAddedFks()->set('fk_post_author', new ForeignKey('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasRemovedFks());
        $this->assertCount(1, $reverseDiff->getRemovedFks());
    }

    public function testReverseDiffHasAddedFks()
    {
        $diff = $this->createTableDiff();
        $diff->getRemovedFks()->set('fk_post_author', new ForeignKey('fk_post_author'));

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasAddedFks());
        $this->assertCount(1, $reverseDiff->getAddedFks());
    }

    public function testReverseDiffHasModifiedFks()
    {
        $fromFk = new ForeignKey('fk_1');
        $toFk = new ForeignKey('fk_1');

        $diff = $this->createTableDiff();
        $diff->getModifiedFks()->set('fk_1', [$fromFk, $toFk]);

        $reverseDiff = $diff->getReverseDiff();
        $this->assertTrue($reverseDiff->hasModifiedFks());
        $this->assertSame(['fk_1' => [$toFk, $fromFk]], $reverseDiff->getModifiedFks()->toArray());
    }
    
    private function createTableDiff(Table $fromTable = null, Table $toTable = null)
    {
        if (null === $fromTable) {
            $fromTable = new Table('users');
        }

        if (null === $toTable) {
            $toTable = new Table('users');
        }

        return new TableDiff($fromTable, $toTable);
    }

    public function testToString()
    {
        $tableA = new Table('A');
        $tableB = new Table('B');

        $diff = new TableDiff($tableA, $tableB);
        $diff->getAddedColumns()->set('id', new Column('id', 'integer'));
        $diff->getRemovedColumns()->set('category_id', new Column('category_id', 'integer'));

        $colFoo = new Column('foo', 'integer');
        $colBar = new Column('bar', 'integer');
        $tableA->addColumn($colFoo);
        $tableA->addColumn($colBar);

        $diff->getRenamedColumns()->set('foo', [$colFoo, $colBar]);
        $columnDiff = new ColumnDiff($colFoo, $colBar);
        $diff->getModifiedColumns()->set('foo', $columnDiff);

        $fk = new ForeignKey('category');
        $fk->setTable($tableA);
        $fk->setForeignTableName('B');
        $fk->addReference('category_id', 'id');

        //Clone doesn't work by now
        $fkChanged = new ForeignKey('category');
        $fkChanged->setTable($tableA);
        $fkChanged->setForeignTableName('B');
        $fkChanged->addReference('category_id', 'id');
        $fkChanged->setForeignTableName('C');
        $fkChanged->addReference('bla', 'id2');
        $fkChanged->setOnDelete('cascade');
        $fkChanged->setOnUpdate('cascade');

        $diff->getAddedFks()->set('category', $fk);
        $diff->getModifiedFks()->set('category', [$fk, $fkChanged]);
        $diff->getRemovedFks()->set('category', $fk);

        $index = new Index('test_index');
        $index->setTable($tableA);
        $index->addColumns([$colFoo]);

        $indexChanged = clone $index;
        $indexChanged->addColumns([$colBar]);

        $diff->getAddedIndices()->set('test_index', $index);
        $diff->getModifiedIndices()->set('test_index', [$index, $indexChanged]);
        $diff->getRemovedIndices()->set('test_index', $index);

        $string = (string) $diff;

        $expected = '  A:
    addedColumns:
      - id
    removedColumns:
      - category_id
    modifiedColumns:
      A.FOO:
        modifiedProperties:
    renamedColumns:
      foo: bar
    addedIndices:
      - test_index
    removedIndices:
      - test_index
    modifiedIndices:
      - test_index
    addedFks:
      - category
    removedFks:
      - category
    modifiedFks:
      category:
          localColumns: from ["category_id"] to ["category_id","bla"]
          foreignColumns: from ["id"] to ["id","id2"]
          onUpdate: from  to CASCADE
          onDelete: from  to CASCADE
';

        $this->assertEquals($expected, $string);
    }

    public function testMagicClone()
    {
        $diff = new TableDiff(new Table('A'), new Table('B'));

        $clonedDiff = clone $diff;

        $this->assertNotSame($clonedDiff, $diff);
        $this->assertNotSame($clonedDiff->getFromTable(), $diff->getFromTable());
        $this->assertNotSame($clonedDiff->getToTable(), $diff->getToTable());
    }
}
