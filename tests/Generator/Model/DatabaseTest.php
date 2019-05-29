<?php declare(strict_types=1);

/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Database;
use Propel\Generator\Model\Table;
use Propel\Generator\Platform\PgsqlPlatform;
use Propel\Generator\Model\Model;

/**
 * Unit test suite for Database model class.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class DatabaseTest extends ModelTestCase
{
    public function testCreateNewDatabase()
    {
        $database = new Database('bookstore');

        $this->assertSame('bookstore', $database->getName());
        $this->assertSame(Model::DEFAULT_STRING_FORMAT, $database->getStringFormat());
        $this->assertSame(Model::DEFAULT_ID_METHOD, $database->getIdMethod());
        $this->assertEmpty($database->getScope());
        $this->assertNull($database->getSchema());
        $this->assertSame('underscore', $database->getDefaultPhpNamingMethod());
        $this->assertNull($database->getDomain('BOOLEAN'));
        $this->assertNull($database->getGeneratorConfig());
        $this->assertEquals(0, $database->getTableSize());
        $this->assertEquals(0, $database->countTables());
        $this->assertFalse($database->isHeavyIndexing());
        $this->assertFalse($database->hasTableByPhpName('foo'));
        $this->assertFalse($database->hasBehavior('foo'));
        $this->assertNull($database->getBehavior('foo'));
    }

    public function testSetParentSchema()
    {
        $schema = $this->getSchemaMock();
        $database = new Database();
        $database->setSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Model\Schema', $database->getSchema());
        $this->assertSame($schema, $database->getSchema());
    }

    public function testAddBehavior()
    {
        $behavior = $this->getBehaviorMock('foo');

        $database = new Database();

        $this->assertInstanceOf('Propel\Generator\Model\Database', $database->addBehavior($behavior), 'Fluent api');
        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $database->getBehavior('foo'));
        $this->assertSame($behavior, $database->getBehavior('foo'));
        $this->assertTrue($database->hasBehavior('foo'));
    }

    public function testCantAddInvalidBehavior()
    {
        $this->expectException('Propel\Generator\Exception\BehaviorNotFoundException');

        $database = new Database();
        $behavior = $database->addBehavior(['name' => 'foo']);
    }

    /**
     * @dataProvider provideBehaviors
     *
     */
    public function testAddArrayBehavior($name, $class)
    {
        $type = sprintf(
            'Propel\Generator\Behavior\%s\%sBehavior',
            $class,
            $class
        );

        $database = new Database();
        $behavior = $database->addBehavior(['name' => $name]);

        $this->assertInstanceOf($type, $behavior);
    }

    public function testGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', ['behaviors' => [
             $this->getBehaviorMock('foo', [
                 'is_table_modified'  => false,
                'modification_order' => 2,
             ]),
             $this->getBehaviorMock('bar', [
                 'is_table_modified'  => false,
                'modification_order' => 1,
             ]),
             $this->getBehaviorMock('baz', ['is_table_modified'  => true]),
         ]]);

        $table2 = $this->getTableMock('authors', ['behaviors' => [
             $this->getBehaviorMock('mix', [
                 'is_table_modified'  => false,
                 'modification_order' => 1,
             ]),
         ]]);

        $database = new Database();
        $database->addTable($table1);
        $database->addTable($table2);

        $behavior = $database->getNextTableBehavior();

        $this->assertInstanceOf('Propel\Generator\Model\Behavior', $behavior);
        $this->assertSame('bar', $behavior->getName());
    }

    public function testCantGetNextTableBehavior()
    {
        $table1 = $this->getTableMock('books', ['behaviors' => [
             $this->getBehaviorMock('foo', ['is_table_modified' => true]),
         ]]);

        $database = new Database();
        $database->addTable($table1);

        $behavior = $database->getNextTableBehavior();

        $this->assertNull($database->getNextTableBehavior());
    }

    public function testCantGetTable()
    {
        $database = new Database();

        $this->assertFalse($database->hasTableByName('foo'));
        $this->assertNull($database->getTableByName('foo'));
    }

    public function testAddNamespacedTable()
    {
        $table = $this->getTableMock('books', ['namespace' => '\Acme']);

        $database = new Database();
        $database->addTable($table);

        $this->assertTrue($database->hasTableByName('books'));
    }

    public function testAddTable()
    {
        $table = $this->getTableMock('books', [
            'namespace' => 'Acme\Model',
        ]);

        $database = new Database();
        $database->setPackage('acme');
        $database->setNamespace('Acme\Model');
        $database->addTable($table);

        $this->assertSame(1, $database->countTables());
        $this->assertCount(1, $database->getTablesForSql());

        $this->assertTrue($database->hasTableByName('books'));
        $this->assertTrue($database->hasTableByName('books'));
        $this->assertFalse($database->hasTableByName('BOOKS'));
        $this->assertSame($table, $database->getTableByName('books'));
    }

    public function testAddSameTableTwice()
    {
        $table = new Table('Author');
        $database = new Database();
        $database->addTable($table);
        $this->assertCount(1, $database->getTables(), 'First call adds the table');
        $database->addTable($table);
        $this->assertCount(1, $database->getTables(), 'Second call does nothing');
    }

    public function testGetGeneratorConfig()
    {
        $config = $this->getMockBuilder('Propel\Generator\Config\GeneratorConfig')
            ->disableOriginalConstructor()->getMock();

        $schema = $this->getSchemaMock('bookstore', [
            'generator_config' => $config
        ]);

        $database = new Database();
        $database->setSchema($schema);

        $this->assertInstanceOf('Propel\Generator\Config\GeneratorConfig', $database->getGeneratorConfig());
        $this->assertSame($config, $database->getGeneratorConfig());
    }

    public function testAddDomain()
    {
        $domain1 = $this->getDomainMock('foo');
        $domain2 = $this->getDomainMock('bar');

        $database = new Database();
        $database->addDomain($domain1);
        $database->addDomain($domain2);

        $this->assertSame($domain1, $database->getDomain('foo'));
        $this->assertSame($domain2, $database->getDomain('bar'));
        $this->assertNull($database->getDomain('baz'));
    }

    public function testSetInvalidDefaultStringFormat()
    {
        $this->expectException('Propel\Generator\Exception\InvalidArgumentException');

        $database = new Database();
        $database->setStringFormat('FOO');
    }

    /**
     * @dataProvider provideSupportedFormats
     *
     */
    public function testSetDefaultStringFormat($format)
    {
        $database = new Database();
        $database->setStringFormat($format);

        $this->assertSame(strtoupper($format), $database->getStringFormat());
    }

    public function provideSupportedFormats()
    {
        return [
            ['xml'],
            ['yaml'],
            ['json'],
            ['csv'],
        ];
    }

    public function testSetHeavyIndexing()
    {
        $database = new Database();
        $database->setHeavyIndexing(true);

        $this->assertTrue($database->isHeavyIndexing());
    }

    public function testSetDefaultIdMethod()
    {
        $database = new Database();
        $database->setIdMethod('native');

        $this->assertSame('native', $database->getIdMethod());
    }

    public function testAddTableWithSameNameOnDifferentSchema()
    {
        $db = new Database();
        $db->setPlatform(new PgsqlPlatform());

        $t1 = new Table('t1');
        $db->addTable($t1);
        $this->assertEquals('t1', $t1->getName());

        $t1b = new Table('t1');
        $t1b->setSchemaName('bis');
        $db->addTable($t1b);
        $this->assertNotSame($t1b, $db->getTableByName('t1'), 'Tables with same name are not added to the database');
    }

    public function testHasTable()
    {
        $db = new Database();
        $table = $this->getTableMock('first');
        $db->addTable($table);

        $this->assertTrue($db->hasTable($table));
    }

    public function testTableGetters()
    {
        $db = new Database();
        $table = $this->getTableMock('First', ['tableName' => 'first_table', 'namespace' => 'my\\namespace']);
        $table->expects($this->any())->method('getFullTableName')->willReturn('mySchema.first_table');
        $db->addTable($table);

        $this->assertTrue($db->hasTableByFullName('my\\namespace\\First'));
        $this->assertEquals($table, $db->getTableByFullName('my\\namespace\\First'));

        $this->assertTrue($db->hasTableByTableName('first_table'));
        $this->assertEquals($table, $db->getTableByTableName('first_table'));

        $this->assertTrue($db->hasTableByFullTableName('mySchema.first_table'));
        $this->assertEquals($table, $db->getTableByFullTableName('mySchema.first_table'));
    }

    public function testGetTableNames()
    {
        $db = new Database();
        $db->addTable($this->getTableMock('First'));
        $db->addTable($this->getTableMock('Second'));
        $db->addTable($this->getTableMock('Third'));

        $this->assertEquals(['First', 'Second', 'Third'], $db->getTableNames());
    }

    public function testAddTables()
    {
        $tables = [];
        for ($i = 0; $i <= 4; $i++) {
            $tables[] = $this->getTableMock("Table$i");
        }
        $db = new Database();
        $db->addTables($tables);

        $this->assertCount(5, $db->getTables());
        $this->assertEquals($tables, $db->getTables());
    }

    public function testClone()
    {
        $generatorConfig = $this->getMockBuilder('Propel\\Generator\\Config\\GeneratorConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $vendor = $this->getMockBuilder('Propel\\Generator\\Model\\Vendor')->getMock();

        $db = new Database();
        for ($i = 0; $i <= 4; $i++) {
            $db->addTable(new Table("Table$i"));
        }
        $db->setPlatform($this->getPlatformMock());
        $db->setGeneratorConfig($generatorConfig);
        $db->setSchema($this->getSchemaMock());
        $db->addVendor($vendor);

        $clone = clone $db;

        $this->assertEquals($db, $clone, 'The clone object is equal.');
        $this->assertNotSame($db, $clone, 'The clone object is not the same.');

        $this->assertEquals($db->getTables(), $clone->getTables());
        $this->assertNotSame($db->getTables(), $clone->getTables());
        $this->assertEquals($db->getPlatform(), $clone->getPlatform());
        $this->assertNotSame($db->getPlatform(), $clone->getPlatform());
    }
}
