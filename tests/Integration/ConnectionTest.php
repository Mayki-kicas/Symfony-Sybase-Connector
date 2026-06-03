<?php

declare(strict_types=1);

namespace SybaseConnector\Tests\Integration;

use SybaseConnector\DBAL\Driver\SybaseDriver;
use SybaseConnector\DBAL\SchemaManager\SQLAnywhereSchemaManagerFactory;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests requiring a live SQL Anywhere database.
 *
 * Set environment variables SYBASE_DSN, SYBASE_USER, SYBASE_PASSWORD
 * to run these tests against a real database.
 */
final class ConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        $dsn = getenv('SYBASE_DSN');

        if ($dsn === false || $dsn === '') {
            self::markTestSkipped('SYBASE_DSN not configured — skipping integration tests.');
        }
    }

    public function testCanConnect(): void
    {
        $connection = $this->createConnection();

        $result = $connection->executeQuery('SELECT 1 AS test_value');
        $row = $result->fetchAssociative();

        self::assertNotFalse($row);
        self::assertEquals(1, $row['test_value'] ?? $row['TEST_VALUE']);
    }

    public function testCanGetDatabaseName(): void
    {
        $connection = $this->createConnection();

        $result = $connection->executeQuery('SELECT DB_NAME() AS db_name');
        $row = $result->fetchAssociative();

        self::assertNotFalse($row);
        self::assertNotEmpty($row['db_name'] ?? $row['DB_NAME'] ?? '');
    }

    public function testMultipleSequentialQueries(): void
    {
        $connection = $this->createConnection();

        $result1 = $connection->executeQuery('SELECT 1 AS val');
        $row1 = $result1->fetchAssociative();

        $result2 = $connection->executeQuery('SELECT 2 AS val');
        $row2 = $result2->fetchAssociative();

        self::assertEquals(1, $row1['val'] ?? $row1['VAL']);
        self::assertEquals(2, $row2['val'] ?? $row2['VAL']);
    }

    public function testTopStartAtPagination(): void
    {
        $connection = $this->createConnection();

        $result = $connection->executeQuery('SELECT TOP 1 START AT 1 1 AS val');
        $rows = $result->fetchAllAssociative();

        self::assertCount(1, $rows);
    }

    private function createConnection(): \Doctrine\DBAL\Connection
    {
        $config = new Configuration();

        return DriverManager::getConnection([
            'driverClass' => SybaseDriver::class,
            'driverOptions' => ['dsn' => getenv('SYBASE_DSN')],
            'user' => getenv('SYBASE_USER') ?: '',
            'password' => getenv('SYBASE_PASSWORD') ?: '',
            'schema_manager_factory' => new SQLAnywhereSchemaManagerFactory(),
        ], $config);
    }
}
