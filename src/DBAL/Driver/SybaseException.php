<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Driver;

use Doctrine\DBAL\Driver\Exception as DriverException;

final class SybaseException extends \RuntimeException implements DriverException
{
    private ?string $sqlState;

    public function __construct(string $message, ?string $sqlState = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->sqlState = $sqlState;
        parent::__construct($message, $code, $previous);
    }

    public static function fromPdoException(\PDOException $e, string $dsn): self
    {
        $sqlState = \is_string($e->getCode()) ? $e->getCode() : null;

        return new self(
            sprintf('Could not connect to SQL Anywhere via ODBC (%s): %s', $dsn, $e->getMessage()),
            $sqlState,
            (int) $e->getCode(),
            $e,
        );
    }

    public function getSQLState(): ?string
    {
        return $this->sqlState;
    }
}
