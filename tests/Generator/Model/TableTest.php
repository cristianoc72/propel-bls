<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use phootwork\lang\Text;
use Propel\Generator\Exception\InvalidArgumentException;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\CrossForeignKey;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Unique;
use Propel\Generator\Platform\SqlitePlatform;
use Propel\Runtime\Exception\RuntimeException;

/**
 * Unit test suite for Table model class.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class TableTest extends ModelTestCase
{
    public function testCreateNewTable(): void
    {
        $table = new Table('books');

        $this->assertSame('books', $table->getName());
        $this->assertFalse($table->isAllowPkInsert());
        $this->assertFalse($table->isCrossRef());
        $this->assertFalse($table->isReloadOnInsert());
        $this->assertFalse($table->isReloadOnUpdate());
        $this->assertFalse($table->isSkipSql());
        $this->assertFalse($table->isReadOnly());
        $this->assertSame(0, $table->getNumLazyLoadColumns());
        $this->assertEmpty($table->getChildrenNames());
        $this->assertFalse($table->hasForeignKeys());
    }

    /**
     * @dataProvider provideNamespaces
     *
     */
    public function testSetNamespace(string $namespace, string $expected): void
    {
        $table = new Table();
        $table->setNamespace($namespace);

        $this->assertSame($expected, $table->getNamespace());
    }

    public function provideNamespaces(): array
    {
        return [
            ['\Acme', '\Acme'],
            ['Acme', 'Acme'],
            ['Acme\\', 'Acme'],
            ['\Acme\Model', '\Acme\Model'],
            ['Acme\Model', 'Acme\Model'],
            ['Acme\Model\\', 'Acme\Model'],
        ];
    }

    public function testNames(): void
    {
        $table = new Table('Wurst\\Und\\Kaese');
        $this->assertEquals('Kaese', $table->getName());
        $this->assertEquals('Wurst\\Und', $table->getNamespace());


        $table = new Table();
        $this->assertEmpty($table->getName());

        $table->setName('Book');
        $this->assertEquals('Book', $table->getName());
        $this->assertEquals('book', $table->getTableName());

        $table->setName('BookAuthor');
        $this->assertEquals('BookAuthor', $table->getName());
        $this->assertEquals('book_author', $table->getTableName());

        $table->setTableName('book_has_author');
        $this->assertEquals('BookAuthor', $table->getName());
        $this->assertEquals('book_has_author', $table->getTableName());

        $table->setScope('bookstore_');
        $this->assertEquals('bookstore_book_has_author', $table->getScopedTableName());

        $table->setNamespace('Bookstore');
        $this->assertEquals('Bookstore\\BookAuthor', $table->getFullName());

        $table = new Table();
        $database = new Database();
        $database->setScope('bookings_');
        $database->setNamespace('Bookstore');
        $table->setDatabase($database);

        $this->assertEquals('Bookstore', $table->getNamespace());
        $this->assertEquals('bookings_', $table->getScope());
    }

    public function testGetGeneratorConfig(): void
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();
        $database = $this->getDatabaseMock('foo');

        $database
            ->expects($this->once())
            ->method('getGeneratorConfig')
            ->will($this->returnValue($config))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame($config, $table->getGeneratorConfig());
    }

    public function testApplyBehaviors(): void
    {
        $behavior = $this->getBehaviorMock('foo');
        $behavior
            ->expects($this->once())
            ->method('isTableModified')
            ->will($this->returnValue(false))
        ;

        $behavior
            ->expects($this->once())
            ->method('getTableModifier')
            ->will($this->returnValue($behavior))
        ;

        $behavior
            ->expects($this->once())
            ->method('modifyTable')
        ;

        $behavior
            ->expects($this->once())
            ->method('setTableModified')
            ->with($this->equalTo(true))
        ;

        $table = new Table();
        $table->addBehavior($behavior);
        $table->applyBehaviors();
    }

    public function testGetAdditionalBuilders(): void
    {
        $additionalBehaviors = [
            $this->getBehaviorMock('foo'),
            $this->getBehaviorMock('bar'),
            $this->getBehaviorMock('baz'),
        ];

        $behavior = $this->getBehaviorMock('mix', [
            'additional_builders' => $additionalBehaviors,
        ]);

        $table = new Table();
        $table->addBehavior($behavior);

        $this->assertCount(3, $table->getAdditionalBuilders());
        $this->assertTrue($table->hasAdditionalBuilders());
    }

    public function testHasNoAdditionalBuilders(): void
    {
        $table = new Table();
        $table->addBehavior($this->getBehaviorMock('foo'));

        $this->assertCount(0, $table->getAdditionalBuilders());
        $this->assertFalse($table->hasAdditionalBuilders());
    }

    public function testGetNameWithoutPlatform(): void
    {
        $table = new Table('books');

        $this->assertSame('books', $table->getName());
    }

    /**
     * @dataProvider provideSchemaNames
     *
     */
    public function testGetNameWithPlatform(bool $supportsSchemas, ?string $schemaName, string $expectedName): void
    {
        $platform = $this->getPlatformMock($supportsSchemas);
        $platform
            ->expects($supportsSchemas ? $this->once() : $this->never())
            ->method('getSchemaDelimiter')
            ->will($this->returnValue('.'))
        ;

        $database = $this->getDatabaseMock($schemaName, [
            'platform' => $platform,
        ]);

        $schema = $this->getSchemaMock($schemaName);
        $database
            ->method('getSchema')
            ->will($this->returnValue($schema))
        ;

        $table = new Table('books');
        if ($supportsSchemas) {
            $table->setSchemaName($schemaName);
        }
        $table->setDatabase($database);
        $table->getDatabase()->setSchema($schema);

        $this->assertSame($expectedName, $table->getFullTableName());
    }

    public function provideSchemaNames(): array
    {
        return [
            [false, 'bookstore', 'books'],
            [false, null, 'books'],
            [true, 'bookstore', 'bookstore.books'],
        ];
    }

    public function testGetOverrideSchemaName(): void
    {
        $table = new Table();
        $table->setDatabase($this->getDatabaseMock('bookstore'));
        $table->setSchemaName('my_schema');

        $this->assertEquals('my_schema', $table->guessSchemaName());
    }

    public function testSetDefaultPhpName(): void
    {
        $table = new Table('created_at');

        $this->assertSame('CreatedAt', $table->getPhpName());
        $this->assertSame('createdAt', $table->getName()->toCamelCase()->toString());
    }

    public function testSetCustomPhpName(): void
    {
        $table = new Table('created_at');
        $table->setPhpName('Created_At');

        $this->assertSame('Created_At', $table->getPhpName());
    }

    public function testSetDescription(): void
    {
        $table = new Table();

        $this->assertFalse($table->hasDescription());

        $table->setDescription('Some description');
        $this->assertNotNull($table->getDescription());
        $this->assertSame('Some description', $table->getDescription());
    }

    public function testSetInvalidStringFormat(): void
    {
        $this->expectException('Propel\Generator\Exception\InvalidArgumentException');

        $table = new Table();
        $table->setStringFormat('FOO');
    }

    public function testGetStringFormatFromDatabase(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getStringFormat')
            ->will($this->returnValue('XML'))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame('XML', $table->getStringFormat());
    }

    /**
     * @dataProvider provideStringFormats
     *
     */
    public function testGetStringFormat(string $format): void
    {
        $table = new Table();
        $table->setStringFormat($format);

        $this->assertSame($format, $table->getStringFormat());
    }

    public function provideStringFormats(): array
    {
        return [
            ['XML'],
            ['YAML'],
            ['JSON'],
            ['CSV'],
        ];
    }

    public function testAddSameColumnTwice(): void
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', ['phpName' => 'CreatedAt']);

        $this->expectException('Propel\Generator\Exception\EngineException');

        $table->addColumn($column);
        $table->addColumn($column);
    }

    public function testGetChildrenNames(): void
    {
        $column = new Column('created_at');
        $column->setInheritanceType('single');

        $inherit = new Inheritance();
        $inherit->setKey('one');
        $column->addInheritance($inherit);

        $inherit1 = new Inheritance();
        $inherit1->setKey('two');
        $column->addInheritance($inherit1);

        $table = new Table('books');
        $table->addColumn($column);

        $names = $table->getChildrenNames();
        $this->assertCount(2, $names);

        $this->assertSame('Propel\Generator\Model\Inheritance', $names[0]);
        $this->assertSame('Propel\Generator\Model\Inheritance', $names[1]);
    }

    public function testCantGetChildrenNames(): void
    {
        $column = $this->getColumnMock('created_at', ['inheritance' => true]);

        $column
            ->expects($this->any())
            ->method('isEnumeratedClasses')
            ->will($this->returnValue(false))
        ;

        $table = new Table('books');
        $table->addColumn($column);

        $this->assertEmpty($table->getChildrenNames());
    }

    public function testAddInheritanceColumn(): void
    {
        $table = new Table('books');
        $column = $this->getColumnMock('created_at', ['inheritance' => true]);
        $table->addColumn($column);
        $this->assertInstanceOf('Propel\Generator\Model\Column', $table->getChildrenColumn());
        $this->assertTrue($table->hasColumn($column));
        $this->assertTrue($table->hasColumn($column));
        $this->assertCount(1, $table->getColumns());
        $this->assertSame(1, $table->getNumColumns());
        $this->assertTrue($table->requiresTransactionInPostgres());
    }

    public function testHasBehaviors(): void
    {
        $behavior1 = $this->getBehaviorMock('Foo');
        $behavior2 = $this->getBehaviorMock('Bar');
        $behavior3 = $this->getBehaviorMock('Baz');

        $table = new Table();
        $table->addBehavior($behavior1);
        $table->addBehavior($behavior2);
        $table->addBehavior($behavior3);

        $this->assertCount(3, $table->getBehaviors());

        $this->assertTrue($table->hasBehavior('Foo'));
        $this->assertTrue($table->hasBehavior('Bar'));
        $this->assertTrue($table->hasBehavior('Baz'));
        $this->assertFalse($table->hasBehavior('Bab'));

        $this->assertSame($behavior1, $table->getBehavior('Foo'));
        $this->assertSame($behavior2, $table->getBehavior('Bar'));
        $this->assertSame($behavior3, $table->getBehavior('Baz'));
    }

    public function testUnregisterBehavior(): void
    {
        $behavior = new Behavior();
        $behavior->setName('foo');
        $table = new Table();
        $table->addBehavior($behavior);
        $this->assertTrue($table->hasBehavior('foo'));
        $this->assertSame($table, $behavior->getTable());

        $table->removeBehavior($behavior);
        $this->assertFalse($table->hasBehavior('foo'));
        $this->assertNull($behavior->getTable());
    }

    public function testAddColumn(): void
    {
        $table = new Table('books');
        $column = $this->getColumnMock('createdAt');
        $table->addColumn($column);
        $this->assertNull($table->getChildrenColumn());
        $this->assertTrue($table->requiresTransactionInPostgres());
        $this->assertTrue($table->hasColumn($column));
        $this->assertSame($column, $table->getColumn('createdAt'));
        $this->assertCount(1, $table->getColumns());
        $this->assertSame(1, $table->getNumColumns());
    }

    public function testCantRemoveColumnWhichIsNotInTable(): void
    {
        $this->expectException('Propel\Generator\Exception\EngineException');

        $column1 = $this->getColumnMock('title');

        $table = new Table('books');
        $table->removeColumn($column1);
    }

    public function testRemoveColumnByName(): void
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);
        $table->removeColumnByName('title');

        $this->assertCount(2, $table->getColumns());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('isbn'));
        $this->assertFalse($table->hasColumn('title'));
    }

    public function testRemoveColumn(): void
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);
        $table->removeColumn($column2);

        $this->assertCount(2, $table->getColumns());
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('isbn'));
        $this->assertFalse($table->hasColumn('title'));
    }

    public function testGetNumLazyLoadColumns(): void
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at', ['lazy' => true]);

        $column3 = $this->getColumnMock('deleted_at', ['lazy' => true]);

        $table = new Table('books');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertSame(2, $table->getNumLazyLoadColumns());
    }

    public function testHasEnumColumns(): void
    {
        $column1 = $this->getColumnMock('created_at');
        $column2 = $this->getColumnMock('updated_at');

        $column1
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(false))
        ;

        $column2
            ->expects($this->any())
            ->method('isEnumType')
            ->will($this->returnValue(true))
        ;

        $table = new Table('books');

        $table->addColumn($column1);
        $this->assertFalse($table->hasEnumColumns());

        $table->addColumn($column2);
        $this->assertTrue($table->hasEnumColumns());
    }

    public function testCantGetColumn(): void
    {
        $table = new Table('books');

        $this->assertFalse($table->hasColumn('FOO'));
    }

    public function testCantGetColumnException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $table = new Table('books');
        $this->assertNull($table->getColumn('FOO'));
    }

    public function testSetAbstract(): void
    {
        $table = new Table();
        $this->assertFalse($table->isAbstract());

        $table->setAbstract(true);
        $this->assertTrue($table->isAbstract());
    }

    public function testAddIndex(): void
    {
        $table = new Table();
        $index = new Index();
        $column = new Column();
        $column->setName('bla');
        $column->setTable($table);
        $index->addColumn($column);
        $table->addIndex($index);

        $this->assertCount(1, $table->getIndices());
    }

    public function testAddEmptyIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $table = new Table();
        $table->addIndex(new Index());

        $this->assertCount(1, $table->getIndices());
    }

    public function testAddAlreadyCreatedIndex()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $index = $this->getIndexMock('idx_fake_table');
        $table = new Table();
        $table->addIndex($index);
        $this->assertCount(1, $table->getIndices());

        $table->addIndex($index);
    }

    public function testCreateIndex(): void
    {
        $table = new Table();
        $column1 = $this->getColumnMock('id_mock');
        $column2 = $this->getColumnMock('foo_mock');
        $table->addColumns([$column1, $column2]);
        $table->createIndex('idx_foo_bar', [$column1, $column2]);

        $this->assertTrue($table->hasIndex('idx_foo_bar'));
        $this->assertTrue($table->isIndex([$column1, $column2]));
    }

    public function testIsIndex(): void
    {
        $table = new Table();
        $column1 = new Column('category_id');
        $column2 = new Column('type');
        $table->addColumn($column1);
        $table->addColumn($column2);

        $index = new Index('test_index');
        $index->addColumns([$column1, $column2]);
        $table->addIndex($index);

        $this->assertTrue($table->isIndex(['category_id', 'type']));
        $this->assertTrue($table->isIndex(['type', 'category_id']));
        $this->assertFalse($table->isIndex(['category_id', 'type2']));
        $this->assertFalse($table->isIndex(['asd']));
    }

    public function testRemoveIndex(): void
    {
        $table = new Table();
        $index = $this->getIndexMock('idx_fake', ['table' => $table]);
        $table->addIndex($index);
        $this->assertTrue($table->hasIndex('idx_fake'));

        $table->removeIndex('idx_fake');

        $this->assertFalse($table->hasIndex('idx_fake'));
    }

    public function testAddUniqueIndex(): void
    {
        $table = new Table();
        $table->addUnique($this->getUniqueIndexMock('author_unq'));

        $this->assertCount(1, $table->getUnices());
    }

    public function testRemoveUniqueIndex(): void
    {
        $table = new Table();
        $unique = $this->getUniqueIndexMock('author_unq', ['table' => $table]);
        $table->addUnique($unique);
        $this->assertCount(1, $table->getUnices());

        $table->removeUnique('author_unq');

        $this->assertCount(0, $table->getUnices());
    }

    public function testIsUnique(): void
    {
        $table = new Table();
        $column1 = $this->getColumnMock('category_id');
        $column2 = $this->getColumnMock('type');
        $table->addColumn($column1);
        $table->addColumn($column2);

        $unique = new Unique('test_unique');
        $unique->addColumns([$column1, $column2]);
        $table->addUnique($unique);

        $this->assertTrue($table->isUnique(['category_id', 'type']));
        $this->assertTrue($table->isUnique(['type', 'category_id']));
        $this->assertFalse($table->isUnique(['category_id', 'type2']));
        $this->assertTrue($table->isUnique([$column1, $column2]));
        $this->assertTrue($table->isUnique([$column2, $column1]));
    }

    public function testIsUniqueWhenUniqueColumn(): void
    {
        $table = new Table();
        $column = $this->getColumnMock('unique_id', ['table' => $table, 'unique' => true]);
        $table->addColumn($column);
        $this->assertTrue($table->isUnique([$column]));
    }

    public function testIsUniquePrimaryKey(): void
    {
        $table = new Table();
        $column = $this->getColumnMock('id', ['primary' => true, 'table' => $table]);

        $table->addColumn($column);
        $this->assertTrue($table->isUnique(['id']));
        $this->assertTrue($table->isUnique([$column]));
    }

    public function testisUniqueWithCompositePrimaryKey(): void
    {
        $table = new Table();
        $column1 = $this->getColumnMock('author_id', ['primary' => true, 'table' => $table]);
        $column2 = $this->getColumnMock('book_id', ['primary' => true, 'table' => $table]);
        $column3 = $this->getColumnMock('title', ['table' => $table]);
        $table->addColumns([$column1, $column2, $column3]);

        $this->assertTrue($table->isUnique(['author_id', 'book_id']));
        $this->assertTrue($table->isUnique([$column1, $column2]));
        $this->assertFalse($table->isUnique(['author_id', 'title']));
        $this->assertFalse($table->isUnique([$column2, $column3]));
    }

    public function testGetCompositePrimaryKey(): void
    {
        $column1 = $this->getColumnMock('book_id', ['primary' => true]);
        $column2 = $this->getColumnMock('author_id', ['primary' => true]);
        $column3 = $this->getColumnMock('rank');

        $table = new Table();
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(2, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertTrue($table->hasCompositePrimaryKey());
        $this->assertSame($column1, $table->getFirstPrimaryKeyColumn());
    }

    public function testGetSinglePrimaryKey(): void
    {
        $column1 = $this->getColumnMock('id', ['primary' => true]);
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(1, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertFalse($table->hasCompositePrimaryKey());
        $this->assertSame($column1, $table->getFirstPrimaryKeyColumn());
    }

    public function testGetNoPrimaryKey(): void
    {
        $column1 = $this->getColumnMock('id');
        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
        $table->setIdMethod('none');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(0, $table->getPrimaryKey());
        $this->assertFalse($table->hasAutoIncrementPrimaryKey());
        $this->assertNull($table->getAutoIncrementPrimaryKey());
        $this->assertFalse($table->hasPrimaryKey());
        $this->assertFalse($table->hasCompositePrimaryKey());
        $this->assertNull($table->getFirstPrimaryKeyColumn());
    }

    public function testGetAutoIncrementPrimaryKey(): void
    {
        $column1 = $this->getColumnMock('id', [
            'primary' => true,
            'auto_increment' => true
        ]);

        $column2 = $this->getColumnMock('title');
        $column3 = $this->getColumnMock('isbn');

        $table = new Table();
        $table->setIdMethod('native');
        $table->addColumn($column1);
        $table->addColumn($column2);
        $table->addColumn($column3);

        $this->assertCount(1, $table->getPrimaryKey());
        $this->assertTrue($table->hasPrimaryKey());
        $this->assertTrue($table->hasAutoIncrementPrimaryKey());
        $this->assertSame($column1, $table->getAutoIncrementPrimaryKey());
    }

    public function testAddIdMethodParameter(): void
    {
        $parameter = $this
            ->getMockBuilder('Propel\Generator\Model\IdMethodParameter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $parameter
            ->expects($this->once())
            ->method('setTable')
        ;

        $table = new Table();
        $table->addIdMethodParameter($parameter);

        $this->assertCount(1, $table->getIdMethodParameters());
    }

    public function testAddReferrerForeignKey(): void
    {
        $table = new Table('books');
        $table->addReferrer($this->getForeignKeyMock());

        $this->assertCount(1, $table->getReferrers());
    }

    public function testAddForeignKey(): void
    {
        $fk = $this->getForeignKeyMock('fk_author_id', [
            'foreign_table_name' => 'authors',
        ]);

        $table = new Table('books');
        $table->addForeignKey($fk);
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());
        $this->assertTrue($table->getForeignTableNames()->search(function ($elem) {
            return 'authors' === $elem;
        }));
    }

    public function testAddForeignKeys(): void
    {
        $authorRel = $this->getForeignKeyMock('author_id', ['target' => 'Authors']);
        $publisherRel = $this->getForeignKeyMock('publisher_id', ['target' => 'Publishers']);
        $fks = [$authorRel, $publisherRel];
        $table = new Table('Books');
        $table->addForeignKeys($fks);
        $this->assertCount(2, $table->getForeignKeys());
        $this->assertTrue($table->hasForeignKeys());
        $this->assertSame($authorRel, $table->getForeignKey('author_id'));
        $this->assertSame($publisherRel, $table->getForeignKey('publisher_id'));
    }

    public function testGetForeignKeysReferencingTable(): void
    {
        $fk1 = $this->getForeignKeyMock('fk1', ['target' => 'authors']);
        $fk2 = $this->getForeignKeyMock('fk2', ['target' => 'categories']);
        $fk3 = $this->getForeignKeyMock('fk1', ['target' => 'authors']);

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);
        $table->addForeignKey($fk3);

        $this->assertCount(2, $table->getForeignKeysReferencingTable('authors'));
    }

    public function testGetForeignKeysReferencingTableMoreThenOnce(): void
    {
        $fk1 = $this->getForeignKeyMock('fk1', ['foreign_table_name' => 'authors']);
        $fk2 = $this->getForeignKeyMock('fk2', ['foreign_table_name' => 'categories']);
        $fk3 = $this->getForeignKeyMock('fk1', ['foreign_table_name' => 'authors']);

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->expectException('Propel\Generator\Exception\EngineException');
        $table->addForeignKey($fk3);
        $this->fail('Expected to throw an EngineException due to duplicate foreign key.');
    }

    public function testGetColumnForeignKeys(): void
    {
        $fk1 = $this->getForeignKeyMock('fk1', [
            'local_columns' => ['foo', 'author_id', 'bar']
        ]);

        $fk2 = $this->getForeignKeyMock('fk2', [
            'local_columns' => ['foo', 'bar']
        ]);

        $table = new Table();
        $table->addForeignKey($fk1);
        $table->addForeignKey($fk2);

        $this->assertCount(1, $table->getColumnForeignKeys('author_id'));
        $this->assertContains($fk1, $table->getColumnForeignKeys('author_id'));
    }

    public function testSetBaseClasses(): void
    {
        $table = new Table();
        $table->setBaseClass('BaseObject');

        $this->assertSame('BaseObject', $table->getBaseClass());
    }

    public function testGetBaseClassesFromDatabase(): void
    {
        $database = $this->getDatabaseMock('bookstore');
        $database
            ->expects($this->once())
            ->method('getBaseClass')
            ->will($this->returnValue('BaseObject'))
        ;

        $table = new Table();
        $table->setDatabase($database);

        $this->assertSame('BaseObject', $table->getBaseClass());
    }

    public function testGetBaseClassesWithAlias(): void
    {
        $table = new Table('books');
        $table->setAlias('Book');

        $this->assertSame('Book', $table->getBaseClass());
    }

    public function testSetAlias(): void
    {
        $table = new Table('books');

        $this->assertFalse($table->isAlias());

        $table->setAlias('Book');
        $this->assertTrue($table->isAlias());
        $this->assertSame('Book', $table->getAlias());
    }

    public function testSetContainsForeignPK(): void
    {
        $table = new Table();

        $table->setContainsForeignPK(true);
        $this->assertTrue($table->getContainsForeignPK());
    }

    public function testSetCrossReference(): void
    {
        $table = new Table('books');

        $this->assertFalse($table->isCrossRef());
        $this->assertFalse($table->isCrossRef());

        $table->setCrossRef(true);
        $this->assertTrue($table->isCrossRef());
        $this->assertTrue($table->isCrossRef());
    }

    public function testSetSkipSql(): void
    {
        $table = new Table('books');
        $table->setSkipSql(true);

        $this->assertTrue($table->isSkipSql());
    }

    public function testSetForReferenceOnly(): void
    {
        $table = new Table('books');
        $table->setForReferenceOnly(true);

        $this->assertTrue($table->isForReferenceOnly());
    }

    public function testSetDatabaseWhenTableBelongsToDifferentDatabase(): void
    {
        $db1 = new Database('bookstore1');
        $db2 =new Database('bookstore2');
        $table = new Table('Book');
        $db1->addTable($table);
        $table->setDatabase($db2);

        $this->assertSame($db2, $table->getDatabase());
    }

    public function testGetAutoincrementColumnNames(): void
    {
        $table= new Table();
        $column1 = $this->getColumnMock('author_id', ['table' => $table, 'auto_increment' => true]);
        $column2 = $this->getColumnMock('book_id', ['table' => $table, 'auto_increment' => true]);
        $table->addColumns([$column1, $column2]);

        $this->assertEquals(['author_id', 'book_id'], $table->getAutoIncrementColumnNames());
    }

    public function testHasAutoincrement(): void
    {
        $table1 = new Table();
        $column1 = $this->getColumnMock('id', ['auto_increment' => true, 'table' => $table1]);
        $table1->addColumn($column1);

        $this->assertTrue($table1->hasAutoIncrement());

        $table2 = new Table();
        $column2 = $this->getColumnMock('title', ['table' => $table1]);
        $table2->addColumn($column2);

        $this->assertFalse($table2->hasAutoIncrement());
    }

    public function testQuoteIdentifier(): void
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $table = new Table();
        $table->setDatabase($database);
        $table->setIdentifierQuoting(true);
        $this->assertTrue($table->isIdentifierQuotingEnabled());
        $this->assertEquals('[text]', $table->quoteIdentifier('text'));
    }

    public function testNoQuoteIdentifier(): void
    {
        $database = $this->getDatabaseMock('test_db', ['platform' => new SqlitePlatform()]);
        $table = new Table();
        $table->setDatabase($database);
        $table->setIdentifierQuoting(false);
        $this->assertFalse($table->isIdentifierQuotingEnabled());
        $this->assertEquals('text', $table->quoteIdentifier('text'));
    }

    public function testGetIdentifierQuoting(): void
    {
        $table = new Table();
        $this->assertNull($table->getIdentifierQuoting());
        $table->setIdentifierQuoting(true);
        $this->assertTrue($table->getIdentifierQuoting());
    }

    public function testQuoteIdentifierNoPlatform(): void
    {
        $this->expectException(RuntimeException::class);

        $table = new Table();
        $database = $this->getDatabaseMock('test_db');
        $table->setDatabase($database);
        $table->quoteIdentifier('text');
    }

    public function testGetCrossForeignKey(): void
    {
        $user = new Table('User');
        $user->addColumn($this->getColumnMock('id', ['table' => $user, 'primary' => true, 'required' => true]));
        $user->addColumn($this->getColumnMock('name', ['table' => $user]));

        $role = new Table('Role');
        $role->addColumn($this->getColumnMock('id', ['table' => $role, 'primary' => true, 'required' => true]));
        $role->addColumn($this->getColumnMock('role', ['table' => $role]));

        $userXrole = new Table('UserXRole');
        $userXrole->addColumn($this->getColumnMock('user_id', ['table' => $userXrole, 'primary' => true, 'required' => true]));
        $userXrole->addColumn($this->getColumnMock('role_id', ['table' => $userXrole, 'primary' => true, 'required' => true]));
        $userXrole->setCrossRef(true);

        $rel1 = new ForeignKey();
        $rel1->setTable($userXrole);
        $rel1->setForeignTable($user);
        $rel1->addReference('user_id', 'id');
        $userXrole->addForeignKey($rel1);
        $user->addReferrer($rel1);

        $rel2 = new ForeignKey();
        $rel2->setTable($userXrole);
        $rel2->setForeignTable($role);
        $rel2->addReference('role_id', 'id');
        $userXrole->addForeignKey($rel2);
        $role->addReferrer($rel2);

        $crossRels = $user->getCrossForeignKeys();

        $this->assertCount(1, $crossRels);
        $this->assertInstanceOf(CrossForeignKey::class, $crossRels[0]);
        $this->assertTrue($user->hasCrossForeignKeys());
        $this->assertTrue($role->hasCrossForeignKeys());
    }

    /**
     * Returns a dummy Column object.
     *
     * @param  string $name    The column name
     * @param  array  $options An array of options
     * @return Column
     */
    protected function getColumnMock(string $name, array $options = []): Column
    {
        $defaults = [
            'primary' => false,
            'auto_increment' => false,
            'inheritance' => false,
            'lazy' => false,
            'phpName' => Text::create($name)->toStudlyCase()->toString(),
            'pg_transaction' => true,
            'unique' => false,
            'required' => false
        ];

        //Overwrite default options with custom options
        $options = array_merge($defaults, $options);

        $column = parent::getColumnMock($name, $options);

        $column
            ->expects($this->any())
            ->method('setTable')
        ;

        $column
            ->expects($this->any())
            ->method('setPosition')
        ;

        $column
            ->expects($this->any())
            ->method('isPrimaryKey')
            ->will($this->returnValue($options['primary']))
        ;

        $column
            ->expects($this->any())
            ->method('isAutoIncrement')
            ->will($this->returnValue($options['auto_increment']))
        ;

        $column
            ->expects($this->any())
            ->method('isInheritance')
            ->will($this->returnValue($options['inheritance']))
        ;

        $column
            ->expects($this->any())
            ->method('isLazyLoad')
            ->will($this->returnValue($options['lazy']))
        ;

        $column
            ->expects($this->any())
            ->method('getPhpName')
            ->will($this->returnValue($options['phpName']))
        ;

        $column
            ->expects($this->any())
            ->method('requiresTransactionInPostgres')
            ->will($this->returnValue($options['pg_transaction']))
        ;
        $column
            ->expects($this->any())
            ->method('isUnique')
            ->will($this->returnValue($options['unique']))
        ;
        $column
            ->expects($this->any())
            ->method('isNotNull')
            ->will($this->returnValue($options['required']))
        ;

        return $column;
    }
}
