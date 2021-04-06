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

/**
 * Class Statement
 *
 * @package VV\Db\Driver\Mysql
 */
class Statement implements \VV\Db\Driver\Statement {

    private ?\mysqli_stmt $stmt;

    private \mysqli $mysqli;

    private \VV\Db\Driver\QueryInfo $query;

    private ?array $blobs = null;

    public function __construct(\mysqli_stmt $stmt, \mysqli $mysqli, \VV\Db\Driver\QueryInfo $query) {
        $this->stmt = $stmt;
        $this->mysqli = $mysqli;
        $this->query = $query;
    }


    /**
     * @inheritdoc
     */
    public function setFetchSize(int $size): void {
        throw new \LogicException('Set fetch size is not supported for this driver');
    }

    /**
     * @inheritdoc
     */
    public function bind(array $params): void {
        if ($params) {
            $bind_types = '';
            static $types = ['string', 'integer', 'double'];

            $this->blobs = [];
            $i = 0;
            $paramsForBind = [];
            foreach ($params as &$param) {
                $type = 's';
                if ($param instanceof \VV\Db\Param) {
                    switch ($param->type()) {
                        // LOBs to end
                        case \VV\Db\Param::T_TEXT:
                        case \VV\Db\Param::T_BLOB:
                            $this->blobs[$i] = &$param->value();
                            $param = null;
                            $type = 'b';
                            break;

                        default:
                            $param = &$param->value();
                            break;
                    }
                }

                $var_type = gettype($param);
                if (in_array($var_type, $types)) {
                    $type = $var_type[0];
                }

                $bind_types .= $type;
                $i++;

                $paramsForBind[] = &$param;
            }
            unset($param);

            $bindReult = $this->stmt->bind_param($bind_types, ...$paramsForBind);
            if (!$bindReult) {
                throw new \RuntimeException('Bind params error', 0, $this->mysqliError());
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function exec(): \VV\Db\Driver\Result {
        if ($this->blobs) {
            foreach ($this->blobs as $nr => $value) {
                if (!$value) continue;
                foreach ($value as $part) {
                    $this->stmt->send_long_data($nr, $part);
                }
            }
        }

        if (!$this->stmt->execute()) {
            throw new \VV\Db\Exceptions\SqlSyntaxError(null, null, $this->mysqliError());
        }

        return new Result($this->stmt);
    }

    /**
     * @inheritdoc
     */
    public function close(): void {
        $this->stmt->close();
        $this->stmt = null;
    }

    public function mysqliError() {
        return \VV\Db\Mysql\Driver::mysqliError($this->mysqli, $this->query->string());
    }
}
