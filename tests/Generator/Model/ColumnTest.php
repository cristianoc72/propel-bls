<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use Propel\Generator\Model\Table;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

/**
 * Tests for package handling.
 *
 * @author Hugo Hamon <webmaster@apprendre-php.com>
 */
class ColumnTest extends ModelTestCase
{
    public function testCreateNewColumn(): void
    {
        $column = new Column('title');
        $table = $this->getTableMock('FakeTable');
        $column->setTable($table);

        $this->assertSame('title', $column->getName());
        $this->assertEmpty($column->getAutoIncrementString());
        $this->assertSame('COLUMN_TITLE', $column->getConstantName());
        $this->assertSame('public', $column->getMutatorVisibility());
        $this->assertSame('public', $column->getAccessorVisibility());
        $this->assertEquals(0, $column->getSize());
        $this->assertTrue($column->getReferrers()->isEmpty());
        $this->assertFalse($column->isAutoIncrement());
        $this->assertFalse($column->isEnumeratedClasses());
        $this->assertFalse($column->isLazyLoad());
        $this->assertFalse($column->isNamePlural());
        $this->assertFalse($column->isNotNull());
        $this->assertFalse($column->isPrimaryKey());
        $this->assertFalse($column->isPrimaryString());
        $this->assertFalse($column->isUnique());
        $this->assertFalse($column->requiresTransactionInPostgres());
        $this->assertNull($column->getPlatform());
    }

    public function testGetNullDefaultValueString(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue(null))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertSame('null', $column->getDefaultValueString());
    }

    /**
     * @dataProvider provideDefaultValues
     */
    public function testGetDefaultValueString($mappingType, $value, $expected): void
    {
        $defaultValue = $this
            ->getMockBuilder('Propel\Generator\Model\ColumnDefaultValue')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $defaultValue
            ->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($value))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getDefaultValue')
            ->will($this->returnValue($defaultValue))
        ;
        $domain
            ->expects($this->any())
            ->method('setDefaultValue')
        ;
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setDefaultValue('foo');          // Test with a scalar
        $column->setDefaultValue($defaultValue);  // Test with an object

        $this->assertSame($expected, $column->getDefaultValueString());
    }

    public function provideDefaultValues()
    {
        return [
            ['DOUBLE', 3.14, '3.14'],
            ['VARCHAR', 'hello', "'hello'"],
            ['VARCHAR', "john's bike", "'john\\'s bike'"],
            ['BOOLEAN', 1, 'true'],
            ['BOOLEAN', 0, 'false'],
            ['ENUM', 'foo,bar', "'foo,bar'"],
        ];
    }

    public function testAddInheritance(): void
    {
        $column = new Column();

        $inheritance = $this
            ->getMockBuilder('Propel\Generator\Model\Inheritance')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $inheritance
            ->expects($this->any())
            ->method('setColumn')
            ->with($this->equalTo($column))
        ;

        $column->addInheritance($inheritance);

        $this->assertTrue($column->isEnumeratedClasses());
        $this->assertEquals(1, $column->getChildren()->size());

        $column->clearInheritanceList();
        $this->assertCount(0, $column->getChildren());
    }

    public function testIsDefaultSqlTypeFromDomain(): void
    {
        $toCopy = $this->getDomainMock();
        $toCopy
            ->expects($this->once())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->any())
            ->method('getDomainForType')
            ->with($this->equalTo('BOOLEAN'))
            ->will($this->returnValue($toCopy))
        ;

        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('copy')
            ->with($this->equalTo($toCopy))
        ;
        $domain
            ->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('BOOLEAN'))
        ;
        $domain
            ->expects($this->any())
            ->method('getSqlType')
            ->will($this->returnValue('INTEGER'))
        ;

        $column = new Column();
        $column->setTable($this->getTableMock('books', [
            'platform' => $platform
        ]));
        $column->setDomain($domain);
        $column->setDomainForType('BOOLEAN');

        $this->assertTrue($column->isDefaultSqlType($platform));
    }

    public function testIsDefaultSqlType(): void
    {
        $column = new Column();

        $this->assertTrue($column->isDefaultSqlType());
    }

    public function testGetNotNullString(): void
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getNotNullString')
            ->will($this->returnValue('NOT NULL'))
        ;

        $table = $this->getTableMock('books', ['platform' => $platform]);

        $column = new Column();
        $column->setTable($table);
        $column->setNotNull(true);

        $this->assertSame('NOT NULL', $column->getNotNullString());
    }

    /**
     * @dataProvider providePdoTypes
     *
     */
    public function testGetPdoType($mappingType, $pdoType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($pdoType, $column->getPDOType());
    }

    public function providePdoTypes()
    {
        return [
            ['CHAR', \PDO::PARAM_STR],
            ['VARCHAR', \PDO::PARAM_STR],
            ['LONGVARCHAR', \PDO::PARAM_STR],
            ['CLOB', \PDO::PARAM_STR],
            ['CLOB_EMU', \PDO::PARAM_STR],
            ['NUMERIC', \PDO::PARAM_INT],
            ['DECIMAL', \PDO::PARAM_STR],
            ['TINYINT', \PDO::PARAM_INT],
            ['SMALLINT', \PDO::PARAM_INT],
            ['INTEGER', \PDO::PARAM_INT],
            ['BIGINT', \PDO::PARAM_INT],
            ['REAL', \PDO::PARAM_STR],
            ['FLOAT', \PDO::PARAM_STR],
            ['DOUBLE', \PDO::PARAM_STR],
            ['BINARY', \PDO::PARAM_STR],
            ['VARBINARY', \PDO::PARAM_LOB],
            ['LONGVARBINARY', \PDO::PARAM_LOB],
            ['BLOB', \PDO::PARAM_LOB],
            ['DATE', \PDO::PARAM_STR],
            ['TIME', \PDO::PARAM_STR],
            ['TIMESTAMP', \PDO::PARAM_STR],
            ['BOOLEAN', \PDO::PARAM_BOOL],
            ['BOOLEAN_EMU', \PDO::PARAM_INT],
            ['OBJECT', \PDO::PARAM_LOB],
            ['ARRAY', \PDO::PARAM_STR],
            ['ENUM', \PDO::PARAM_STR],
            ['BU_DATE', \PDO::PARAM_STR],
            ['BU_TIMESTAMP', \PDO::PARAM_STR],
        ];
    }

    public function testEnumType(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('ENUM'))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType('ENUM');
        $column->setValueSet(['FOO', 'BAR']);

        $this->assertSame('string', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isEnumType());
        $this->assertTrue($column->getValueSet()->search('FOO'));
        $this->assertTrue($column->getValueSet()->search('BAR'));
    }

    public function testSetType(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('SET'))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType('SET');
        $column->setValueSet(['FOO', 'BAR']);

        $this->assertSame('int', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isSetType());
        $this->assertContains('FOO', $column->getValueSet());
        $this->assertContains('BAR', $column->getValueSet());
    }

    public function testSetStringValueSet(): void
    {
        $column = new Column();
        $column->setValueSet(' FOO , BAR , BAZ');

        $this->assertContains('FOO', $column->getValueSet());
        $this->assertContains('BAR', $column->getValueSet());
        $this->assertContains('BAZ', $column->getValueSet());
    }

    public function testPhpObjectType(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('OBJECT'))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType('OBJECT');

        $this->assertFalse($column->isPhpPrimitiveType());
        $this->assertTrue($column->isPhpObjectType());
    }

    /**
     * @dataProvider provideMappingTemporalTypes
     */
    public function testTemporalType($mappingType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('string', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isTemporalType());
    }

    public function provideMappingTemporalTypes()
    {
        return [
            ['DATE'],
            ['TIME'],
            ['TIMESTAMP'],
            ['BU_DATE'],
            ['BU_TIMESTAMP'],
        ];
    }

    /**
     * @dataProvider provideMappingLobTypes
     */
    public function testLobType($mappingType, $phpType, $isPhpPrimitiveType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($phpType, $column->getPhpType());
        $this->assertSame($isPhpPrimitiveType, $column->isPhpPrimitiveType());
        $this->assertTrue($column->isLobType());
    }

    public function provideMappingLobTypes(): array
    {
        return [
            ['VARBINARY', 'string', true],
            ['LONGVARBINARY', 'string', true],
            ['BLOB', 'resource', false],
        ];
    }

    /**
     * @dataProvider provideMappingBooleanTypes
     */
    public function testBooleanType($mappingType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('boolean', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isBooleanType());
    }

    public function provideMappingBooleanTypes()
    {
        return [
            ['BOOLEAN'],
            ['BOOLEAN_EMU'],
        ];
    }

    /**
     * @dataProvider provideMappingNumericTypes
     */
    public function testNumericType($mappingType, $phpType, $isPrimitiveNumericType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame($phpType, $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertSame($isPrimitiveNumericType, $column->isPhpPrimitiveNumericType());
        $this->assertTrue($column->isNumericType());
    }

    public function provideMappingNumericTypes()
    {
        return [
            ['SMALLINT', 'int', true],
            ['TINYINT', 'int', true],
            ['INTEGER', 'int', true],
            ['BIGINT', 'string', false],
            ['FLOAT', 'double', true],
            ['DOUBLE', 'double', true],
            ['NUMERIC', 'string', false],
            ['DECIMAL', 'string', false],
            ['REAL', 'double', true],
        ];
    }

    /**
     * @dataProvider provideMappingTextTypes
     */
    public function testTextType($mappingType): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setType')
            ->with($this->equalTo($mappingType))
        ;

        $domain
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($mappingType))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setType($mappingType);

        $this->assertSame('string', $column->getPhpType());
        $this->assertTrue($column->isPhpPrimitiveType());
        $this->assertTrue($column->isTextType());
    }

    public function provideMappingTextTypes()
    {
        return [
            ['CHAR'],
            ['VARCHAR'],
            ['LONGVARCHAR'],
            ['CLOB'],
            ['DATE'],
            ['TIME'],
            ['TIMESTAMP'],
            ['BU_DATE'],
            ['BU_TIMESTAMP'],
        ];
    }

    public function testGetSizeDefinition(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getSizeDefinition')
            ->will($this->returnValue('(10,2)'))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertSame('(10,2)', $column->getSizeDefinition());
    }

    public function testGetConstantName(): void
    {
        $table = $this->getTableMock('Article');

        $column = new Column('created_at');
        $column->setTable($table);
        $column->setColumnName('created_at');

        $this->assertSame('created_at', $column->getColumnName());
        $this->assertSame('COLUMN_CREATED_AT', $column->getConstantName());
        $this->assertSame('ArticleTableMap::COLUMN_CREATED_AT', $column->getFQConstantName());
    }

    public function testSetDefaultPhpName(): void
    {
        $column = new Column('createdAt');

        $this->assertSame('CreatedAt', $column->getPhpName());
        $this->assertSame('createdAt', $column->getName()->toCamelCase());
    }

    public function testSetCustomPhpName(): void
    {
        $column = new Column('created_at');
        $column->setPhpName('CreatedAt');

        $this->assertSame('CreatedAt', $column->getPhpName());
        $this->assertSame('createdAt', $column->getName()->toCamelCaseName());
    }

    public function testSetDefaultMutatorAndAccessorMethodsVisibility(): void
    {
        $column = new Column();
        $column->setAccessorVisibility('foo');
        $column->setMutatorVisibility('bar');

        $this->assertSame('public', $column->getAccessorVisibility());
        $this->assertSame('public', $column->getMutatorVisibility());
    }

    public function testSetMutatorAndAccessorMethodsVisibility(): void
    {
        $column = new Column();
        $column->setAccessorVisibility('private');
        $column->setMutatorVisibility('private');

        $this->assertSame('private', $column->getAccessorVisibility());
        $this->assertSame('private', $column->getMutatorVisibility());
    }

    public function testGetPhpDefaultValue(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('getPhpDefaultValue')
            ->will($this->returnValue(true))
        ;

        $column = new Column();
        $column->setDomain($domain);

        $this->assertTrue($column->getPhpDefaultValue());
    }

    public function testGetAutoIncrementStringThrowsEngineException(): void
    {
        $this->expectException('Propel\Generator\Exception\EngineException');

        $table = $this->getTableMock('books');
        $table
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('none'))
        ;

        $column = new Column();
        $column->setTable($table);
        $column->setAutoIncrement(true);
        $column->getAutoIncrementString();
    }

    public function testGetNativeAutoIncrementString(): void
    {
        $platform = $this->getPlatformMock();
        $platform
            ->expects($this->once())
            ->method('getAutoIncrement')
            ->will($this->returnValue('AUTO_INCREMENT'))
        ;

        $table = $this->getTableMock('books', ['platform' => $platform]);
        $table
            ->expects($this->once())
            ->method('getIdMethod')
            ->will($this->returnValue('native'))
        ;

        $column = new Column();
        $column->setAutoIncrement(true);
        $column->setTable($table);

        $this->assertEquals('AUTO_INCREMENT', $column->getAutoIncrementString());
    }

    public function testGetFullyQualifiedName(): void
    {
        $column = new Column('title');
        $column->setTable($this->getTableMock('books'));

        $this->assertSame('books.TITLE', $column->getFullyQualifiedName());
    }

    public function testIsPhpArrayType(): void
    {
        $column = new Column();
        $this->assertFalse($column->isPhpArrayType());

        $column->setType(PropelTypes::PHP_ARRAY);
        $this->assertTrue($column->isPhpArrayType());
    }

    public function testSetSize(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setSize')
            ->with($this->equalTo(50))
        ;
        $domain
            ->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(50))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setSize(50);

        $this->assertSame(50, $column->getSize());
    }

    public function testSetScale(): void
    {
        $domain = $this->getDomainMock();
        $domain
            ->expects($this->once())
            ->method('setScale')
            ->with($this->equalTo(2))
        ;
        $domain
            ->expects($this->once())
            ->method('getScale')
            ->will($this->returnValue(2))
        ;

        $column = new Column();
        $column->setDomain($domain);
        $column->setScale(2);

        $this->assertSame(2, $column->getScale());
    }

    public function testGetDefaultDomain(): void
    {
        $column = new Column();

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $column->getDomain());
    }

    public function testGetSingularName(): void
    {
        $column = new Column('titles');

        $this->assertSame('title', $column->getSingularName());
        $this->assertTrue($column->isNamePlural());
    }

    public function testSetTable(): void
    {
        $column = new Column();
        $column->setTable($this->getTableMock('books'));

        $this->assertInstanceOf('Propel\Generator\Model\Table', $column->getTable());
        $this->assertSame('books', $column->getTable()->getName());
    }

    public function testSetDomain(): void
    {
        $column = new Column();
        $column->setDomain($this->getDomainMock());

        $this->assertInstanceOf('Propel\Generator\Model\Domain', $column->getDomain());
    }

    public function testSetDescription(): void
    {
        $column = new Column();
        $column->setDescription('Some description');

        $this->assertSame('Some description', $column->getDescription());
    }

    public function testSetAutoIncrement(): void
    {
        $column = new Column();
        $column->setAutoIncrement(true);

        $this->assertTrue($column->isAutoIncrement());
    }

    public function testSetPrimaryString(): void
    {
        $column = new Column();
        $column->setPrimaryString(true);

        $this->assertTrue($column->isPrimaryString());
    }

    public function testSetNotNull(): void
    {
        $column = new Column();
        $column->setNotNull(true);

        $this->assertTrue($column->isNotNull());
    }

    public function testPhpSingularName(): void
    {
        $column = new Column();
        $column->setName('aliases');

        $this->assertEquals($column->getName(), 'aliases');
        $this->assertEquals($column->getSingularName(), 'aliase');

        $column = new Column();
        $column->setName('Aliases');
        $column->setSingularName('Alias');

        $this->assertEquals($column->getName(), 'Aliases');
        $this->assertEquals($column->getSingularName(), 'Alias');
    }

    public function testGetMethodName(): void
    {
        $column = new Column('title');
        $this->assertEquals('Title', $column->getMethodName());
    }

    public function testSetPhpType(): void
    {
        $column = new Column('title');
        $column->setType('VARCHAR');
        $column->setPhpType('string');
        $this->assertEquals('string', $column->getPhpType());
    }

    public function testGetPosition(): void
    {
        $column = new Column('foo');
        $column->setPosition(1);

        $this->assertSame(1, $column->getPosition());
    }

    public function testGetInheritanceType(): void
    {
        $column = new Column('foo');
        $column->setInheritanceType('single');

        $this->assertEquals('single', $column->getInheritanceType());
    }

    public function testIsInheritance(): void
    {
        $column = new Column('foo');
        $column->setInheritanceType('single');
        $this->assertTrue($column->isInheritance());

        $column->setInheritanceType('false');
        $this->assertFalse($column->isInheritance());
    }

    public function testSetPrimaryKey(): void
    {
        $column= new Column('foo');
        $this->assertFalse($column->isPrimaryKey());

        $column->setPrimaryKey(true);
        $this->assertTrue($column->isPrimaryKey());
    }

    public function testGetForeignKeys(): void
    {
        $table = new Table('book');
        $column = new Column('author_id');
        $column->setTable($table);
        $foreignKey = $this->getForeignKeyMock('author_fk', [
            'table' => $table,
            'target' => 'author',
            'local_columns' => ['author_id']
        ]);
        $table->addForeignKey($foreignKey);

        $this->assertTrue($column->isForeignKey());
        $this->assertSame([$foreignKey], $column->getForeignKeys());
    }

    public function testHasMultipleFk(): void
    {
        $table = new Table('book');
        $column = new Column('author_id');
        $column->setTable($table);
        $foreignKey = $this->getForeignKeyMock('author_fk', [
            'table' => $table,
            'target' => 'author',
            'local_columns' => ['author_id']
        ]);
        $table->addForeignKey($foreignKey);
        $this->assertFalse($column->hasMultipleFK());

        $foreignKey1 = $this->getForeignKeyMock('author_fk', [
            'table' => $table,
            'target' => 'foo',
            'local_columns' => ['author_id']
        ]);
        $table->addForeignKey($foreignKey1);
        $this->assertTrue($column->hasMultipleFK());
    }
}
