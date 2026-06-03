<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\SchemaManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * Schema manager for SAP SQL Anywhere.
 *
 * Introspects database schema using SQL Anywhere system catalog views:
 * sys.systable, sys.systabcol, sys.sysdomain, sys.sysidx, sys.sysfkey, etc.
 *
 * @extends AbstractSchemaManager<\SybaseConnector\DBAL\Platform\SQLAnywherePlatform>
 */
class SQLAnywhereSchemaManager extends AbstractSchemaManager
{
    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableDefinition(array $table): string
    {
        return $table['table_name'] ?? $table['TABLE_NAME'] ?? '';
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        $dbType = strtolower(trim($tableColumn['type'] ?? $tableColumn['TYPE'] ?? 'varchar'));
        $length = (int) ($tableColumn['length'] ?? $tableColumn['LENGTH'] ?? 0);
        $scale = (int) ($tableColumn['scale'] ?? $tableColumn['SCALE'] ?? 0);
        $nullable = (bool) ($tableColumn['nullable'] ?? $tableColumn['NULLABLE'] ?? false);
        $autoincrement = (bool) ($tableColumn['autoincrement'] ?? $tableColumn['AUTOINCREMENT'] ?? false);
        $default = $tableColumn['default_value'] ?? $tableColumn['DEFAULT_VALUE'] ?? null;
        $comment = $tableColumn['comment'] ?? $tableColumn['COMMENT'] ?? null;
        $columnName = $tableColumn['column_name'] ?? $tableColumn['COLUMN_NAME'] ?? '';

        $type = $this->extractDoctrineType($dbType);
        $precision = null;

        if (\in_array($dbType, ['decimal', 'numeric', 'money', 'smallmoney'], true)) {
            $precision = $length;
        }

        if ($autoincrement && $default !== null) {
            $default = null;
        }

        $column = new Column($columnName, Type::getType($type));
        $column->setNotnull(!$nullable);
        $column->setAutoincrement($autoincrement);

        if ($length > 0 && !\in_array($type, [Types::TEXT, Types::BLOB], true)) {
            $column->setLength($length);
        }

        if ($precision !== null) {
            $column->setPrecision($precision);
        }

        if ($scale > 0) {
            $column->setScale($scale);
        }

        if ($default !== null && !$autoincrement) {
            $column->setDefault($this->normalizeDefault($default));
        }

        if ($comment !== null && $comment !== '') {
            $column->setComment($comment);
        }

        return $column;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableIndexesList(array $tableIndexes, string $tableName): array
    {
        $grouped = [];

        foreach ($tableIndexes as $row) {
            $indexName = $row['index_name'] ?? $row['INDEX_NAME'] ?? '';
            $columnName = $row['column_name'] ?? $row['COLUMN_NAME'] ?? '';
            $isUnique = (bool) ($row['is_unique'] ?? $row['IS_UNIQUE'] ?? false);
            $isPrimary = (bool) ($row['is_primary'] ?? $row['IS_PRIMARY'] ?? false);

            $grouped[$indexName]['columns'][] = $columnName;
            $grouped[$indexName]['unique'] = $isUnique;
            $grouped[$indexName]['primary'] = $isPrimary;
        }

        $indexes = [];

        foreach ($grouped as $name => $data) {
            $indexes[$name] = new Index(
                (string) $name,
                $data['columns'],
                $data['unique'],
                $data['primary'],
            );
        }

        return $indexes;
    }

    /**
     * {@inheritDoc}
     */
    protected function _getPortableTableForeignKeysList(array $tableForeignKeys): array
    {
        $grouped = [];

        foreach ($tableForeignKeys as $row) {
            $constraintName = $row['constraint_name'] ?? $row['CONSTRAINT_NAME'] ?? '';
            $localColumn = $row['local_column'] ?? $row['LOCAL_COLUMN'] ?? '';
            $foreignTable = $row['foreign_table'] ?? $row['FOREIGN_TABLE'] ?? '';
            $foreignColumn = $row['foreign_column'] ?? $row['FOREIGN_COLUMN'] ?? '';

            $grouped[$constraintName]['local'][] = $localColumn;
            $grouped[$constraintName]['foreign'][] = $foreignColumn;
            $grouped[$constraintName]['foreignTable'] = $foreignTable;
        }

        $fkeys = [];

        foreach ($grouped as $name => $data) {
            $fkeys[] = new ForeignKeyConstraint(
                $data['local'],
                $data['foreignTable'],
                $data['foreign'],
                (string) $name,
            );
        }

        return $fkeys;
    }

    private function extractDoctrineType(string $dbType): string
    {
        $platform = $this->platform;

        try {
            return $platform->getDoctrineTypeMapping($dbType);
        } catch (\Throwable) {
            return Types::STRING;
        }
    }

    private function normalizeDefault(mixed $default): ?string
    {
        if ($default === null) {
            return null;
        }

        $value = trim((string) $default);

        // Strip SQL Anywhere default keywords
        if (\in_array(strtolower($value), ['autoincrement', 'global autoincrement', 'current date', 'current timestamp'], true)) {
            return null;
        }

        // Remove surrounding quotes
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
