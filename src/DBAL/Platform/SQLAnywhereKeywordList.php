<?php

declare(strict_types=1);

namespace SybaseConnector\DBAL\Platform;

use Doctrine\DBAL\Platforms\Keywords\KeywordList;

final class SQLAnywhereKeywordList extends KeywordList
{
    /**
     * SQL Anywhere reserved words.
     *
     * @return list<string>
     */
    protected function getKeywords(): array
    {
        return [
            'ADD', 'ALL', 'ALTER', 'AND', 'ANY', 'AS', 'ASC', 'ATTACH',
            'BACKUP', 'BEGIN', 'BETWEEN', 'BIGINT', 'BINARY', 'BIT', 'BOTTOM', 'BREAK', 'BY',
            'CALL', 'CAPABILITY', 'CASCADE', 'CASE', 'CAST', 'CHAR', 'CHAR_CONVERT', 'CHARACTER',
            'CHECK', 'CHECKPOINT', 'CLOSE', 'COMMENT', 'COMMIT', 'CONNECT', 'CONSTRAINT', 'CONTAINS',
            'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CUBE', 'CURRENT', 'CURRENT_DATE',
            'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR',
            'DATE', 'DATETIME', 'DBSPACE', 'DEALLOCATE', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT',
            'DELETE', 'DELETING', 'DESC', 'DETACH', 'DISTINCT', 'DO', 'DOUBLE', 'DROP',
            'ELSE', 'ELSEIF', 'ENCRYPTED', 'END', 'ESCAPE', 'EXCEPT', 'EXCEPTION', 'EXEC',
            'EXECUTE', 'EXISTING', 'EXISTS', 'EXTERNLOGIN',
            'FETCH', 'FIRST', 'FLOAT', 'FOR', 'FORCE', 'FOREIGN', 'FORWARD', 'FROM', 'FULL',
            'GOTO', 'GRANT', 'GROUP',
            'HAVING', 'HOLDLOCK',
            'IDENTIFIED', 'IF', 'IN', 'INDEX', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT',
            'INSERTING', 'INSTALL', 'INSTEAD', 'INT', 'INTEGER', 'INTEGRATED', 'INTERSECT', 'INTO', 'IQ',
            'IS', 'ISOLATION',
            'JOIN',
            'KEY',
            'LATERAL', 'LEFT', 'LIKE', 'LOCK', 'LOGIN', 'LONG', 'LOOP',
            'MATCH', 'MEMBERSHIP', 'MESSAGE', 'MODE', 'MODIFY', 'MONEY',
            'NATURAL', 'NCHAR', 'NEW', 'NO', 'NOHOLDLOCK', 'NOT', 'NOTIFY', 'NULL', 'NUMERIC', 'NVARCHAR',
            'OF', 'OFF', 'ON', 'OPEN', 'OPTION', 'OPTIONS', 'OR', 'ORDER', 'OTHERS', 'OUT', 'OUTER', 'OVER',
            'PASSTHROUGH', 'PRECISION', 'PREPARE', 'PRIMARY', 'PRINT', 'PRIVILEGES', 'PROC', 'PROCEDURE',
            'PUBLICATION',
            'RAISERROR', 'READTEXT', 'REAL', 'REFERENCE', 'REFERENCES', 'RELEASE', 'REMOTE', 'REMOVE',
            'RENAME', 'REORGANIZE', 'RESOURCE', 'RESTORE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT',
            'ROLLBACK', 'ROLLUP', 'SAVE', 'SAVEPOINT',
            'SCROLL', 'SELECT', 'SENSITIVE', 'SESSION', 'SET', 'SETUSER', 'SHARE', 'SMALLINT',
            'SMALLMONEY', 'SOME', 'SQLCODE', 'SQLSTATE', 'START', 'STOP', 'SUBTRANS', 'SUBTRANSACTION',
            'SYNCHRONIZE',
            'TABLE', 'TEMPORARY', 'THEN', 'TIME', 'TIMESTAMP', 'TINYINT', 'TO', 'TOP', 'TREAT',
            'TRIGGER', 'TRUNCATE',
            'TSEQUAL',
            'UNBOUNDED', 'UNION', 'UNIQUE', 'UNIQUEIDENTIFIER', 'UNKNOWN', 'UNSIGNED', 'UPDATE',
            'UPDATING', 'USER', 'USING',
            'VALIDATE', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARIABLE', 'VARYING', 'VIEW',
            'WAIT', 'WAITFOR', 'WHEN', 'WHERE', 'WHILE', 'WINDOW', 'WITH', 'WITHIN', 'WORK', 'WRITETEXT',
        ];
    }
}
