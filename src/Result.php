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
 * Class Result
 *
 * @package VV\Db\Mysql
 */
class Result implements \VV\Db\Driver\Result, \VV\Db\Driver\SeekableResult
{

    private \mysqli_stmt $stmt;

    public function __construct(\mysqli_stmt $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * @inheritdoc
     */
    public function seek(int $offset)
    {
        $this->stmt->data_seek($offset);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(int $flags): \Traversable
    {
        $res = $this->stmt->get_result();
        if (!$res) {
            throw new \RuntimeException('get_result error');
        }

        $myFlags = 0;
        if ($flags & \VV\Db::FETCH_ASSOC) {
            $myFlags |= MYSQLI_ASSOC;
        }
        if ($flags & \VV\Db::FETCH_NUM) {
            $myFlags |= MYSQLI_ASSOC;
        }

        while ($row = $res->fetch_array($myFlags)) {
            yield $row;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInsertedId(): int|string|null
    {
        return $this->stmt->insert_id;
    }

    /**
     * @inheritdoc
     */
    public function getAffectedRows(): int
    {
        return $this->stmt->affected_rows;
    }

    /**
     * @inheritdoc
     */
    public function close(): void { }
}
