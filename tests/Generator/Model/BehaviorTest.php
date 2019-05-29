<?php declare(strict_types=1);

/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Tests\Generator\Model;

use org\bovigo\vfs\vfsStream;
use phootwork\lang\Text;
use Propel\Common\Collection\Map;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Table;
use Propel\Generator\Schema\SchemaReader;

/**
 * Tests for Behavior class
 *
 * @author Martin Poeschl <mpoeschl@marmot.at>
 */
class BehaviorTest extends ModelTestCase
{
    public function testName()
    {
        $b = new Behavior();
        $this->assertInstanceOf(Text::class,$b->getName(), 'Behavio name is a Text object');
        $this->assertEquals('', $b->getName()->toString(), 'Behavior name is null string by default');
        $b->setName('foo');
        $this->assertEquals('foo', $b->getName()->toString(), 'setName() sets the name, and getName() gets it');
    }

    public function testTable()
    {
        $b = new Behavior();
        $this->assertNull($b->getTable(), 'Behavior Table is null by default');
        $t = new Table();
        $t->setName('FooTable');
        $b->setTable($t);
        $this->assertEquals($b->getTable(), $t, 'setTable() sets the name, and getTable() gets it');
    }

    public function testParameters()
    {
        $b = new Behavior();
        $this->assertInstanceOf(Map::class, $b->getParameters());
        $this->assertEquals($b->getParameters()->toArray(), [], 'Behavior parameters is an empty array by default');
        $b->addParameter(['name' => 'foo', 'value' => 'bar']);
        $this->assertEquals($b->getParameters(), new Map(['foo' => 'bar']), 'addParameter() sets a parameter from an associative array');
        $b->addParameter(['name' => 'foo2', 'value' => 'bar2']);
        $this->assertEquals($b->getParameters()->toArray(), ['foo' => 'bar', 'foo2' => 'bar2'], 'addParameter() adds a parameter from an associative array');
        $b->addParameter(['name' => 'foo', 'value' => 'bar3']);
        $this->assertEquals($b->getParameters()->toArray(), ['foo' => 'bar3', 'foo2' => 'bar2'], 'addParameter() changes a parameter from an associative array');
        $this->assertEquals($b->getParameter('foo'), 'bar3', 'getParameter() retrieves a parameter value by name');
        $b->setParameters(['foo3' => 'bar3', 'foo4' => 'bar4']);
        $this->assertEquals($b->getParameters()->toArray(), ['foo3' => 'bar3', 'foo4' => 'bar4'], 'setParameters() changes the whole parameter array');
    }

    /**
     * test if the tables get the package name from the properties file
     *
     */
    public function testSchemaReader()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
    <column name="title" type="VARCHAR" size="100" primaryString="true" />
    <column name="created_on" type="TIMESTAMP" />
    <column name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_column" value="created_on" />
      <parameter name="update_column" value="updated_on" />
    </behavior>
  </table>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $table = $appData->getDatabase('test1')->getTableByName('Table1');
        $behaviors = $table->getBehaviors();
        $this->assertEquals(1, count($behaviors), 'SchemaReader ads as many behaviors as there are behaviors tags');
        $behavior = $table->getBehavior('timestampable');
        $this->assertEquals('Table1', $behavior->getTable()->getName(), 'SchemaReader sets the behavior table correctly');
        $this->assertEquals(
            ['create_column' => 'created_on', 'update_column' => 'updated_on', 'disable_created_at' => false, 'disable_updated_at' => false],
            $behavior->getParameters(),
            'SchemaReader sets the behavior parameters correctly'
        );
    }

    /**
     * @expectedException \Propel\Generator\Exception\BehaviorNotFoundException
     */
    public function testUnknownBehavior()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
    <behavior name="foo" />
  </table>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
    }

    public function testModifyTable()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <table name="table2">
    <column name="id" type="INTEGER" primaryKey="true" />
    <column name="title" type="VARCHAR" size="100" primaryString="true" />
    <behavior name="timestampable" />
  </table>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $table = $appData->getDatabase('test1')->getTableByName('Table2');
        $this->assertEquals(4, $table->getColumns()->size(), 'A behavior can modify its table by implementing modifyTable()');
    }

    public function testModifyDatabase()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <behavior name="timestampable" />
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
  </table>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $appData->getPlatform()->doFinalInitialization($appData);
        $table = $appData->getDatabase('test1')->getTableByName('Table1');
        $this->assertTrue(array_key_exists('timestampable', $table->getBehaviors()), 'A database behavior is automatically copied to all its table');
    }

    public function testGetColumnForParameter()
    {
        $schemaReader = new SchemaReader();
        $content = <<<EOF
<database name="test1">
  <table name="table1">
    <column name="id" type="INTEGER" primaryKey="true" />
    <column name="title" type="VARCHAR" size="100" primaryString="true" />
    <column name="created_on" type="TIMESTAMP" />
    <column name="updated_on" type="TIMESTAMP" />
    <behavior name="timestampable">
      <parameter name="create_column" value="created_on" />
      <parameter name="update_column" value="updated_on" />
    </behavior>
  </table>
</database>
EOF;
        $schema = vfsStream::newFile('schema.xml')->at($this->getRoot())->setContent($content);
        $appData = $schemaReader->parse($schema->url());
        $table = $appData->getDatabase('test1')->getTableByName('Table1');
        $behavior = $table->getBehavior('timestampable');
        $this->assertEquals($table->getColumn('created_on'), $behavior->getColumnForParameter('create_column'), 'getColumnForParameter() returns the configured column for behavior based on a parameter name');
    }
}
