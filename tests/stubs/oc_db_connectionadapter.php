<?php

namespace OC\DB;

/**
 * Adapts the public API to our internal DBAL connection wrapper
 */
class ConnectionAdapter implements \OCP\IDBConnection
{
    #[\Override]
    public function getQueryBuilder(): \OCP\DB\QueryBuilder\IQueryBuilder
    {
    }
    #[\Override]
    public function getTypedQueryBuilder(): \OCP\DB\QueryBuilder\ITypedQueryBuilder
    {
    }
    #[\Override]
    public function prepare($sql, $limit = null, $offset = null): \OCP\DB\IPreparedStatement
    {
    }
    #[\Override]
    public function executeQuery(string $sql, array $params = [], $types = []): \OCP\DB\IResult
    {
    }
    #[\Override]
    public function executeUpdate(string $sql, array $params = [], array $types = []): int
    {
    }
    #[\Override]
    public function executeStatement($sql, array $params = [], array $types = []): int
    {
    }
    #[\Override]
    public function lastInsertId(string $table): int
    {
    }
    #[\Override]
    public function insertIfNotExist(string $table, array $input, ?array $compare = null)
    {
    }
    #[\Override]
    public function insertIgnoreConflict(string $table, array $values): int
    {
    }
    #[\Override]
    public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int
    {
    }
    #[\Override]
    public function lockTable($tableName): void
    {
    }
    #[\Override]
    public function unlockTable(): void
    {
    }
    #[\Override]
    public function beginTransaction(): void
    {
    }
    #[\Override]
    public function inTransaction(): bool
    {
    }
    #[\Override]
    public function commit(): void
    {
    }
    #[\Override]
    public function rollBack(): void
    {
    }
    #[\Override]
    public function getError(): string
    {
    }
    #[\Override]
    public function errorCode()
    {
    }
    #[\Override]
    public function errorInfo()
    {
    }
    #[\Override]
    public function connect(): bool
    {
    }
    #[\Override]
    public function close(): void
    {
    }
    #[\Override]
    public function quote($input, $type = \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR)
    {
    }
    /**
     * @todo we are leaking a 3rdparty type here
     */
    #[\Override]
    public function getDatabasePlatform(): \Doctrine\DBAL\Platforms\AbstractPlatform
    {
    }
    #[\Override]
    public function dropTable(string $table): void
    {
    }
    #[\Override]
    public function truncateTable(string $table, bool $cascade): void
    {
    }
    #[\Override]
    public function tableExists(string $table): bool
    {
    }
    #[\Override]
    public function escapeLikeParameter(string $param): string
    {
    }
    #[\Override]
    public function supports4ByteText(): bool
    {
    }
    /**
     * @todo leaks a 3rdparty type
     */
    #[\Override]
    public function createSchema(): \Doctrine\DBAL\Schema\Schema
    {
    }
    #[\Override]
    public function migrateToSchema(\Doctrine\DBAL\Schema\Schema $toSchema): void
    {
    }
    public function getInner(): \Doctrine\DBAL\Connection
    {
    }
    /**
     * @return self::PLATFORM_MYSQL|self::PLATFORM_ORACLE|self::PLATFORM_POSTGRES|self::PLATFORM_SQLITE|self::PLATFORM_MARIADB
     */
    #[\Override]
    public function getDatabaseProvider(bool $strict = false): string
    {
    }
    /**
     * @internal Should only be used inside the QueryBuilder, ExpressionBuilder and FunctionBuilder
     * All apps and API code should not need this and instead use provided functionality from the above.
     */
    public function getServerVersion(): string
    {
    }
    public function logDatabaseException(\Exception $exception)
    {
    }
    #[\Override]
    public function getShardDefinition(string $name): ?\OC\DB\QueryBuilder\Sharded\ShardDefinition
    {
    }
    #[\Override]
    public function getCrossShardMoveHelper(): \OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper
    {
    }
}
