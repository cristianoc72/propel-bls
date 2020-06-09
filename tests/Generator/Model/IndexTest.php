<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Index;

/**
 * Unit test suite for the Index model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class IndexTest extends ModelTestCase
{
    public function testCreateNamedIndex(): void
    {
        $index = new Index('foo_idx');
        $index->setTable($this->getTableMock('db_books'));

        $this->assertEquals('foo_idx', $index->getName());
        $this->assertFalse($index->isUnique());
        $this->assertInstanceOf('Propel\Generator\Model\Table', $index->getTable());
        $this->assertSame('db_books', $index->getTable()->getName());
        $this->assertCount(0, $index->getColumns());
        $this->assertTrue($index->getColumns()->isEmpty());
    }

    /**
     * @dataProvider provideTableSpecificAttributes
     *
     */
    public function testCreateDefaultIndexName(string $tableName, int $maxColumnNameLength, string $indexName): void
    {
        $platform = $this->getPlatformMock(true, ['max_column_name_length' => $maxColumnNameLength]);
        $database = $this->getDatabaseMock('bookstore', ['platform' => $platform]);

        $table = $this->getTableMock($tableName, [
            'common_name' => $tableName,
            'indices'     => [ new Index(), new Index() ],
            'database'    => $database,
        ]);

        $index = new Index();
        $index->setTable($table);

        $this->assertSame($indexName, $index->getName());
    }

    public function provideTableSpecificAttributes(): array
    {
        return [
            [ 'books', 64, 'books_i_no_columns' ],
            [ 'super_long_table_name', 16, 'super_long_table' ],
        ];
    }

    public function testAddIndexedColumns(): void
    {
        $columns = [
            $this->getColumnMock('foo', [ 'size' => 100 ]),
            $this->getColumnMock('bar', [ 'size' => 5   ]),
            $this->getColumnMock('baz', [ 'size' => 0   ])
        ];

        $index = new Index();
        $index->setTable($this->getTableMock('index_table'));
        $index->addColumns($columns);

        $this->assertFalse($index->getColumns()->isEmpty());
        $this->assertCount(3, $index->getColumns());
        $this->assertSame(100, $index->getColumn('foo')->getSize());
        $this->assertSame(5, $index->getColumn('bar')->getSize());
        $this->assertEquals(0, $index->getColumn('baz')->getSize());
    }

    public function testNoColumnAtFirstPosition(): void
    {
        $index = new Index();

        $this->assertFalse($index->hasColumnAtPosition(0, 'foo'));
    }

    /**
     * @dataProvider provideColumnAttributes
     */
    public function testNoColumnAtPositionCaseSensitivity(string $name): void
    {
        $index = new Index();
        $index->setTable($this->getTableMock('db_books'));
        $index->addColumn($this->getColumnMock('foo', [ 'size' => 5 ]));

        $this->assertFalse($index->hasColumnAtPosition(0, $name, 5));
    }

    public function provideColumnAttributes()
    {
        return [
            [ 'bar' ],
            [ 'BAR' ],
        ];
    }

    public function testNoSizedColumnAtPosition(): void
    {
        $size = 5;

        $index = new Index();
        $index->setTable($this->getTableMock('db_books'));
        $index->addColumn($this->getColumnMock('foo', [ 'size' => $size ]));

        $size++;
        $this->assertFalse($index->hasColumnAtPosition(0, 'foo', $size));
    }

    public function testHasColumnAtFirstPosition(): void
    {
        $index = new Index();
        $index->setTable($this->getTableMock('db_books'));
        $index->addColumn($this->getColumnMock('foo', [ 'size' => 0 ]));

        $this->assertTrue($index->hasColumnAtPosition(0, 'foo'));
    }

    public function testGetSuperordinate(): void
    {
        $table = $this->getTableMock('db_books');
        $index = new Index();
        $index->setTable($table);

        $this->assertSame($table, $index->getSuperordinate());
    }
}
