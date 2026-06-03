<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Platform;

use SybaseConnector\DBAL\SchemaManager\SQLAnywhereSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DateIntervalUnit;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Platforms\TrimMode;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\TransactionIsolationLevel;

/**
 * Platform implementation for SAP SQL Anywhere.
 *
 * SQL Anywhere uses a T-SQL variant with specific syntax:
 * - Pagination: SELECT TOP N START AT M (1-based offset)
 * - Current date: CURRENT DATE / CURRENT TIMESTAMP
 * - Null coalesce: ISNULL(expr, default)
 * - Boolean: BIT type
 * - Identity: DEFAULT AUTOINCREMENT
 */
class SQLAnywherePlatform extends AbstractPlatform
{
    // -----------------------------------------------------------------------
    // Type declarations
    // -----------------------------------------------------------------------

    public function getBooleanTypeDeclarationSQL(array $column): string
    {
        return 'BIT';
    }

    public function getIntegerTypeDeclarationSQL(array $column): string
    {
        return empty($column['autoincrement']) ? 'INTEGER' : 'INTEGER DEFAULT AUTOINCREMENT';
    }

    public function getBigIntTypeDeclarationSQL(array $column): string
    {
        return empty($column['autoincrement']) ? 'BIGINT' : 'BIGINT DEFAULT AUTOINCREMENT';
    }

    public function getSmallIntTypeDeclarationSQL(array $column): string
    {
        return empty($column['autoincrement']) ? 'SMALLINT' : 'SMALLINT DEFAULT AUTOINCREMENT';
    }

    public function getClobTypeDeclarationSQL(array $column): string
    {
        return 'LONG VARCHAR';
    }

    public function getBlobTypeDeclarationSQL(array $column): string
    {
        return 'LONG BINARY';
    }

    public function getDateTimeTypeDeclarationSQL(array $column): string
    {
        return 'DATETIME';
    }

    public function getDateTypeDeclarationSQL(array $column): string
    {
        return 'DATE';
    }

    public function getTimeTypeDeclarationSQL(array $column): string
    {
        return 'TIME';
    }

    protected function getVarcharTypeDeclarationSQLSnippet(?int $length): string
    {
        if ($length === null) {
            $length = 255;
        }

        return 'VARCHAR(' . $length . ')';
    }

    public function getAsciiStringTypeDeclarationSQL(array $column): string
    {
        $length = $column['length'] ?? 255;

        return 'VARCHAR(' . $length . ')';
    }

    protected function getBinaryTypeDeclarationSQLSnippet(?int $length): string
    {
        if ($length === null) {
            $length = 255;
        }

        return 'BINARY(' . $length . ')';
    }

    protected function getVarbinaryTypeDeclarationSQLSnippet(?int $length): string
    {
        if ($length === null) {
            $length = 255;
        }

        return 'VARBINARY(' . $length . ')';
    }

    public function getJsonTypeDeclarationSQL(array $column): string
    {
        return 'LONG VARCHAR';
    }

    // -----------------------------------------------------------------------
    // SQL fragments
    // -----------------------------------------------------------------------

    public function getCurrentDateSQL(): string
    {
        return 'CURRENT DATE';
    }

    public function getCurrentTimestampSQL(): string
    {
        return 'CURRENT TIMESTAMP';
    }

    public function getLocateExpression(string $string, string $substring, ?string $start = null): string
    {
        if ($start !== null) {
            return sprintf('LOCATE(%s, %s, %s)', $string, $substring, $start);
        }

        return sprintf('LOCATE(%s, %s)', $string, $substring);
    }

    public function getSubstringExpression(string $string, string $start, ?string $length = null): string
    {
        if ($length !== null) {
            return sprintf('SUBSTRING(%s, %s, %s)', $string, $start, $length);
        }

        return sprintf('SUBSTRING(%s, %s)', $string, $start);
    }

    public function getLengthExpression(string $string): string
    {
        return 'LENGTH(' . $string . ')';
    }

    public function getConcatExpression(string ...$string): string
    {
        return implode(' || ', $string);
    }

    public function getTrimExpression(string $str, TrimMode $mode = TrimMode::UNSPECIFIED, ?string $char = null): string
    {
        $trimFn = match ($mode) {
            TrimMode::LEADING => 'LTRIM',
            TrimMode::TRAILING => 'RTRIM',
            default => 'TRIM',
        };

        if ($mode === TrimMode::UNSPECIFIED || $char === null) {
            return $trimFn . '(' . $str . ')';
        }

        return sprintf('TRIM(%s, %s)', $str, $char);
    }

    public function getSetTransactionIsolationSQL(TransactionIsolationLevel $level): string
    {
        return 'SET TEMPORARY OPTION isolation_level = ' . $this->getTransactionIsolationLevelSQL($level);
    }

    private function getTransactionIsolationLevelSQL(TransactionIsolationLevel $level): string
    {
        return match ($level) {
            TransactionIsolationLevel::READ_UNCOMMITTED => '0',
            TransactionIsolationLevel::READ_COMMITTED => '1',
            TransactionIsolationLevel::REPEATABLE_READ => '2',
            TransactionIsolationLevel::SERIALIZABLE => '3',
        };
    }

    // -----------------------------------------------------------------------
    // Pagination (SELECT TOP N START AT M)
    // -----------------------------------------------------------------------

    protected function doModifyLimitQuery(string $query, ?int $limit, int $offset): string
    {
        if ($limit === null && $offset === 0) {
            return $query;
        }

        // SQL Anywhere: SELECT TOP <limit> START AT <offset+1> ...
        // START AT is 1-based
        $top = $limit !== null ? (string) $limit : '2147483647';
        $startAt = $offset + 1;

        // Insert TOP clause after SELECT (handle SELECT DISTINCT)
        $selectPos = stripos($query, 'SELECT');
        if ($selectPos === false) {
            return $query;
        }

        $afterSelect = $selectPos + 6;
        $rest = ltrim(substr($query, $afterSelect));

        $distinct = '';
        if (stripos($rest, 'DISTINCT') === 0) {
            $distinct = 'DISTINCT ';
            $rest = ltrim(substr($rest, 8));
        }

        return substr($query, 0, $selectPos)
            . 'SELECT ' . $distinct
            . 'TOP ' . $top . ' START AT ' . $startAt . ' '
            . $rest;
    }

    // -----------------------------------------------------------------------
    // Platform capabilities
    // -----------------------------------------------------------------------

    public function supportsIdentityColumns(): bool
    {
        return true;
    }

    public function supportsCommentOnStatement(): bool
    {
        return true;
    }

    // -----------------------------------------------------------------------
    // Required abstract method implementations
    // -----------------------------------------------------------------------

    protected function _getCommonIntegerTypeDeclarationSQL(array $column): string
    {
        $sql = '';

        if (!empty($column['autoincrement'])) {
            $sql .= ' DEFAULT AUTOINCREMENT';
        }

        return $sql;
    }

    public function getDateDiffExpression(string $date1, string $date2): string
    {
        return 'DATEDIFF(day, ' . $date2 . ', ' . $date1 . ')';
    }

    protected function getDateArithmeticIntervalExpression(
        string $date,
        string $operator,
        string $interval,
        DateIntervalUnit $unit,
    ): string {
        $factorClause = match ($unit) {
            DateIntervalUnit::SECOND => 'second',
            DateIntervalUnit::MINUTE => 'minute',
            DateIntervalUnit::HOUR => 'hour',
            DateIntervalUnit::DAY => 'day',
            DateIntervalUnit::WEEK => 'week',
            DateIntervalUnit::MONTH => 'month',
            DateIntervalUnit::QUARTER => 'quarter',
            DateIntervalUnit::YEAR => 'year',
        };

        $sign = $operator === '-' ? -1 : 1;

        return sprintf('DATEADD(%s, %s * %d, %s)', $factorClause, $interval, $sign, $date);
    }

    public function getCurrentDatabaseExpression(): string
    {
        return 'DB_NAME()';
    }

    public function getListViewsSQL(string $database): string
    {
        return <<<'SQL'
            SELECT t.table_name AS viewname
            FROM sys.systable t
            JOIN sys.sysuser u ON t.creator = u.user_id
            WHERE t.table_type = 'VIEW'
            AND u.user_name NOT IN ('SYS', 'dbo')
            ORDER BY t.table_name
            SQL;
    }

    protected function createReservedKeywordsList(): KeywordList
    {
        return new SQLAnywhereKeywordList();
    }

    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return new SQLAnywhereSchemaManager($connection, $this);
    }

    // -----------------------------------------------------------------------
    // DDL generation
    // -----------------------------------------------------------------------

    public function getAlterTableSQL(TableDiff $diff): array
    {
        $sql = [];
        $tableName = $diff->getOldTable()->getQuotedName($this);

        foreach ($diff->getAddedColumns() as $column) {
            $sql[] = sprintf(
                'ALTER TABLE %s ADD %s',
                $tableName,
                $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray()),
            );
        }

        foreach ($diff->getDroppedColumns() as $column) {
            $sql[] = sprintf(
                'ALTER TABLE %s DROP %s',
                $tableName,
                $column->getQuotedName($this),
            );
        }

        foreach ($diff->getModifiedColumns() as $columnDiff) {
            $newColumn = $columnDiff->getNewColumn();
            $sql[] = sprintf(
                'ALTER TABLE %s ALTER %s',
                $tableName,
                $this->getColumnDeclarationSQL($newColumn->getQuotedName($this), $newColumn->toArray()),
            );
        }

        foreach ($diff->getRenamedColumns() as $oldName => $newColumn) {
            $sql[] = sprintf(
                'ALTER TABLE %s RENAME %s TO %s',
                $tableName,
                $oldName,
                $newColumn->getQuotedName($this),
            );
        }

        return $sql;
    }

    public function getDropTableSQL(string $table): string
    {
        return 'DROP TABLE ' . $table;
    }

    // -----------------------------------------------------------------------
    // Type mappings
    // -----------------------------------------------------------------------

    protected function initializeDoctrineTypeMappings(): void
    {
        $this->doctrineTypeMapping = [
            'integer' => 'integer',
            'int' => 'integer',
            'smallint' => 'smallint',
            'tinyint' => 'smallint',
            'bigint' => 'bigint',
            'unsigned int' => 'integer',
            'unsigned bigint' => 'bigint',
            'unsigned smallint' => 'smallint',
            'float' => 'float',
            'double' => 'float',
            'real' => 'float',
            'decimal' => 'decimal',
            'numeric' => 'decimal',
            'money' => 'decimal',
            'smallmoney' => 'decimal',
            'bit' => 'boolean',
            'char' => 'string',
            'varchar' => 'string',
            'nchar' => 'string',
            'nvarchar' => 'string',
            'text' => 'text',
            'ntext' => 'text',
            'long varchar' => 'text',
            'long nvarchar' => 'text',
            'unitext' => 'text',
            'univarchar' => 'string',
            'binary' => 'binary',
            'varbinary' => 'binary',
            'image' => 'blob',
            'long binary' => 'blob',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            'smalldatetime' => 'datetime',
            'timestamp' => 'datetime',
            'uniqueidentifier' => 'guid',
            'xml' => 'text',
        ];
    }

    // -----------------------------------------------------------------------
    // Schema introspection SQL (used by SQLAnywhereSchemaManager)
    // -----------------------------------------------------------------------

    public function getListTablesSQL(): string
    {
        return <<<'SQL'
            SELECT t.table_name
            FROM sys.systable t
            JOIN sys.sysuser u ON t.creator = u.user_id
            WHERE t.table_type = 'BASE'
            AND u.user_name NOT IN ('SYS', 'dbo', 'rs_systabgroup')
            ORDER BY t.table_name
            SQL;
    }

    public function getListTableColumnsSQL(string $table): string
    {
        return sprintf(
            <<<'SQL'
                SELECT
                    c.column_name,
                    d.domain_name AS type,
                    c.width AS length,
                    c.scale,
                    c."default" AS default_value,
                    CASE WHEN c.nulls = 'Y' THEN 1 ELSE 0 END AS nullable,
                    CASE WHEN c."default" = 'autoincrement' OR c."default" = 'global autoincrement'
                        THEN 1 ELSE 0 END AS autoincrement,
                    rm.remarks AS comment
                FROM sys.systabcol c
                JOIN sys.systable t ON c.table_id = t.table_id
                JOIN sys.sysdomain d ON c.domain_id = d.domain_id
                LEFT JOIN sys.sysremark rm ON c.object_id = rm.object_id
                WHERE t.table_name = %s
                ORDER BY c.column_id
                SQL,
            $this->quoteStringLiteral($table),
        );
    }

    public function getListTableIndexesSQL(string $table): string
    {
        return sprintf(
            <<<'SQL'
                SELECT
                    i.index_name,
                    ic.column_name,
                    CASE WHEN i."unique" IN (1, 2) THEN 1 ELSE 0 END AS is_unique,
                    CASE WHEN i."unique" = 1 THEN 1 ELSE 0 END AS is_primary
                FROM sys.sysidx i
                JOIN sys.systable t ON i.table_id = t.table_id
                JOIN sys.sysidxcol ic ON i.table_id = ic.table_id AND i.index_id = ic.index_id
                JOIN sys.systabcol c ON ic.table_id = c.table_id AND ic.column_id = c.column_id
                WHERE t.table_name = %s
                ORDER BY i.index_name, ic.sequence
                SQL,
            $this->quoteStringLiteral($table),
        );
    }

    public function getListTableForeignKeysSQL(string $table): string
    {
        return sprintf(
            <<<'SQL'
                SELECT
                    fk.role AS constraint_name,
                    fc.column_name AS local_column,
                    pt.table_name AS foreign_table,
                    pc.column_name AS foreign_column
                FROM sys.sysfkey fk
                JOIN sys.systable ft ON fk.foreign_table_id = ft.table_id
                JOIN sys.systable pt ON fk.primary_table_id = pt.table_id
                JOIN sys.sysfkcol fkc ON fk.foreign_table_id = fkc.foreign_table_id
                    AND fk.foreign_key_id = fkc.foreign_key_id
                JOIN sys.systabcol fc ON fkc.foreign_table_id = fc.table_id
                    AND fkc.foreign_column_id = fc.column_id
                JOIN sys.systabcol pc ON fkc.primary_table_id = pc.table_id
                    AND fkc.primary_column_id = pc.column_id
                WHERE ft.table_name = %s
                ORDER BY fk.role, fkc.primary_column_id
                SQL,
            $this->quoteStringLiteral($table),
        );
    }
}
