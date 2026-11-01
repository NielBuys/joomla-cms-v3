<?php
/**
 * @package    Joomla.Test
 *
 * @copyright  (C) 2026 Niel Buys. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * A small replacement for the parts of phpunit/dbunit the suite uses.
 *
 * phpunit/dbunit is abandoned and has no release for current PHPUnit versions. These classes keep its class names
 * and behaviour for what the tests need: CSV and XML data sets, the DELETE_ALL and INSERT setup operations, row
 * counts and data set comparison.
 */

/**
 * A table of rows loaded from a data set.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_DataSet_DefaultTable
{
	/**
	 * @var    string
	 * @since  3.12.0
	 */
	private $name;

	/**
	 * @var    string[]
	 * @since  3.12.0
	 */
	private $columns;

	/**
	 * @var    array[]  Rows keyed by column name. A null value is SQL NULL.
	 * @since  3.12.0
	 */
	private $rows;

	/**
	 * Constructor
	 *
	 * @param   string    $name     The table name.
	 * @param   string[]  $columns  The column names.
	 * @param   array[]   $rows     Rows keyed by column name.
	 *
	 * @since   3.12.0
	 */
	public function __construct($name, array $columns, array $rows)
	{
		$this->name    = $name;
		$this->columns = $columns;
		$this->rows    = $rows;
	}

	/**
	 * @return  string
	 *
	 * @since   3.12.0
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return  string[]
	 *
	 * @since   3.12.0
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * @return  array[]
	 *
	 * @since   3.12.0
	 */
	public function getRows()
	{
		return $this->rows;
	}

	/**
	 * @return  integer
	 *
	 * @since   3.12.0
	 */
	public function getRowCount()
	{
		return count($this->rows);
	}
}

/**
 * Base class for data sets: a list of tables.
 *
 * @since  3.12.0
 */
abstract class PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/**
	 * @var    PHPUnit_Extensions_Database_DataSet_DefaultTable[]  Tables keyed by name, in the order they were added.
	 * @since  3.12.0
	 */
	protected $tables = array();

	/**
	 * @return  string[]
	 *
	 * @since   3.12.0
	 */
	public function getTableNames()
	{
		return array_keys($this->getTables());
	}

	/**
	 * @param   string  $name  The table name.
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_DefaultTable
	 *
	 * @since   3.12.0
	 */
	public function getTable($name)
	{
		$tables = $this->getTables();

		if (!isset($tables[$name]))
		{
			throw new InvalidArgumentException(sprintf('Table "%s" is not in the data set.', $name));
		}

		return $tables[$name];
	}

	/**
	 * @return  PHPUnit_Extensions_Database_DataSet_DefaultTable[]
	 *
	 * @since   3.12.0
	 */
	public function getTables()
	{
		return $this->tables;
	}
}

/**
 * A data set read from CSV files, one file per table. The first line holds the column names.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_DataSet_CsvDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/**
	 * @var    string[]
	 * @since  3.12.0
	 */
	private $csvOptions;

	/**
	 * Constructor
	 *
	 * @param   string  $delimiter  The field delimiter.
	 * @param   string  $enclosure  The field enclosure.
	 * @param   string  $escape     The escape character.
	 *
	 * @since   3.12.0
	 */
	public function __construct($delimiter = ',', $enclosure = '"', $escape = '"')
	{
		$this->csvOptions = array($delimiter, $enclosure, $escape);
	}

	/**
	 * Adds a table from a CSV file.
	 *
	 * @param   string  $tableName  The table name.
	 * @param   string  $csvFile    The CSV file.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function addTable($tableName, $csvFile)
	{
		$handle = fopen($csvFile, 'r');

		if ($handle === false)
		{
			throw new InvalidArgumentException(sprintf('Could not read the CSV file "%s".', $csvFile));
		}

		list($delimiter, $enclosure, $escape) = $this->csvOptions;

		$columns = fgetcsv($handle, null, $delimiter, $enclosure, $escape);
		$rows    = array();

		while (($values = fgetcsv($handle, null, $delimiter, $enclosure, $escape)) !== false)
		{
			// Skip blank lines
			if ($values === array(null))
			{
				continue;
			}

			$rows[] = array_combine($columns, array_pad($values, count($columns), ''));
		}

		fclose($handle);

		$this->tables[$tableName] = new PHPUnit_Extensions_Database_DataSet_DefaultTable($tableName, $columns, $rows);
	}
}

/**
 * A data set read from an XML file in the dbunit "XmlDataSet" format.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_DataSet_XmlDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/**
	 * Constructor
	 *
	 * @param   string  $xmlFile  The XML file.
	 *
	 * @since   3.12.0
	 */
	public function __construct($xmlFile)
	{
		$xml = simplexml_load_file($xmlFile);

		if ($xml === false)
		{
			throw new InvalidArgumentException(sprintf('Could not read the XML file "%s".', $xmlFile));
		}

		foreach ($xml->table as $table)
		{
			$name    = (string) $table['name'];
			$columns = array();
			$rows    = array();

			foreach ($table->column as $column)
			{
				$columns[] = (string) $column;
			}

			foreach ($table->row as $row)
			{
				$values = array();

				foreach ($row->children() as $value)
				{
					$values[] = $value->getName() === 'null' ? null : (string) $value;
				}

				$rows[] = array_combine($columns, $values);
			}

			$this->tables[$name] = new PHPUnit_Extensions_Database_DataSet_DefaultTable($name, $columns, $rows);
		}
	}
}

/**
 * A data set that reads its tables from the database when they are requested.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_DataSet_QueryDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
	/**
	 * @var    PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 * @since  3.12.0
	 */
	private $connection;

	/**
	 * @var    string[]  Queries keyed by table name.
	 * @since  3.12.0
	 */
	private $queries = array();

	/**
	 * Constructor
	 *
	 * @param   PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection  $connection  The database connection.
	 *
	 * @since   3.12.0
	 */
	public function __construct($connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Adds a table, read with the given query or with all of its columns.
	 *
	 * @param   string  $tableName  The table name.
	 * @param   string  $query      The query; defaults to selecting every row.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function addTable($tableName, $query = null)
	{
		$this->queries[$tableName] = $query !== null ? $query : 'SELECT * FROM ' . $this->connection->quoteSchemaObject($tableName);
	}

	/**
	 * @return  PHPUnit_Extensions_Database_DataSet_DefaultTable[]
	 *
	 * @since   3.12.0
	 */
	public function getTables()
	{
		$tables = array();

		foreach ($this->queries as $name => $query)
		{
			$statement = $this->connection->getConnection()->query($query);
			$rows      = $statement->fetchAll(PDO::FETCH_ASSOC);
			$columns   = array();

			for ($i = 0; $i < $statement->columnCount(); $i++)
			{
				$meta      = $statement->getColumnMeta($i);
				$columns[] = $meta['name'];
			}

			$tables[$name] = new PHPUnit_Extensions_Database_DataSet_DefaultTable($name, $columns, $rows);
		}

		return $tables;
	}
}

/**
 * A database connection used by the setup operations.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
{
	/**
	 * @var    PDO
	 * @since  3.12.0
	 */
	private $pdo;

	/**
	 * @var    string
	 * @since  3.12.0
	 */
	private $schema;

	/**
	 * Constructor
	 *
	 * @param   PDO     $pdo     The PDO connection.
	 * @param   string  $schema  The schema (database) name.
	 *
	 * @since   3.12.0
	 */
	public function __construct(PDO $pdo, $schema = '')
	{
		$this->pdo    = $pdo;
		$this->schema = $schema;
	}

	/**
	 * @return  PDO
	 *
	 * @since   3.12.0
	 */
	public function getConnection()
	{
		return $this->pdo;
	}

	/**
	 * @return  string
	 *
	 * @since   3.12.0
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * Quotes a table or column name for the connection's database.
	 *
	 * @param   string  $name  The name.
	 *
	 * @return  string
	 *
	 * @since   3.12.0
	 */
	public function quoteSchemaObject($name)
	{
		$quote = in_array($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME), array('mysql'), true) ? '`' : '"';

		return $quote . str_replace($quote, $quote . $quote, $name) . $quote;
	}

	/**
	 * Returns the number of rows in a table.
	 *
	 * @param   string  $tableName    The table name.
	 * @param   string  $whereClause  An optional condition, without the WHERE keyword.
	 *
	 * @return  integer
	 *
	 * @since   3.12.0
	 */
	public function getRowCount($tableName, $whereClause = null)
	{
		$query = 'SELECT COUNT(*) FROM ' . $this->quoteSchemaObject($tableName);

		if ($whereClause !== null)
		{
			$query .= ' WHERE ' . $whereClause;
		}

		return (int) $this->pdo->query($query)->fetchColumn();
	}
}

/**
 * A database operation run before or after a test.
 *
 * @since  3.12.0
 */
interface PHPUnit_Extensions_Database_Operation_DatabaseOperation
{
	/**
	 * Runs the operation for the tables in a data set.
	 *
	 * @param   PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection  $connection  The connection.
	 * @param   PHPUnit_Extensions_Database_DataSet_AbstractDataSet       $dataSet     The data set.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function execute($connection, $dataSet);
}

/**
 * Does nothing.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_Operation_Null implements PHPUnit_Extensions_Database_Operation_DatabaseOperation
{
	/**
	 * @inheritdoc
	 */
	public function execute($connection, $dataSet)
	{
	}
}

/**
 * Deletes every row from the data set's tables.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_Operation_DeleteAll implements PHPUnit_Extensions_Database_Operation_DatabaseOperation
{
	/**
	 * @inheritdoc
	 */
	public function execute($connection, $dataSet)
	{
		foreach (array_reverse($dataSet->getTableNames()) as $name)
		{
			$connection->getConnection()->exec('DELETE FROM ' . $connection->quoteSchemaObject($name));
		}
	}
}

/**
 * Inserts the data set's rows.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_Operation_Insert implements PHPUnit_Extensions_Database_Operation_DatabaseOperation
{
	/**
	 * @inheritdoc
	 */
	public function execute($connection, $dataSet)
	{
		foreach ($dataSet->getTables() as $table)
		{
			$columns = $table->getColumns();

			if (!$table->getRowCount())
			{
				continue;
			}

			$query = sprintf(
				'INSERT INTO %s (%s) VALUES (%s)',
				$connection->quoteSchemaObject($table->getName()),
				implode(', ', array_map(array($connection, 'quoteSchemaObject'), $columns)),
				implode(', ', array_fill(0, count($columns), '?'))
			);

			$statement = $connection->getConnection()->prepare($query);

			foreach ($table->getRows() as $row)
			{
				$values = array();

				foreach ($columns as $column)
				{
					$values[] = $row[$column];
				}

				$statement->execute($values);
			}
		}
	}
}

/**
 * Runs several operations in order.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_Operation_Composite implements PHPUnit_Extensions_Database_Operation_DatabaseOperation
{
	/**
	 * @var    PHPUnit_Extensions_Database_Operation_DatabaseOperation[]
	 * @since  3.12.0
	 */
	private $operations;

	/**
	 * Constructor
	 *
	 * @param   PHPUnit_Extensions_Database_Operation_DatabaseOperation[]  $operations  The operations.
	 *
	 * @since   3.12.0
	 */
	public function __construct(array $operations)
	{
		$this->operations = $operations;
	}

	/**
	 * @inheritdoc
	 */
	public function execute($connection, $dataSet)
	{
		foreach ($this->operations as $operation)
		{
			$operation->execute($connection, $dataSet);
		}
	}
}

/**
 * Creates the standard operations.
 *
 * @since  3.12.0
 */
class PHPUnit_Extensions_Database_Operation_Factory
{
	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	public static function NONE()
	{
		return new PHPUnit_Extensions_Database_Operation_Null;
	}

	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	public static function DELETE_ALL()
	{
		return new PHPUnit_Extensions_Database_Operation_DeleteAll;
	}

	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	public static function INSERT()
	{
		return new PHPUnit_Extensions_Database_Operation_Insert;
	}

	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	public static function CLEAN_INSERT()
	{
		return new PHPUnit_Extensions_Database_Operation_Composite(array(static::DELETE_ALL(), static::INSERT()));
	}
}

/**
 * A test case that loads a data set into the database before each test.
 *
 * @since  3.12.0
 */
abstract class PHPUnit_Extensions_Database_TestCase extends TestCasePhpunit
{
	/**
	 * Returns the connection the data set is loaded into, or null for none.
	 *
	 * @return  PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection|null
	 *
	 * @since   3.12.0
	 */
	abstract protected function getConnection();

	/**
	 * Returns the data set loaded before each test.
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_AbstractDataSet
	 *
	 * @since   3.12.0
	 */
	abstract protected function getDataSet();

	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	protected function getSetUpOperation()
	{
		return PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
	}

	/**
	 * @return  PHPUnit_Extensions_Database_Operation_DatabaseOperation
	 *
	 * @since   3.12.0
	 */
	protected function getTearDownOperation()
	{
		return PHPUnit_Extensions_Database_Operation_Factory::NONE();
	}

	/**
	 * Loads the data set.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$connection = $this->getConnection();

		if ($connection !== null)
		{
			$this->getSetUpOperation()->execute($connection, $this->getDataSet());
		}
	}

	/**
	 * Runs the tear down operation.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	protected function tearDown(): void
	{
		$connection = $this->getConnection();

		if ($connection !== null)
		{
			$this->getTearDownOperation()->execute($connection, $this->getDataSet());
		}

		parent::tearDown();
	}

	/**
	 * Creates a connection for a PDO object.
	 *
	 * @param   PDO     $connection  The PDO connection.
	 * @param   string  $schema      The schema (database) name.
	 *
	 * @return  PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 *
	 * @since   3.12.0
	 */
	protected function createDefaultDBConnection(PDO $connection, $schema = '')
	{
		return new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($connection, $schema);
	}

	/**
	 * Creates a data set from an XML file.
	 *
	 * @param   string  $xmlFile  The XML file.
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 *
	 * @since   3.12.0
	 */
	protected function createXMLDataSet($xmlFile)
	{
		return new PHPUnit_Extensions_Database_DataSet_XmlDataSet($xmlFile);
	}

	/**
	 * Asserts that two data sets hold the same tables, columns and rows.
	 *
	 * @param   PHPUnit_Extensions_Database_DataSet_AbstractDataSet  $expected  The expected data set.
	 * @param   PHPUnit_Extensions_Database_DataSet_AbstractDataSet  $actual    The actual data set.
	 * @param   string                                               $message   The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertDataSetsEqual($expected, $actual, $message = '')
	{
		$normalise = function ($dataSet)
		{
			$tables = array();

			foreach ($dataSet->getTables() as $name => $table)
			{
				$rows = array();

				// Compare values as strings, as dbunit did; null stays null
				foreach ($table->getRows() as $row)
				{
					$rows[] = array_map(function ($value)
					{
						return $value === null ? null : (string) $value;
					}, $row);
				}

				$tables[$name] = array('columns' => $table->getColumns(), 'rows' => $rows);
			}

			return $tables;
		};

		static::assertSame($normalise($expected), $normalise($actual), $message);
	}
}
