<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Driver;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\PDO\Result;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use PDO;

/**
 * PDO connection wrapper for SQL Anywhere via FreeTDS/ODBC.
 *
 * Handles the "Invalid cursor state" issue: FreeTDS only allows one active
 * cursor per connection. This wrapper closes the previous cursor before
 * any new query or prepare call.
 */
final class SybaseConnection implements Connection
{
    private ?\PDOStatement $lastStatement = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function prepare(string $sql): StatementInterface
    {
        $this->closePreviousCursor();

        $stmt = $this->pdo->prepare($sql);

        return new Statement($stmt);
    }

    public function query(string $sql): ResultInterface
    {
        $this->closePreviousCursor();

        $stmt = $this->pdo->query($sql);
        $this->lastStatement = $stmt;

        return new Result($stmt);
    }

    public function quote(string $value): string
    {
        $quoted = $this->pdo->quote($value);

        if ($quoted === false) {
            // Some ODBC drivers don't support PDO::quote()
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return $quoted;
    }

    public function exec(string $sql): int|string
    {
        $this->closePreviousCursor();

        return $this->pdo->exec($sql);
    }

    public function lastInsertId(): int|string
    {
        // SQL Anywhere doesn't support PDO::lastInsertId() via ODBC.
        // Use @@IDENTITY instead.
        $stmt = $this->pdo->query('SELECT @@IDENTITY AS last_id');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $row['last_id'] ?? 0;
    }

    public function beginTransaction(): void
    {
        $this->closePreviousCursor();
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function getNativeConnection(): PDO
    {
        return $this->pdo;
    }

    public function getServerVersion(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?: 'unknown';
    }

    private function closePreviousCursor(): void
    {
        if ($this->lastStatement !== null) {
            try {
                $this->lastStatement->closeCursor();
            } catch (\Throwable) {
                // Ignore — cursor may already be closed
            }
            $this->lastStatement = null;
        }
    }
}
