<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\ForeignKey;

/**
 * Unit test suite for the ForeignKey model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class ForeignKeyTest extends ModelTestCase
{
    public function testCreateNewForeignKey(): void
    {
        $fk = new ForeignKey('book_author');

        $this->assertSame('book_author', $fk->getName());
        $this->assertFalse($fk->hasOnUpdate());
        $this->assertFalse($fk->hasOnDelete());
        $this->assertFalse($fk->isComposite());
        $this->assertFalse($fk->isSkipSql());
    }

    public function testForeignKeyIsForeignPrimaryKey(): void
    {
        $database     = $this->getDatabaseMock('bookstore');
        $platform     = $this->getPlatformMock();
        $foreignTable = $this->getTableMock('authors');

        $localTable   = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $idColumn     = $this->getColumnMock('id');
        $authorIdColumn = $this->getColumnMock('author_id');

        $database
            ->expects($this->any())
            ->method('getTableByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignTable))
        ;

        $foreignTable
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue([$idColumn]))
        ;

        $foreignTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('id'))
            ->will($this->returnValue($idColumn))
        ;

        $localTable
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($authorIdColumn))
        ;

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->setForeignTableName('authors');
        $fk->addReference('author_id', 'id');

        $fkMapping = $fk->getColumnObjectsMapping();

        $this->assertTrue($fk->isForeignPrimaryKey());
        $this->assertCount(1, $fk->getForeignColumnObjects());
        $this->assertSame($authorIdColumn, $fkMapping[0]['local']);
        $this->assertSame($idColumn, $fkMapping[0]['foreign']);
        $this->assertSame($idColumn, $fk->getForeignColumn(0));
    }

    public function testForeignKeyDoesNotUseRequiredColumns(): void
    {
        $column = $this->getColumnMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(false))
        ;

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isLocalColumnsRequired());
    }

    public function testForeignKeyUsesRequiredColumns(): void
    {
        $column = $this->getColumnMock('author_id');
        $column
            ->expects($this->once())
            ->method('isNotNull')
            ->will($this->returnValue(true))
        ;

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalColumnsRequired());
    }

    public function testCantGetInverseForeignKey(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(false);
        $foreignTable = $this->getTableMock('authors');

        $localTable = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $database
            ->expects($this->any())
            ->method('getTableByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignTable))
        ;

        $inversedFk = new ForeignKey();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setTable($localTable);

        $foreignTable
            ->expects($this->any())
            ->method('getForeignKeys')
            ->will($this->returnValue([]))
        ;

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->addReference('author_id', 'id');
        $fk->setForeignTableName('authors');

        $this->assertSame('authors', $fk->getForeignTableName());
        $this->assertNull($fk->getInverseFK());
        $this->assertFalse($fk->isMatchedByInverseFK());
    }

    public function testGetInverseForeignKey(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $platform = $this->getPlatformMock(true);
        $foreignTable = $this->getTableMock('authors');

        $localTable = $this->getTableMock('books', [
            'platform' => $platform,
            'database' => $database
        ]);

        $database
            ->expects($this->any())
            ->method('getTableByName')
            ->with($this->equalTo('authors'))
            ->will($this->returnValue($foreignTable))
        ;

        $inversedFk = new ForeignKey();
        $inversedFk->addReference('id', 'author_id');
        $inversedFk->setTable($localTable);

        $foreignTable
            ->expects($this->any())
            ->method('getForeignKeys')
            ->will($this->returnValue([$inversedFk]))
        ;

        $fk = new ForeignKey();
        $fk->setTable($localTable);
        $fk->addReference('author_id', 'id');
        $fk->setForeignTableName('authors');

        $this->assertSame('authors', $fk->getForeignTableName());
        $this->assertInstanceOf('Propel\Generator\Model\Table', $fk->getForeignTable());
        $this->assertSame($inversedFk, $fk->getInverseFK());
        $this->assertTrue($fk->isMatchedByInverseFK());
    }

    public function testGetLocalColumn(): void
    {
        $column = $this->getColumnMock('id');

        $table = $this->getTableMock('books');
        $table
            ->expects($this->any())
            ->method('getColumn')
            ->with($this->equalTo('author_id'))
            ->will($this->returnValue($column))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('author_id', 'id');

        $this->assertCount(1, $fk->getLocalColumnObjects());
        $this->assertInstanceOf('Propel\Generator\Model\Column', $fk->getLocalColumn(0));
    }

    public function testForeignKeyIsNotLocalPrimaryKey(): void
    {
        $pks = [$this->getColumnMock('id')];

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('book_id', 'id');

        $this->assertFalse($fk->isLocalPrimaryKey());
    }

    public function testForeignKeyIsLocalPrimaryKey(): void
    {
        $pks = [
            $this->getColumnMock('book_id'),
            $this->getColumnMock('author_id'),
        ];

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getPrimaryKey')
            ->will($this->returnValue($pks))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isLocalPrimaryKey());
    }

    public function testGetOtherForeignKeys(): void
    {
        $fk = new ForeignKey();

        $fks[] = new ForeignKey();
        $fks[] = $fk;
        $fks[] = new ForeignKey();

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getForeignKeys')
            ->will($this->returnValue($fks))
        ;

        $fk->setTable($table);

        $this->assertCount(2, $fk->getOtherFks());
    }

    public function testClearReferences(): void
    {
        $fk = new ForeignKey();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');
        $fk->clearReferences();

        $this->assertCount(0, $fk->getLocalColumns());
        $this->assertCount(0, $fk->getForeignColumns());
    }

    public function testAddMultipleReferences(): void
    {
        $fk = new ForeignKey();
        $fk->addReference('book_id', 'id');
        $fk->addReference('author_id', 'id');

        $this->assertTrue($fk->isComposite());
        $this->assertCount(2, $fk->getLocalColumns());
        $this->assertCount(2, $fk->getForeignColumns());

        $this->assertSame('book_id', $fk->getLocalColumns()->get(0));
        $this->assertSame('id', $fk->getForeignColumns()->get(0));
        $this->assertSame('id', $fk->getMappedForeignColumn('book_id'));

        $this->assertSame('author_id', $fk->getLocalColumns()->get(1));
        $this->assertSame('id', $fk->getForeignColumns()->get(1));
        $this->assertSame('id', $fk->getMappedForeignColumn('author_id'));
    }

    public function testAddSingleStringReference(): void
    {
        $fk = new ForeignKey();
        $fk->addReference('author_id', 'id');

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame('author_id', $fk->getMappedLocalColumn('id'));
    }

    public function testAddSingleArrayReference(): void
    {
        $reference = ['local' => 'author_id', 'foreign' => 'id'];

        $fk = new ForeignKey();
        $fk->addReference($reference);

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame($reference['local'], $fk->getMappedLocalColumn($reference['foreign']));
    }

    public function testAddSingleColumnReference(): void
    {
        $fk = new ForeignKey();
        $fk->addReference(
            $this->getColumnMock('author_id'),
            $this->getColumnMock('id')
        );

        $this->assertFalse($fk->isComposite());
        $this->assertCount(1, $fk->getLocalColumns());
        $this->assertCount(1, $fk->getForeignColumns());

        $this->assertSame('author_id', $fk->getMappedLocalColumn('id'));
    }

    public function testSetTable(): void
    {
        $table = $this->getTableMock('book');
        $table
            ->expects($this->once())
            ->method('getSchemaName')
            ->will($this->returnValue('books'))
        ;

        $fk = new ForeignKey();
        $fk->setTable($table);

        $this->assertInstanceOf('Propel\Generator\Model\Table', $fk->getTable());
        $this->assertSame('books', $fk->getSchemaName());
        $this->assertSame('book', $fk->getTableName());
    }

    public function testSetDefaultJoin(): void
    {
        $fk = new ForeignKey();
        $fk->setDefaultJoin('INNER');

        $this->assertSame('INNER', $fk->getDefaultJoin());
    }

    public function testSetNames(): void
    {
        $fk = new ForeignKey();
        $fk->setName('book_author');
        $fk->setColumn('author');
        $fk->setRefColumn('books');

        $this->assertSame('book_author', $fk->getName());
        $this->assertSame('author', $fk->getColumn());
        $this->assertSame('books', $fk->getRefColumn());
    }

    public function testSkipSql(): void
    {
        $fk = new ForeignKey();
        $fk->setSkipSql(true);

        $this->assertTrue($fk->isSkipSql());
    }

    public function testGetOnActionBehaviors(): void
    {
        $fk = new ForeignKey();
        $fk->setOnUpdate('SETNULL');
        $fk->setOnDelete('CASCADE');

        $this->assertSame('SET NULL', $fk->getOnUpdate());
        $this->assertTrue($fk->hasOnUpdate());

        $this->assertSame('CASCADE', $fk->getOnDelete());
        $this->assertTrue($fk->hasOnDelete());
    }

    /**
     * @dataProvider provideOnActionBehaviors
     *
     */
    public function testNormalizeForeignKey(?string $behavior, string $normalized): void
    {
        $fk = new ForeignKey();

        $this->assertSame($normalized, $fk->normalizeFKey($behavior));
    }

    public function provideOnActionBehaviors()
    {
        return [
            [null, ''],
            ['none', ''],
            ['NONE', ''],
            ['setnull', 'SET NULL'],
            ['SETNULL', 'SET NULL'],
            ['cascade', 'CASCADE'],
            ['CASCADE', 'CASCADE'],
        ];
    }
}
