<?php

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


declare(strict_types=1);

namespace VV\Db\Mysqli;

use VV\Db\Exceptions\SqlExecutionError;
use VV\Db\Sql\Stringifiers\Factory as StringifiersFactory;

class Driver implements \VV\Db\Driver\Driver
{
    public const DFLT_CHARSET = 'UTF8';

    /**
     * @inheritdoc
     */
    public function connect(string $host, string $user, string $passwd, ?string $scheme, ?string $charset): Connection
    {
        $mysqli = @new \mysqli($host, $user, $passwd, $scheme);
        if ($code = $mysqli->connect_errno) {
            $message = self::toUtf8($mysqli->connect_error);

            throw new MysqliError($message ?: 'MySQLi Error', $code);
        }

        // set session params
        $res = $mysqli->set_charset($charset ?: self::DFLT_CHARSET);
        if (!$res) {
            throw new \RuntimeException('Can\'t set charset', 0, self::createMysqliError($mysqli));
        }

        $res = $mysqli->query("SET SQL_MODE='PIPES_AS_CONCAT'"); // ,TRADITIONAL
        if (!$res) {
            throw new SqlExecutionError('Can\'t set SQL_MODE', 0, self::createMysqliError($mysqli));
        }

        $res = $mysqli->autocommit(false);
        if (!$res) {
            throw new \RuntimeException('Can\'t disable autocommit', 0, self::createMysqliError($mysqli));
        }

        return new Connection($mysqli);
    }

    /**
     * @inheritdoc
     */
    public function getDbmsName(): string
    {
        return self::DBMS_MYSQL;
    }

    /**
     * @inheritdoc
     */
    public function getSqlStringifiersFactory(): ?StringifiersFactory
    {
        return null;
    }

    /**
     * @param \mysqli     $mysqli
     * @param string|null $queryString
     *
     * @return MysqliError|null
     */
    public static function createMysqliError(\mysqli $mysqli, string $queryString = null): ?MysqliError
    {
        if (!$code = $mysqli->errno) {
            return null;
        }

        $message = $mysqli->error;
        if ($queryString) {
            $message .= "\n$queryString\n";
        }

        return new MysqliError($message ?: 'MySQLi Error', $code);
    }

    protected static function toUtf8(string $message): string
    {
        $convert2utf8 = function_exists('iconv')
                        && str_starts_with(PHP_OS, 'WIN')
                        && !mb_check_encoding($message, 'UTF-8');

        if ($convert2utf8) {
            return iconv('cp1251', 'utf-8//IGNORE', $message);
        }

        return $message;
    }
}
