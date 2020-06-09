<?php declare(strict_types=1);
/**
 *  This file is part of the Propel package.
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *  
 * @license MIT License
 */

namespace Propel\Generator\Schema\Dumper;

use DOMDocument;
use DOMNode;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Model;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\IdMethodParameter;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Inheritance;
use Propel\Generator\Model\Schema;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Unique;
use Propel\Generator\Model\Vendor;

/**
 * A class for dumping a schema to an XML representation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 */
class XmlDumper implements DumperInterface
{
    /**
     * The DOMDocument object.
     */
    private DOMDocument $document;

    /**
     * Constructor.
     *
     * @param DOMDocument $document
     */
    public function __construct(DOMDocument $document = null)
    {
        if (null === $document) {
            $document = new DOMDocument('1.0', 'utf-8');
            $document->formatOutput = true;
        }

        $this->document = $document;
    }

    /**
     * Dumps a single Database model into an XML formatted version.
     *
     * @param  Database $database The database model
     * @return string   The dumped XML formatted output
     */
    public function dump(Database $database): string
    {
        $this->appendDatabaseNode($database, $this->document);

        return trim($this->document->saveXML());
    }

    /**
     * Dumps a single Schema model into an XML formatted version.
     *
     * @param  Schema  $schema                The schema object
     * @return string
     */
    public function dumpSchema(Schema $schema): string
    {
        $rootNode = $this->document->createElement('app-data');
        $this->document->appendChild($rootNode);
        foreach ($schema->getDatabases() as $database) {
            $this->appendDatabaseNode($database, $rootNode);
        }

        return trim($this->document->saveXML());
    }

    /**
     * Appends the generated <database> XML node to its parent node.
     *
     * @param Database $database   The Database model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendDatabaseNode(Database $database, DOMNode $parentNode): void
    {
        $databaseNode = $parentNode->appendChild($this->document->createElement('database'));
        $databaseNode->setAttribute('name', $database->getName());
        $databaseNode->setAttribute('defaultIdMethod', $database->getIdMethod());

        if ($schema = $database->getSchemaName()) {
            $databaseNode->setAttribute('schema', $schema);
        }

        if ($namespace = $database->getNamespace()) {
            $databaseNode->setAttribute('namespace', $namespace);
        }

        $defaultAccessorVisibility = $database->getAccessorVisibility();
        if ($defaultAccessorVisibility !== Model::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $database->getMutatorVisibility();
        if ($defaultMutatorVisibility !== Model::VISIBILITY_PUBLIC) {
            $databaseNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        $defaultStringFormat = $database->getStringFormat();
        if (Model::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $databaseNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        if ($database->isHeavyIndexing()) {
            $databaseNode->setAttribute('heavyIndexing', 'true');
        }

        /*
            FIXME - Before we can add support for domains in the schema, we need
            to have a method of the Column that indicates whether the column was mapped
            to a SPECIFIC domain (since Column->getDomain() will always return a Domain object)

            foreach ($this->domainMap as $domain) {
                $this->appendDomainNode($databaseNode);
            }
         */
        foreach ($database->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $databaseNode);
        }

        foreach ($database->getTables() as $table) {
            $this->appendTableNode($table, $databaseNode);
        }
    }

    /**
     * Appends the generated <vendor> XML node to its parent node.
     *
     * @param Vendor $vendorInfo The VendorInfo model instance
     * @param DOMNode   $parentNode The parent DOMNode object
     */
    private function appendVendorInformationNode(Vendor $vendorInfo, DOMNode $parentNode): void
    {
        //It's an empty Vendor created by VendorPart::getVendorByType method
        if ([] === $vendorInfo->getParameters()) {
            return;
        }

        $vendorNode = $parentNode->appendChild($this->document->createElement('vendor'));
        $vendorNode->setAttribute('type', $vendorInfo->getType());

        foreach ($vendorInfo->getParameters() as $key => $value) {
            $parameterNode = $this->document->createElement('parameter');
            $parameterNode->setAttribute('name', $key);
            $parameterNode->setAttribute('value', $value);
            $vendorNode->appendChild($parameterNode);
        }
    }

    /**
     * Appends the generated <table> XML node to its parent node.
     *
     * @param Table    $table      The Table model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendTableNode(Table $table, DOMNode $parentNode): void
    {
        $tableNode = $parentNode->appendChild($this->document->createElement('table'));
        $tableNode->setAttribute('name', $table->getName());

        $database = $table->getDatabase();
        $schema = $table->getSchemaName();
        if ($schema && $schema !== $database->getSchemaName()) {
            $tableNode->setAttribute('schema', $schema);
        }

        if (Model::ID_METHOD_NATIVE !== ($idMethod = $table->getIdMethod())) {
            $tableNode->setAttribute('idMethod', $idMethod);
        }

        if ($tableName = $table->getTableName()) {
            $tableNode->setAttribute('tableName', $tableName);
        }

        if ($namespace = $table->getNamespace()) {
            $tableNode->setAttribute('namespace', $namespace);
        }

        if ($table->isSkipSql()) {
            $tableNode->setAttribute('skipSql', 'true');
        }

        if ($table->isCrossRef()) {
            $tableNode->setAttribute('isCrossRef', 'true');
        }

        if ($table->isReadOnly()) {
            $tableNode->setAttribute('readOnly', 'true');
        }

        if ($table->isReloadOnInsert()) {
            $tableNode->setAttribute('reloadOnInsert', 'true');
        }

        if ($table->isReloadOnUpdate()) {
            $tableNode->setAttribute('reloadOnUpdate', 'true');
        }

        if ($referenceOnly = $table->isForReferenceOnly()) {
            $tableNode->setAttribute('forReferenceOnly', $referenceOnly ? 'true' : 'false');
        }

        if ($alias = $table->getAlias()) {
            $tableNode->setAttribute('alias', $alias);
        }

        if ($description = $table->getDescription()) {
            $tableNode->setAttribute('description', $description);
        }

        $defaultStringFormat = $table->getStringFormat();
        if (Model::DEFAULT_STRING_FORMAT !== $defaultStringFormat) {
            $tableNode->setAttribute('defaultStringFormat', $defaultStringFormat);
        }

        $defaultAccessorVisibility = $table->getAccessorVisibility();
        if ($defaultAccessorVisibility !== Model::VISIBILITY_PUBLIC) {
            $tableNode->setAttribute('defaultAccessorVisibility', $defaultAccessorVisibility);
        }

        $defaultMutatorVisibility = $table->getMutatorVisibility();
        if ($defaultMutatorVisibility !== Model::VISIBILITY_PUBLIC) {
            $tableNode->setAttribute('defaultMutatorVisibility', $defaultMutatorVisibility);
        }

        foreach ($table->getColumns() as $column) {
            $this->appendColumnNode($column, $tableNode);
        }

        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->appendForeignKeyNode($foreignKey, $tableNode);
        }

        foreach ($table->getIdMethodParameters() as $parameter) {
            $this->appendIdMethodParameterNode($parameter, $tableNode);
        }

        foreach ($table->getIndices() as $index) {
            $this->appendIndexNode($index, $tableNode);
        }

        foreach ($table->getUnices() as $index) {
            $this->appendUniqueIndexNode($index, $tableNode);
        }

        foreach ($table->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $tableNode);
        }

        foreach ($table->getBehaviors() as $behavior) {
            $this->appendBehaviorNode($behavior, $tableNode);
        }
    }

    /**
     * Appends the generated <behavior> XML node to its parent node.
     *
     * @param Behavior $behavior   The Behavior model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendBehaviorNode(Behavior $behavior, DOMNode $parentNode): void
    {
        $behaviorNode = $parentNode->appendChild($this->document->createElement('behavior'));
        $behaviorNode->setAttribute('name', $behavior->getName());

        if ($behavior->allowMultiple()) {
            $behaviorNode->setAttribute('id', $behavior->getId());
        }

        foreach ($behavior->getParameters() as $name => $value) {
            $parameterNode = $behaviorNode->appendChild($this->document->createElement('parameter'));
            $parameterNode->setAttribute('name', $name);
            $parameterNode->setAttribute('value', is_bool($value) ? (true === $value ? 'true' : 'false') : $value);
        }
    }

    /**
     * Appends the generated <column> XML node to its parent node.
     *
     * @param Column   $column     The Column model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendColumnNode(Column $column, DOMNode $parentNode): void
    {
        $columnNode = $parentNode->appendChild($this->document->createElement('column'));
        $columnNode->setAttribute('name', $column->getName());

        $columnNode->setAttribute('type', $column->getType());

        $domain = $column->getDomain();
        if ($size = $domain->getSize()) {
            $columnNode->setAttribute('size', (string)$size);
        }

        if ($scale = $domain->getScale()) {
            $columnNode->setAttribute('scale', (string)$scale);
        }

        $platform = $column->getPlatform();
        if (!$column->isDefaultSqlType($platform)) {
            $columnNode->setAttribute('sqlType', $platform->getDomainForType($column->getType())->getSqlType());
        }

        if ($description = $column->getDescription()) {
            $columnNode->setAttribute('description', $description);
        }

        if ($column->isPrimaryKey()) {
            $columnNode->setAttribute('primaryKey', 'true');
        }

        if ($column->isAutoIncrement()) {
            $columnNode->setAttribute('autoIncrement', 'true');
        }

        if ($column->isNotNull()) {
            $columnNode->setAttribute('required', 'true');
        }

        $defaultValue = $domain->getDefaultValue();
        if ($defaultValue) {
            $type = $defaultValue->isExpression() ? 'defaultExpr' : 'defaultValue';
            $columnNode->setAttribute($type, $defaultValue->getValue());
        }

        if ($column->isInheritance()) {
            $columnNode->setAttribute('inheritance', $column->getInheritanceType());
            foreach ($column->getInheritanceList() as $inheritance) {
                $this->appendInheritanceNode($inheritance, $columnNode);
            }
        }

        foreach ($column->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $columnNode);
        }
    }

    /**
     * Appends the generated <inheritance> XML node to its parent node.
     *
     * @param Inheritance $inheritance The Inheritance model instance
     * @param DOMNode    $parentNode  The parent DOMNode object
     */
    private function appendInheritanceNode(Inheritance $inheritance, DOMNode $parentNode): void
    {
        $inheritanceNode = $parentNode->appendChild($this->document->createElement('inheritance'));
        $inheritanceNode->setAttribute('key', $inheritance->getKey());
        $inheritanceNode->setAttribute('class', $inheritance->getClassName());

        if ($ancestor = $inheritance->getAncestor()) {
            $inheritanceNode->setAttribute('extends', $ancestor);
        }
    }

    /**
     * Appends the generated <foreign-key> XML node to its parent node.
     *
     * @param ForeignKey $foreignKey The ForeignKey model instance
     * @param DOMNode   $parentNode The parent DOMNode object
     */
    private function appendForeignKeyNode(ForeignKey $foreignKey, DOMNode $parentNode): void
    {
        $foreignKeyNode = $parentNode->appendChild($this->document->createElement('foreignKey'));
        $foreignKeyNode->setAttribute('target', $foreignKey->getForeignTableName());

        if ($foreignKey->hasName()) {
            $foreignKeyNode->setAttribute('name', $foreignKey->getName());
        }
        $foreignKeyNode->setAttribute('column', $foreignKey->getColumn());

        if ($refColumn = $foreignKey->getRefColumn()) {
            $foreignKeyNode->setAttribute('refColumn', $refColumn);
        }

        if ($defaultJoin = $foreignKey->getDefaultJoin()) {
            $foreignKeyNode->setAttribute('defaultJoin', $defaultJoin);
        }

        if ($onDeleteBehavior = $foreignKey->getOnDelete()) {
            $foreignKeyNode->setAttribute('onDelete', $onDeleteBehavior);
        }

        if ($onUpdateBehavior = $foreignKey->getOnUpdate()) {
            $foreignKeyNode->setAttribute('onUpdate', $onUpdateBehavior);
        }

        for ($i = 0, $size = $foreignKey->getLocalColumns()->size(); $i < $size; $i++) {
            $refNode = $foreignKeyNode->appendChild($this->document->createElement('reference'));
            $refNode->setAttribute('local', $foreignKey->getLocalColumn($i)->getName());
            $refNode->setAttribute('foreign', $foreignKey->getForeignColumns()->get($i));
        }

        foreach ($foreignKey->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $foreignKeyNode);
        }
    }

    /**
     * Appends the generated <id-method-parameter> XML node to its parent node.
     *
     * @param IdMethodParameter $parameter  The IdMethodParameter model instance
     * @param DOMNode          $parentNode The parent DOMNode object
     */
    private function appendIdMethodParameterNode(IdMethodParameter $parameter, DOMNode $parentNode): void
    {
        $idMethodParameterNode = $parentNode->appendChild($this->document->createElement('id-method-parameter'));
        $idMethodParameterNode->setAttribute('value', $parameter->getValue());
    }

    /**
     * Appends the generated <index> XML node to its parent node.
     *
     * @param Index    $index      The Index model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendIndexNode(Index $index, DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('index', $index, $parentNode);
    }

    /**
     * Appends the generated <unique> XML node to its parent node.
     *
     * @param Unique   $index     The Unique model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendUniqueIndexNode(Unique $index, DOMNode $parentNode): void
    {
        $this->appendGenericIndexNode('unique', $index, $parentNode);
    }

    /**
     * Appends a generice <index> or <unique> XML node to its parent node.
     *
     * @param string   $nodeType   The node type (index or unique)
     * @param Index    $index      The Index model instance
     * @param DOMNode $parentNode The parent DOMNode object
     */
    private function appendGenericIndexNode($nodeType, Index $index, DOMNode $parentNode): void
    {
        $indexNode = $parentNode->appendChild($this->document->createElement($nodeType));
        $indexNode->setAttribute('name', $index->getName());

        foreach ($index->getColumns() as $column) {
            $indexColumnNode = $indexNode->appendChild($this->document->createElement($nodeType.'-column'));
            $indexColumnNode->setAttribute('name', $column->getName());

            if ($size = $index->getColumnSizes()->get($column->getName())) {
                $indexColumnNode->setAttribute('size', (string)$size);
            }
        }

        foreach ($index->getVendor() as $vendorInformation) {
            $this->appendVendorInformationNode($vendorInformation, $indexNode);
        }
    }
}
