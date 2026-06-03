<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\SchemaManager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaManagerFactory;

final class SQLAnywhereSchemaManagerFactory implements SchemaManagerFactory
{
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return new SQLAnywhereSchemaManager(
            $connection,
            $connection->getDatabasePlatform(),
        );
    }
}
