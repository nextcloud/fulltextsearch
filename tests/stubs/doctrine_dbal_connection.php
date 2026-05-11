<?php

namespace Doctrine\DBAL;

/**
 * A database abstraction-level connection that implements features like events, transaction isolation levels,
 * configuration, emulated transaction nesting, lazy connecting and more.
 *
 * @phpstan-import-type Params from DriverManager
 * @phpstan-consistent-constructor
 */
class Connection
{
    /**
     * Represents an array of ints to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::INTEGER} instead.
     */
    public const PARAM_INT_ARRAY = \Doctrine\DBAL\ArrayParameterType::INTEGER;
    /**
     * Represents an array of strings to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::STRING} instead.
     */
    public const PARAM_STR_ARRAY = \Doctrine\DBAL\ArrayParameterType::STRING;
    /**
     * Represents an array of ascii strings to be expanded by Doctrine SQL parsing.
     *
     * @deprecated Use {@see ArrayParameterType::ASCII} instead.
     */
    public const PARAM_ASCII_STR_ARRAY = \Doctrine\DBAL\ArrayParameterType::ASCII;
    /**
     * Offset by which PARAM_* constants are detected as arrays of the param type.
     *
     * @internal Should be used only within the wrapper layer.
     */
    public const ARRAY_PARAM_OFFSET = 100;
    /**
     * The wrapped driver connection.
     *
     * @var DriverConnection|null
     */
    protected $_conn;
    /** @var Configuration */
    protected $_config;
    /**
     * @deprecated
     *
     * @var EventManager
     */
    protected $_eventManager;
    /**
     * @deprecated Use {@see createExpressionBuilder()} instead.
     *
     * @var ExpressionBuilder
     */
    protected $_expr;
    /**
     * The schema manager.
     *
     * @deprecated Use {@see createSchemaManager()} instead.
     *
     * @var AbstractSchemaManager|null
     */
    protected $_schemaManager;
    /**
     * The used DBAL driver.
     *
     * @var Driver
     */
    protected $_driver;
    /**
     * Initializes a new instance of the Connection class.
     *
     * @internal The connection can be only instantiated by the driver manager.
     *
     * @param array<string,mixed> $params       The connection parameters.
     * @param Driver              $driver       The driver to use.
     * @param Configuration|null  $config       The configuration, optional.
     * @param EventManager|null   $eventManager The event manager, optional.
     * @phpstan-param Params $params
     *
     * @throws Exception
     */
    public function __construct(
        #[\SensitiveParameter]
        array $params,
        \Doctrine\DBAL\Driver $driver,
        ?\Doctrine\DBAL\Configuration $config = null,
        ?\Doctrine\Common\EventManager $eventManager = null
    )
    {
    }
    /**
     * Gets the parameters used during instantiation.
     *
     * @internal
     *
     * @return array<string,mixed>
     * @phpstan-return Params
     */
    public function getParams()
    {
    }
    /**
     * Gets the name of the currently selected database.
     *
     * @return string|null The name of the database or NULL if a database is not selected.
     *                     The platforms which don't support the concept of a database (e.g. embedded databases)
     *                     must always return a string as an indicator of an implicitly selected database.
     *
     * @throws Exception
     */
    public function getDatabase()
    {
    }
    /**
     * Gets the DBAL driver instance.
     *
     * @return Driver
     */
    public function getDriver()
    {
    }
    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
    }
    /**
     * Gets the EventManager used by the Connection.
     *
     * @deprecated
     *
     * @return EventManager
     */
    public function getEventManager()
    {
    }
    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return AbstractPlatform
     *
     * @throws Exception
     */
    public function getDatabasePlatform()
    {
    }
    /**
     * Creates an expression builder for the connection.
     */
    public function createExpressionBuilder(): \Doctrine\DBAL\Query\Expression\ExpressionBuilder
    {
    }
    /**
     * Gets the ExpressionBuilder for the connection.
     *
     * @deprecated Use {@see createExpressionBuilder()} instead.
     *
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
    }
    /**
     * Establishes the connection with the database.
     *
     * @internal This method will be made protected in DBAL 4.0.
     *
     * @return bool TRUE if the connection was successfully established, FALSE if
     *              the connection is already open.
     *
     * @throws Exception
     *
     * @phpstan-assert !null $this->_conn
     */
    public function connect()
    {
    }
    /**
     * Returns the current auto-commit mode for this connection.
     *
     * @see    setAutoCommit
     *
     * @return bool True if auto-commit mode is currently enabled for this connection, false otherwise.
     */
    public function isAutoCommit()
    {
    }
    /**
     * Sets auto-commit mode for this connection.
     *
     * If a connection is in auto-commit mode, then all its SQL statements will be executed and committed as individual
     * transactions. Otherwise, its SQL statements are grouped into transactions that are terminated by a call to either
     * the method commit or the method rollback. By default, new connections are in auto-commit mode.
     *
     * NOTE: If this method is called during a transaction and the auto-commit mode is changed, the transaction is
     * committed. If this method is called and the auto-commit mode is not changed, the call is a no-op.
     *
     * @see   isAutoCommit
     *
     * @param bool $autoCommit True to enable auto-commit mode; false to disable it.
     *
     * @return void
     */
    public function setAutoCommit($autoCommit)
    {
    }
    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchAssociative(string $query, array $params = [], array $types = [])
    {
    }
    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<mixed>|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchNumeric(string $query, array $params = [], array $types = [])
    {
    }
    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return mixed|false False is returned if no rows are found.
     *
     * @throws Exception
     */
    public function fetchOne(string $query, array $params = [], array $types = [])
    {
    }
    /**
     * Whether an actual connection to the database is established.
     *
     * @return bool
     */
    public function isConnected()
    {
    }
    /**
     * Checks whether a transaction is currently active.
     *
     * @return bool TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function isTransactionActive()
    {
    }
    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table    Table name
     * @param array<string, mixed>                                                 $criteria Deletion criteria
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types    Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function delete($table, array $criteria, array $types = [])
    {
    }
    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
    }
    /**
     * Sets the transaction isolation level.
     *
     * @param TransactionIsolationLevel::* $level The level to set.
     *
     * @return int|string
     *
     * @throws Exception
     */
    public function setTransactionIsolation($level)
    {
    }
    /**
     * Gets the currently active transaction isolation level.
     *
     * @return TransactionIsolationLevel::* The current transaction isolation level.
     *
     * @throws Exception
     */
    public function getTransactionIsolation()
    {
    }
    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table    Table name
     * @param array<string, mixed>                                                 $data     Column-value pairs
     * @param array<string, mixed>                                                 $criteria Update criteria
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types    Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function update($table, array $data, array $criteria, array $types = [])
    {
    }
    /**
     * Inserts a table row with specified data.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string                                                               $table Table name
     * @param array<string, mixed>                                                 $data  Column-value pairs
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function insert($table, array $data, array $types = [])
    {
    }
    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use quoted identifiers does not mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function quoteIdentifier($str)
    {
    }
    /**
     * The usage of this method is discouraged. Use prepared statements
     * or {@see AbstractPlatform::quoteStringLiteral()} instead.
     *
     * @param mixed                $value
     * @param int|string|Type|null $type
     *
     * @return mixed
     */
    public function quote($value, $type = \Doctrine\DBAL\ParameterType::STRING)
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an array of numeric arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<list<mixed>>
     *
     * @throws Exception
     */
    public function fetchAllNumeric(string $query, array $params = [], array $types = []): array
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociative(string $query, array $params = [], array $types = []): array
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,mixed>
     *
     * @throws Exception
     */
    public function fetchAllKeyValue(string $query, array $params = [], array $types = []): array
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function fetchAllAssociativeIndexed(string $query, array $params = [], array $types = []): array
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an array of the first column values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return list<mixed>
     *
     * @throws Exception
     */
    public function fetchFirstColumn(string $query, array $params = [], array $types = []): array
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented as numeric arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,list<mixed>>
     *
     * @throws Exception
     */
    public function iterateNumeric(string $query, array $params = [], array $types = []): \Traversable
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociative(string $query, array $params = [], array $types = []): \Traversable
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<mixed,mixed>
     *
     * @throws Exception
     */
    public function iterateKeyValue(string $query, array $params = [], array $types = []): \Traversable
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * @param string                                           $query  SQL query
     * @param list<mixed>|array<string, mixed>                 $params Query parameters
     * @param array<int, int|string>|array<string, int|string> $types  Parameter types
     *
     * @return Traversable<mixed,array<string,mixed>>
     *
     * @throws Exception
     */
    public function iterateAssociativeIndexed(string $query, array $params = [], array $types = []): \Traversable
    {
    }
    /**
     * Prepares and executes an SQL query and returns the result as an iterator over the first column values.
     *
     * @param string                                                               $query  SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return Traversable<int,mixed>
     *
     * @throws Exception
     */
    public function iterateColumn(string $query, array $params = [], array $types = []): \Traversable
    {
    }
    /**
     * Prepares an SQL statement.
     *
     * @param string $sql The SQL statement to prepare.
     *
     * @throws Exception
     */
    public function prepare(string $sql): \Doctrine\DBAL\Statement
    {
    }
    /**
     * Executes an, optionally parameterized, SQL query.
     *
     * If the query is parametrized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws Exception
     */
    public function executeQuery(string $sql, array $params = [], $types = [], ?\Doctrine\DBAL\Cache\QueryCacheProfile $qcp = null): \Doctrine\DBAL\Result
    {
    }
    /**
     * Executes a caching query.
     *
     * @param string                                                               $sql    SQL query
     * @param list<mixed>|array<string, mixed>                                     $params Query parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @throws CacheException
     * @throws Exception
     */
    public function executeCacheQuery($sql, $params, $types, \Doctrine\DBAL\Cache\QueryCacheProfile $qcp): \Doctrine\DBAL\Result
    {
    }
    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be used for:
     *  - DML statements: INSERT, UPDATE, DELETE, etc.
     *  - DDL statements: CREATE, DROP, ALTER, etc.
     *  - DCL statements: GRANT, REVOKE, etc.
     *  - Session control statements: ALTER SESSION, SET, DECLARE, etc.
     *  - Other statements that don't yield a row set.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string                                                               $sql    SQL statement
     * @param list<mixed>|array<string, mixed>                                     $params Statement parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types  Parameter types
     *
     * @return int|string The number of affected rows.
     *
     * @throws Exception
     */
    public function executeStatement($sql, array $params = [], array $types = [])
    {
    }
    /**
     * Returns the current transaction nesting level.
     *
     * @return int The nesting level. A value of 0 means there's no active transaction.
     */
    public function getTransactionNestingLevel()
    {
    }
    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @param string|null $name Name of the sequence object from which the ID should be returned.
     *
     * @return string|int|false A string representation of the last inserted ID.
     *
     * @throws Exception
     */
    public function lastInsertId($name = null)
    {
    }
    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param Closure(self):T $func The function to execute transactionally.
     *
     * @return T The value returned by $func
     *
     * @throws Throwable
     *
     * @template T
     */
    public function transactional(\Closure $func)
    {
    }
    /**
     * Sets if nested transactions should use savepoints.
     *
     * @param bool $nestTransactionsWithSavepoints
     *
     * @return void
     *
     * @throws Exception
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
    }
    /**
     * Gets if nested transactions should use savepoints.
     *
     * @return bool
     */
    public function getNestTransactionsWithSavepoints()
    {
    }
    /**
     * Returns the savepoint name to use for nested transactions.
     *
     * @return string
     */
    protected function _getNestedTransactionSavePointName()
    {
    }
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function beginTransaction()
    {
    }
    /**
     * @return bool
     *
     * @throws Exception
     */
    public function commit()
    {
    }
    /**
     * Cancels any database changes done during the current transaction.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function rollBack()
    {
    }
    /**
     * Creates a new savepoint.
     *
     * @param string $savepoint The name of the savepoint to create.
     *
     * @return void
     *
     * @throws Exception
     */
    public function createSavepoint($savepoint)
    {
    }
    /**
     * Releases the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to release.
     *
     * @return void
     *
     * @throws Exception
     */
    public function releaseSavepoint($savepoint)
    {
    }
    /**
     * Rolls back to the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to rollback to.
     *
     * @return void
     *
     * @throws Exception
     */
    public function rollbackSavepoint($savepoint)
    {
    }
    /**
     * Gets the wrapped driver connection.
     *
     * @deprecated Use {@link getNativeConnection()} to access the native connection.
     *
     * @return DriverConnection
     *
     * @throws Exception
     */
    public function getWrappedConnection()
    {
    }
    /** @return resource|object */
    public function getNativeConnection()
    {
    }
    /**
     * Creates a SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @throws Exception
     */
    public function createSchemaManager(): \Doctrine\DBAL\Schema\AbstractSchemaManager
    {
    }
    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @deprecated Use {@see createSchemaManager()} instead.
     *
     * @return AbstractSchemaManager
     *
     * @throws Exception
     */
    public function getSchemaManager()
    {
    }
    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     *
     * @return void
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function setRollbackOnly()
    {
    }
    /**
     * Checks whether the current transaction is marked for rollback only.
     *
     * @return bool
     *
     * @throws ConnectionException If no transaction is active.
     */
    public function isRollbackOnly()
    {
    }
    /**
     * Converts a given value to its database representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted value.
     *
     * @throws Exception
     */
    public function convertToDatabaseValue($value, $type)
    {
    }
    /**
     * Converts a given value to its PHP representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted type.
     *
     * @throws Exception
     */
    public function convertToPHPValue($value, $type)
    {
    }
    /**
     * Creates a new instance of a SQL query builder.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
    }
    /**
     * @internal
     *
     * @param list<mixed>|array<string, mixed>                                     $params
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $types
     */
    final public function convertExceptionDuringQuery(\Doctrine\DBAL\Driver\Exception $e, string $sql, array $params = [], array $types = []): \Doctrine\DBAL\Exception\DriverException
    {
    }
    /** @internal */
    final public function convertException(\Doctrine\DBAL\Driver\Exception $e): \Doctrine\DBAL\Exception\DriverException
    {
    }
    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated Use {@see executeStatement()} instead
     *
     * @param array<mixed>           $params The query parameters
     * @param array<int|string|null> $types  The parameter types
     */
    public function executeUpdate(string $sql, array $params = [], array $types = []): int
    {
    }
    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated Use {@see executeQuery()} instead
     */
    public function query(string $sql): \Doctrine\DBAL\Result
    {
    }
    /**
     * BC layer for a wide-spread use-case of old DBAL APIs
     *
     * @deprecated please use {@see executeStatement()} instead
     */
    public function exec(string $sql): int
    {
    }
}