<?php

declare(strict_types=1);

namespace SybaseConnector\Tests\DBAL;

use SybaseConnector\DBAL\Platform\SQLAnywherePlatform;
use PHPUnit\Framework\TestCase;

final class SQLAnywherePlatformTest extends TestCase
{
    private SQLAnywherePlatform $platform;

    protected function setUp(): void
    {
        $this->platform = new SQLAnywherePlatform();
    }

    public function testModifyLimitQueryWithLimitOnly(): void
    {
        $sql = 'SELECT * FROM users';
        $result = $this->callModifyLimitQuery($sql, 10, 0);

        self::assertSame('SELECT TOP 10 START AT 1 * FROM users', $result);
    }

    public function testModifyLimitQueryWithLimitAndOffset(): void
    {
        $sql = 'SELECT * FROM users';
        $result = $this->callModifyLimitQuery($sql, 10, 20);

        self::assertSame('SELECT TOP 10 START AT 21 * FROM users', $result);
    }

    public function testModifyLimitQueryWithOffsetOnly(): void
    {
        $sql = 'SELECT * FROM users';
        $result = $this->callModifyLimitQuery($sql, null, 5);

        self::assertSame('SELECT TOP 2147483647 START AT 6 * FROM users', $result);
    }

    public function testModifyLimitQueryNoLimitNoOffset(): void
    {
        $sql = 'SELECT * FROM users';
        $result = $this->callModifyLimitQuery($sql, null, 0);

        self::assertSame($sql, $result);
    }

    public function testModifyLimitQueryWithDistinct(): void
    {
        $sql = 'SELECT DISTINCT u.id FROM users u';
        $result = $this->callModifyLimitQuery($sql, 5, 0);

        self::assertSame('SELECT DISTINCT TOP 5 START AT 1 u.id FROM users u', $result);
    }

    public function testGetBooleanTypeDeclarationSQL(): void
    {
        self::assertSame('BIT', $this->platform->getBooleanTypeDeclarationSQL([]));
    }

    public function testGetIntegerTypeDeclarationSQL(): void
    {
        self::assertSame('INTEGER', $this->platform->getIntegerTypeDeclarationSQL([]));
    }

    public function testGetIntegerAutoIncrementTypeDeclarationSQL(): void
    {
        self::assertSame(
            'INTEGER DEFAULT AUTOINCREMENT',
            $this->platform->getIntegerTypeDeclarationSQL(['autoincrement' => true]),
        );
    }

    public function testGetBigIntTypeDeclarationSQL(): void
    {
        self::assertSame('BIGINT', $this->platform->getBigIntTypeDeclarationSQL([]));
    }

    public function testGetClobTypeDeclarationSQL(): void
    {
        self::assertSame('LONG VARCHAR', $this->platform->getClobTypeDeclarationSQL([]));
    }

    public function testGetBlobTypeDeclarationSQL(): void
    {
        self::assertSame('LONG BINARY', $this->platform->getBlobTypeDeclarationSQL([]));
    }

    public function testGetDateTimeTypeDeclarationSQL(): void
    {
        self::assertSame('DATETIME', $this->platform->getDateTimeTypeDeclarationSQL([]));
    }

    public function testGetCurrentDateSQL(): void
    {
        self::assertSame('CURRENT DATE', $this->platform->getCurrentDateSQL());
    }

    public function testGetCurrentTimestampSQL(): void
    {
        self::assertSame('CURRENT TIMESTAMP', $this->platform->getCurrentTimestampSQL());
    }

    public function testSupportsIdentityColumns(): void
    {
        self::assertTrue($this->platform->supportsIdentityColumns());
    }

    public function testTypeMappings(): void
    {
        // Force initialization
        self::assertSame('integer', $this->platform->getDoctrineTypeMapping('int'));
        self::assertSame('integer', $this->platform->getDoctrineTypeMapping('integer'));
        self::assertSame('boolean', $this->platform->getDoctrineTypeMapping('bit'));
        self::assertSame('decimal', $this->platform->getDoctrineTypeMapping('money'));
        self::assertSame('text', $this->platform->getDoctrineTypeMapping('long varchar'));
        self::assertSame('blob', $this->platform->getDoctrineTypeMapping('image'));
        self::assertSame('blob', $this->platform->getDoctrineTypeMapping('long binary'));
        self::assertSame('string', $this->platform->getDoctrineTypeMapping('varchar'));
        self::assertSame('guid', $this->platform->getDoctrineTypeMapping('uniqueidentifier'));
        self::assertSame('datetime', $this->platform->getDoctrineTypeMapping('datetime'));
        self::assertSame('date', $this->platform->getDoctrineTypeMapping('date'));
    }

    public function testGetListTablesSQL(): void
    {
        $sql = $this->platform->getListTablesSQL();
        self::assertStringContainsString('sys.systable', $sql);
        self::assertStringContainsString("table_type = 'BASE'", $sql);
    }

    public function testGetListTableColumnsSQL(): void
    {
        $sql = $this->platform->getListTableColumnsSQL('customer');
        self::assertStringContainsString('sys.systabcol', $sql);
        self::assertStringContainsString('sys.sysdomain', $sql);
        self::assertStringContainsString("'customer'", $sql);
    }

    /**
     * Call the protected doModifyLimitQuery via reflection.
     */
    private function callModifyLimitQuery(string $query, ?int $limit, int $offset): string
    {
        $method = new \ReflectionMethod(SQLAnywherePlatform::class, 'doModifyLimitQuery');

        return $method->invoke($this->platform, $query, $limit, $offset);
    }
}
