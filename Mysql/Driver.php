<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Mysql;

use VV\Db\Driver\Driver as DriverAlias;
use VV\Db\Driver\QueryStringifiers;
use VV\Db\Sql;

/**
 * Class Driver
 *
 * @package VV\Db\Driver
 */
class Driver implements \VV\Db\Driver\Driver {

    const DFLT_CHARSET = 'UTF8';

    public function connect(string $host, string $user, string $passwd, ?string $scheme, ?string $charset): Connection {
        $mysqli = @new \mysqli($host, $user, $passwd, $scheme);
        if ($code = $mysqli->connect_errno) {
            $message = $mysqli->connect_error;
            if (\VV\OS\WIN) $message = \VV\toUtf8($message);

            throw new MysqliError($message, $code);
        }

        // set session params
        $res = $mysqli->set_charset($charset ?: self::DFLT_CHARSET);
        if (!$res) {
            throw new \RuntimeException('Can\'t set charset', 0, self::mysqliError($mysqli));
        }

        $res = $mysqli->query("SET SQL_MODE='PIPES_AS_CONCAT'"); // ,TRADITIONAL
        if (!$res) {
            throw new \VV\Db\Exceptions\SqlExecutionError('Can\'t set SQL_MODE', 0, self::mysqliError($mysqli));
        }

        $res = $mysqli->autocommit(false);
        if (!$res) {
            throw new \RuntimeException('Can\'t disable autocommit', 0, self::mysqliError($mysqli));
        }

        return new Connection($mysqli);
    }

    /**
     * @param Sql\SelectQuery $query
     *
     * @return QueryStringifiers\SelectStringifier
     */
    public function createSelectStringifier(Sql\SelectQuery $query): QueryStringifiers\SelectStringifier {
        return new SqlStringifier\SelectStringifier($query, $this);
    }

    /**
     * @param Sql\InsertQuery $query
     *
     * @return QueryStringifiers\InsertStringifier
     */
    public function createInsertStringifier(Sql\InsertQuery $query): QueryStringifiers\InsertStringifier {
        return new SqlStringifier\InsertStringifier($query, $this);
    }

    /**
     * @param Sql\UpdateQuery $query
     *
     * @return QueryStringifiers\UpdateStringifier
     */
    public function createUpdateStringifier(Sql\UpdateQuery $query): QueryStringifiers\UpdateStringifier {
        return new SqlStringifier\UpdateStringifier($query, $this);
    }

    /**
     * @param Sql\DeleteQuery $query
     *
     * @return QueryStringifiers\DeleteStringifier
     */
    public function createDeleteStringifier(Sql\DeleteQuery $query): QueryStringifiers\DeleteStringifier {
        return new SqlStringifier\DeleteStringifier($query, $this);
    }

    /**
     * @inheritdoc
     */
    public function dbmsName(): string {
        return DriverAlias::DBMS_MYSQL;
    }

    /**
     * @param \mysqli $mysqli
     * @param string  $queryString
     *
     * @return MysqliError|null
     */
    static function mysqliError(\mysqli $mysqli, $queryString = null) {
        if (!$code = $mysqli->errno) return null;

        $message = $mysqli->error;
        //if (\VV\OS\WIN) $message = \VV\toUtf8($message);

        if ($queryString) $message .= "\n$queryString\n";

        return new MysqliError($message, $code);
    }
}
