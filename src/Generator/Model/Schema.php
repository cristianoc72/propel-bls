<?php declare(strict_types=1);

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use Propel\Generator\Exception\EngineException;
use Propel\Generator\Model\Parts\PlatformMutatorPart;
use Propel\Generator\Platform\PlatformInterface;
use Propel\Generator\Schema\Dumper\XmlDumper;
use Propel\Common\Collection\Set;
use Propel\Generator\Model\Parts\NamePart;
use Propel\Generator\Model\Parts\SchemaPart;
use Propel\Common\Collection\ArrayList;

/**
 * A class for holding application data structures.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Leon Messerschmidt <leon@opticode.co.za> (Torque)
 * @author John McNally <jmcnally@collab.net> (Torque)
 * @author Daniel Rall <dlr@finemaltcoding.com> (Torque)
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Schema
{
    use PlatformMutatorPart;
    use NamePart;
    use SchemaPart;

    /** @var ArrayList */
    private $databases;

    /** @var Set */
    protected $schemas;

    /** @var string */
    protected $filename;

    /** @var bool */
    protected $referenceOnly = true;

    /**
     * Creates a new instance for the specified database type.
     *
     * @param PlatformInterface $platform
     */
    public function __construct(?PlatformInterface $platform = null)
    {
        if (null !== $platform) {
            $this->setPlatform($platform);
        }

        // init
        $this->databases = new ArrayList([], Database::class);
        $this->schemas = new Set([], Schema::class);
    }

    protected function getSuperordinate(): Schema
    {
        return $this->schema;
    }

    /**
     * Sets the filename when reading this schema
     *
     * @param string $filename
     */
    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    /**
     * Returns the filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    protected function registerSchema(Schema $schema): void
    {
        $schema->addExternalSchema($this);
    }

    /**
     * Adds an external schema
     *
     * @param Schema $schema
     */
    public function addExternalSchema(Schema $schema): void
    {
        if (!$this->schemas->contains($schema)) {
            $this->schemas->add($schema);
            $schema->setSchema($this);
        }
    }

    /**
     * Returns whether this is an external schema
     *
     * @return bool
     */
    public function isExternalSchema(): bool
    {
        return null !== $this->schema;
    }

    /**
     * Returns all external schema
     *
     * @return Schema[]
     */
    public function getExternalSchemas(): array
    {
        return $this->schemas->toArray();
    }

    /**
     * Returns the amount of external schemas
     *
     * @return int
     */
    public function getExternalSchemaSize(): int
    {
        return $this->schemas->size();
    }

    protected function unregisterSchema(Schema $schema): void
    {
        $this->removeExternalSchema($schema);
    }

    /**
     * Removes an external schema (only relevant if this is an external schema)
     *
     * @param Schema $schema
     */
    public function removeExternalSchema(Schema $schema): void
    {
        if ($this->schemas->contains($schema)) {
            $schema->setSchema(null);
            $this->schemas->remove($schema);
        }
    }

    /**
     * Retuns the root schema
     *
     * @return Schema
     */
    public function getRootSchema(): Schema
    {
        $parent = $this;
        while ($parent->getSchema()) {
            $parent = $parent->getSchema();
        }

        return $parent;
    }

    /**
     * Set whether this schema is only for reference (only relevant if this is an external schema)
     *
     * @param bool $referenceOnly
     */
    public function setReferenceOnly(bool $referenceOnly): void
    {
        $this->referenceOnly = $referenceOnly;
    }

    /**
     * Returns whether this schema is for reference only
     *
     * @return bool
     */
    public function getReferenceOnly(): bool
    {
        return $this->referenceOnly;
    }

    /**
     * Adds a database to the list and sets the Schema property to this
     * Schema.
     *
     * @param Database $database
     */
    public function addDatabase(Database $database): void
    {
        if (!$this->databases->contains($database)) {
            $this->databases->add($database);
            $database->setSchema($this);
        }
    }

    /**
     * Returns whether or not a database with the specified name exists in this
     * schema.
     *
     * @param  string  $name
     * @return boolean
     */
    public function hasDatabase($name): bool
    {
        return $this->databases->search($name, function (Database $db, $query) {
            return $db->getName() === $query;
        });
    }

    /**
     * Returns whether or not this schema has multiple databases.
     *
     * @return bool
     */
    public function hasMultipleDatabases(): bool
    {
        return $this->databases->size() > 1;
    }

    /**
     * Returns the database according to the specified name or the first one.
     *
     * @param string $name
     * @return Database
     */
    public function getDatabase(?string $name = null): ?Database
    {
        if ($this->databases->size() === 0) {
            return null;
        }

        if (null === $name) {
            return $this->databases->get(0);
        }

        return $this->databases->find($name, function (Database $db, $query) {
            return $db->getName() === $query;
        });
    }

    /**
     * Returns an array of all databases.
     *
     * The first boolean parameter tells whether or not to run the
     * final initialization process.
     *
     * @return ArrayList List of Database objects
     */
    public function getDatabases(): ArrayList
    {
        return $this->databases;
    }

    /**
     * Returns the amount of databases
     *
     * @return int
     */
    public function getDatabaseSize(): int
    {
        return $this->databases->size();
    }

    /**
     * Removes the database from this schema
     *
     * @param Database $database
     */
    public function removeDatabase(Database $database): void
    {
        if ($this->databases->contains($database)) {
            $database->setSchema(null);
            $this->databases->remove($database);
        }
    }

    /**
     * @TODO
     * Merge other Schema objects together into this Schema object.
     */
    public function joinSchemas(array $schemas)
    {
        foreach ($schemas as $schema) {
            /** @var Database $addDb */
            foreach ($schema->getDatabases() as $addDb) {
                $addDbName = $addDb->getName();
                if ($this->hasDatabase($addDbName)) {
                    $db = $this->getDatabase($addDbName->toString());
                    // temporarily reset database namespace to avoid double namespace decoration (see ticket #1355)
                    $namespace = $db->getNamespace();
                    $db->setNamespace(null);
                    // join tables
                    foreach ($addDb->getTables() as $addTable) {
                        if ($db->hasTableByFullName($addTable->getFullName())) {
                            throw new EngineException(sprintf('Duplicate table found: %s.', $addTable->getName()));
                        }
                        $db->addTable($addTable);
                    }
                    // join database behaviors
                    if ($addDb->getBehaviors()) {
                        foreach ($addDb->getBehaviors() as $addBehavior) {
                            if (!$db->hasBehavior($addBehavior->getId())) {
                                $db->addBehavior($addBehavior);
                            }
                        }
                    }
                    // restore the database namespace
                    $db->setNamespace($namespace);
                } else {
                    $this->addDatabase($addDb);
                }
            }
        }
    }

    public function joinExternalSchemas()
    {
        $this->joinSchemas($this->schemas->toArray());
        $this->schemas->clear();
    }

    /**
     * Returns the number of tables in all the databases of this Schema object.
     *
     * @return int
     */
    public function countTables(): int
    {
        $nb = 0;
        foreach ($this->getDatabases() as $database) {
            $nb += $database->countTables();
        }

        return $nb;
    }

    /**
     * Creates a string representation of this Schema.
     * The representation is given in xml format.
     *
     * @return string Representation in xml format
     */
    public function toString(): string
    {
        $dumper = new XmlDumper();

        return $dumper->dumpSchema($this);
    }

    /**
     * Magic string method
     *
     * @see toString()
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
