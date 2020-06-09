<?php declare(strict_types=1);
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace Propel\Generator\Model;

use phootwork\collection\Map;
use phootwork\collection\Set;
use phootwork\lang\Text;
use Propel\Generator\Model\Parts\CopyPart;
use Propel\Generator\Model\Parts\DatabasePart;
use Propel\Generator\Model\Parts\TablePart;
use Propel\Generator\Model\Parts\NamePart;

/**
 * Information about behaviors of a table.
 *
 * @author François Zaninotto
 * @author Hugo Hamon <webmaster@apprendre-php.com> (Propel)
 * @author Thomas Gossmann
 */
class Behavior
{
    use CopyPart, DatabasePart, NamePart, TablePart;

    /**
     * The behavior id.
     *
     * @var string
     */
    protected string $id;

    /**
     * A collection of parameters.
     *
     * @var Map
     */
    protected Map $parameters;

    /**
     * Array of default parameters.
     * Usually override by subclasses.
     *
     * @var array
     */
    protected array $defaultParameters = [];

    /**
     * Whether or not the table has been
     * modified by the behavior.
     *
     * @var bool
     */
    protected bool $isTableModified = false;

    /**
     * The absolute path to the directory
     * that contains the behavior's templates
     * files.
     *
     * @var string
     */
    protected string $dirname;

    /**
     * A collection of additional builders.
     *
     * @var array
     */
    protected array $additionalBuilders = [];

    /**
     * The order in which the behavior must
     * be applied.
     *
     * @var int
     */
    protected int $tableModificationOrder = 50;

    public function __construct()
    {
        //Add the subclasses default parameters
        $this->parameters = new Map($this->defaultParameters);
    }

    /**
     * Sets the name of the Behavior
     *
     * @param string $name the name of the behavior
     */
    public function setName(string $name): void
    {
        if (!isset($this->id)) {
            $this->setId($name);
        }

        $this->name = new Text($name);
    }

    /**
     * Sets the id of the Behavior
     *
     * @param string $id The id of the behavior
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns the id of the Behavior
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Indicates whether the behavior can be applied several times on the same
     * table or not.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return false;
    }

    /**
     * Sets a single parameter by its name.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter(string $name, $value): void
    {
        //Don't want override a default parameter with a null value
        if (null !== $value) {
            $this->parameters->set(strtolower($name), $value);
        }
    }

    /**
     * Adds a single parameter.
     *
     * Expects an associative array:
     * ['name' => 'foo', 'value' => 'bar']
     *
     * @param array $parameter
     */
    public function addParameter(array $parameter): void
    {
        $this->parameters->set(strtolower($parameter['name']), $parameter['value']);
    }

    /**
     * Overrides the behavior parameters.
     *
     * Expects an associative array looking like [ 'foo' => 'bar' ].
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters->clear();
        $this->parameters->setAll($parameters);
    }

    /**
     * Checks whether a parameter is set
     *
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return $this->parameters->has($name);
    }

    /**
     * Returns a single parameter by its name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters->get($name);
    }

    /**
     * Returns the associative array of parameters.
     *
     * @return Map
     */
    public function getParameters(): Map
    {
        return $this->parameters;
    }

    /**
     * Defines when this behavior must execute its modifyTable() method
     * relative to other behaviors. The bigger the value is, the later the
     * behavior is executed.
     *
     * Default is 50.
     *
     * @param integer $tableModificationOrder
     */
    public function setTableModificationOrder(int $tableModificationOrder): void
    {
        $this->tableModificationOrder = $tableModificationOrder;
    }

    /**
     * Returns when this behavior must execute its modifyTable() method relative
     * to other behaviors. The bigger the value is, the later the behavior is
     * executed.
     *
     * Default is 50.
     *
     * @return int
     */
    public function getTableModificationOrder(): int
    {
        return $this->tableModificationOrder;
    }

    /**
     * This method is automatically called on database behaviors when the
     * database model is finished.
     *
     * Propagates the behavior to the tables of the database and override this
     * method to have a database behavior do something special.
     */
    public function modifyDatabase(): void
    {
        foreach ($this->getTables() as $table) {
            if ($table->hasBehavior($this->getId())) {
                // don't add the same behavior twice
                continue;
            }

            $behavior = $this->copy();
            $table->addBehavior($behavior);
        }
    }

    /**
     * Returns the list of all tables in the same database.
     *
     * @return Set A collection of Table instance
     */
    protected function getTables(): Set
    {
        return $this->getDatabase()->getTables();
    }

    /**
     * This method is automatically called on table behaviors when the database
     * model is finished. It also override it to add columns to the current
     * table.
     */
    public function modifyTable()
    {
    }

    /**
     * Sets whether or not the table has been modified.
     *
     * @param bool $modified
     */
    public function setTableModified(bool $modified): void
    {
        $this->isTableModified = $modified;
    }

    /**
     * Returns whether or not the table has been modified.
     *
     * @return bool
     */
    public function isTableModified(): bool
    {
        return $this->isTableModified;
    }

    /**
     * Returns a column object using a name stored in the behavior parameters.
     * Useful for table behaviors.
     *
     * @param string $name
     * @return Column
     */
    public function getColumnForParameter(string $name): Column
    {
        return $this->getTable()->getColumn($this->getParameter($name));
    }

    //@todo useful?
    /**
     * Returns the table modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getTableModifier()
    {
        return $this;
    }

    /**
     * Returns the object builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getObjectBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the query builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getQueryBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns the table map builder modifier object.
     *
     * The current object is returned by default.
     *
     * @return $this|Behavior
     */
    public function getTableMapBuilderModifier()
    {
        return $this;
    }

    /**
     * Returns whether or not this behavior has additional builders.
     *
     * @return bool
     */
    public function hasAdditionalBuilders(): bool
    {
        return !empty($this->additionalBuilders);
    }

    /**
     * Returns the list of additional builder objects.
     *
     * @return array
     */
    public function getAdditionalBuilders(): array
    {
        return $this->additionalBuilders;
    }
}
