<?php declare(strict_types=1);

/*
 * This file is part of the VV package.
 *
 * (c) Volodymyr Sarnytskyi <v00v4n@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace VV\Db\Mysqli;

/**
 * Class Connection
 *
 * @package VV\Db\Driver\Mysql
 */
class Connection implements \VV\Db\Driver\Connection {

    private ?\mysqli $mysqli;

    /**
     * Connection constructor.
     *
     * @param \mysqli $mysqli
     */
    public function __construct(\mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * @inheritdoc
     */
    public function prepare(\VV\Db\Driver\QueryInfo $query): \VV\Db\Driver\Statement {
        $mysqli = $this->mysqli;
        $queryString = $query->string();

        $mysqliError = function () use ($mysqli, $queryString) {
            return \VV\Db\Mysqli\Driver::mysqliError($mysqli, $queryString);
        };

        $stmt = $mysqli->prepare($queryString);
        if (!$stmt) {
            throw new \VV\Db\Exceptions\SqlExecutionError(null, null, $mysqliError());
        }

        return new Statement($stmt, $mysqli, $query);
    }

    /**
     * @inheritDoc
     */
    public function startTransaction(): void { }

    /**
     * @inheritdoc
     */
    public function commit(bool $autocommit = false): void {
        $this->mysqli->commit();
    }

    /**
     * @inheritdoc
     */
    public function rollback(): void {
        $this->mysqli->rollback();
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): void {
        $this->mysqli->close();
        $this->mysqli = null;
    }
}
