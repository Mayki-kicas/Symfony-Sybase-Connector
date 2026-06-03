<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Driver;

use SybaseConnector\DBAL\Platform\SQLAnywherePlatform;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\ServerVersionProvider;
use PDO;
use PDOException;

final class SybaseDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(
        #[\SensitiveParameter]
        array $params,
    ): DriverConnection {
        $dsn = $this->buildDsn($params);
        $user = $params['user'] ?? '';
        $password = $params['password'] ?? '';

        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 10,
        ];

        if (isset($params['driverOptions']) && \is_array($params['driverOptions'])) {
            foreach ($params['driverOptions'] as $key => $value) {
                if (\is_int($key)) {
                    $pdoOptions[$key] = $value;
                }
            }
        }

        $attempt = 0;
        $maxAttempts = 2;

        while (true) {
            ++$attempt;

            try {
                $pdo = new PDO($dsn, $user, $password, $pdoOptions);

                return new SybaseConnection($pdo);
            } catch (PDOException $e) {
                $isTransient = $attempt < $maxAttempts
                    && $e->getCode() === '08S01'
                    && str_contains($e->getMessage(), '20009');

                if (!$isTransient) {
                    throw SybaseException::fromPdoException($e, $dsn);
                }

                usleep(200_000);
            }
        }
    }

    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        return new SQLAnywherePlatform();
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new SybaseExceptionConverter();
    }

    private function buildDsn(array $params): string
    {
        // Support direct DSN override via driverOptions
        if (isset($params['driverOptions']['dsn']) && \is_string($params['driverOptions']['dsn'])) {
            return $params['driverOptions']['dsn'];
        }

        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 2639;
        $database = $params['dbname'] ?? '';
        $tdsVersion = $params['driverOptions']['tds_version'] ?? '5.0';

        return sprintf(
            'odbc:Driver=FreeTDS;Server=%s;Port=%d;Database=%s;TDS_Version=%s',
            $host,
            (int) $port,
            $database,
            $tdsVersion,
        );
    }
}
