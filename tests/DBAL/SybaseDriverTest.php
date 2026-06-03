<?php

declare(strict_types=1);

namespace SybaseConnector\Tests\DBAL;

use SybaseConnector\DBAL\Driver\SybaseDriver;
use SybaseConnector\DBAL\Driver\SybaseExceptionConverter;
use SybaseConnector\DBAL\Platform\SQLAnywherePlatform;
use PHPUnit\Framework\TestCase;

final class SybaseDriverTest extends TestCase
{
    private SybaseDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new SybaseDriver();
    }

    public function testGetDatabasePlatformReturnsSQLAnywherePlatform(): void
    {
        $versionProvider = $this->createMock(\Doctrine\DBAL\ServerVersionProvider::class);
        $platform = $this->driver->getDatabasePlatform($versionProvider);

        self::assertInstanceOf(SQLAnywherePlatform::class, $platform);
    }

    public function testGetExceptionConverterReturnsSybaseExceptionConverter(): void
    {
        $converter = $this->driver->getExceptionConverter();

        self::assertInstanceOf(SybaseExceptionConverter::class, $converter);
    }

    public function testBuildDsnFromHostPortDatabase(): void
    {
        // Use reflection to test DSN building
        $method = new \ReflectionMethod(SybaseDriver::class, 'buildDsn');

        $dsn = $method->invoke($this->driver, [
            'host' => 'myserver.example.com',
            'port' => 2639,
            'dbname' => 'SPACE_DB',
        ]);

        self::assertSame(
            'odbc:Driver=FreeTDS;Server=myserver.example.com;Port=2639;Database=SPACE_DB;TDS_Version=5.0',
            $dsn,
        );
    }

    public function testBuildDsnWithCustomTdsVersion(): void
    {
        $method = new \ReflectionMethod(SybaseDriver::class, 'buildDsn');

        $dsn = $method->invoke($this->driver, [
            'host' => 'localhost',
            'port' => 5000,
            'dbname' => 'testdb',
            'driverOptions' => ['tds_version' => '7.0'],
        ]);

        self::assertSame(
            'odbc:Driver=FreeTDS;Server=localhost;Port=5000;Database=testdb;TDS_Version=7.0',
            $dsn,
        );
    }

    public function testBuildDsnWithDirectDsnOverride(): void
    {
        $method = new \ReflectionMethod(SybaseDriver::class, 'buildDsn');

        $dsn = $method->invoke($this->driver, [
            'driverOptions' => ['dsn' => 'odbc:Driver=FreeTDS;Server=custom;Port=1234;Database=mydb;TDS_Version=8.0'],
        ]);

        self::assertSame(
            'odbc:Driver=FreeTDS;Server=custom;Port=1234;Database=mydb;TDS_Version=8.0',
            $dsn,
        );
    }

    public function testBuildDsnDefaults(): void
    {
        $method = new \ReflectionMethod(SybaseDriver::class, 'buildDsn');

        $dsn = $method->invoke($this->driver, []);

        self::assertSame(
            'odbc:Driver=FreeTDS;Server=localhost;Port=2639;Database=;TDS_Version=5.0',
            $dsn,
        );
    }
}
