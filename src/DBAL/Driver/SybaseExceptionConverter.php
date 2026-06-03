<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Driver;

use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query;

final class SybaseExceptionConverter implements ExceptionConverter
{
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        $message = $exception->getMessage();

        // SQL Anywhere error codes (from message text since ODBC wraps them)
        return match (true) {
            str_contains($message, 'already exists') => new TableExistsException($exception, $query),
            str_contains($message, 'not found') && str_contains($message, 'Table') => new TableNotFoundException($exception, $query),
            str_contains($message, 'Column') && str_contains($message, 'not found') => new InvalidFieldNameException($exception, $query),
            str_contains($message, 'Integrity constraint violation') => new UniqueConstraintViolationException($exception, $query),
            str_contains($message, 'Foreign key') => new ForeignKeyConstraintViolationException($exception, $query),
            str_contains($message, 'cannot be NULL') => new NotNullConstraintViolationException($exception, $query),
            str_contains($message, 'Ambiguous') => new NonUniqueFieldNameException($exception, $query),
            str_contains($message, 'Syntax error') => new SyntaxErrorException($exception, $query),
            str_contains($message, '08S01') || str_contains($message, 'Communication link') => new ConnectionException($exception, $query),
            default => new DriverException($exception, $query),
        };
    }
}
