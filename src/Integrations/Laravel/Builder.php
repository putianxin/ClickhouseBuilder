<?php

namespace Ptx\ClickhouseBuilder\Integrations\Laravel;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Traits\Macroable;
use Tinderbox\Clickhouse\Common\Format;
use Tinderbox\Clickhouse\Query;
use Tinderbox\Clickhouse\Query\QueryStatistic;
use Ptx\ClickhouseBuilder\Query\BaseBuilder;
use Ptx\ClickhouseBuilder\Query\Expression;
use Ptx\ClickhouseBuilder\Query\Grammar;

class Builder extends BaseBuilder
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Connection which is used to perform queries.
     *
     * @var \Ptx\ClickhouseBuilder\Integrations\Laravel\Connection
     */
    protected $connection;

    /**
     * Builder constructor.
     *
     * @param \Ptx\ClickhouseBuilder\Integrations\Laravel\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = new Grammar();
    }

    /**
     * Perform compiled from builder sql query and getting result.
     *
     * @return \Tinderbox\Clickhouse\Query\Result|\Tinderbox\Clickhouse\Query\Result[]
     * @throws Tinderbox\Clickhouse\Exceptions\ClientException
     *
     */
    public function get($columns = null)
    {
        if ($columns) {
            $this->select($columns);
        }

        if (!empty($this->async)) {
            $return = $this->connection->selectAsync($this->toAsyncQueries());
        } else {
            $return = $this->connection->select($this->toSql(), [], $this->getFiles());
        }
        return $return ? collect($return) : null;
    }

    /**
     * Returns Query instance.
     *
     * @param array $settings
     *
     * @return Query
     */
    public function toQuery(array $settings = []): Query
    {
        return new Query($this->connection->getServer(), $this->toSql(), $this->getFiles(), $settings);
    }

    /**
     * Performs compiled sql for count rows only. May be used for pagination
     * Works only without async queries.
     *
     * @return int|mixed
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     *
     */
    public function count()
    {
        $builder = $this->getCountQuery();
        $result = $builder->get();

        if (!empty($this->groups)) {
            return $result ? $result->count() : 0;
        } else {
            return $result ? $result->first()['count'] : 0;
        }
    }

    /**
     * @param $column
     * @return int
     * 2022-07-18 add
     */
    public function sum($column)
    {
        $builder = $this->getSumQuery($column);
        $result = $builder->get();

        return $result ? $result->first()['sum'] : 0;
    }

    public function value($column)
    {
        $result = $this->limit(1)->get($column);

        return $result ? $result->first()[$column] : null;
    }

    /**
     * @param $value
     * @param $callback
     * @param null $default
     * @return $this|Builder
     * 2022-07-18 add
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Perform query and get first row.
     *
     * @return mixed|null|\Tinderbox\Clickhouse\Query\Result
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     *
     */
    public function first($column = null)
    {
        $result = $this->limit(1)->get($column);

        return $result ? (object)$result->first() : null;
    }

    public function distinct($column = '')
    {
        $this->distinct = $column;
        return $this;
    }

    /**
     * Makes clean instance of builder.
     *
     * @return self
     */
    public function newQuery(): Builder
    {
        return new static($this->connection);
    }

    /**
     * Insert in table data from files.
     *
     * @param array $columns
     * @param array $files
     * @param string $format
     * @param int $concurrency
     *
     * @return array
     */
    public function insertFiles(array $columns, array $files, string $format = Format::CSV, int $concurrency = 5): array
    {
        return $this->connection->insertFiles((string)$this->getFrom()->getTable(), $columns, $files, $format, $concurrency);
    }

    /**
     * Insert in table data from files.
     *
     * @param array $columns
     * @param string|\Tinderbox\Clickhouse\Interfaces\FileInterface $file
     * @param string $format
     *
     * @return bool
     */
    public function insertFile(array $columns, $file, string $format = Format::CSV): bool
    {
        $file = $this->prepareFile($file);

        $result = $this->connection->insertFiles($this->getFrom()->getTable(), $columns, [$file], $format);

        return $result[0][0];
    }

    /**
     * Performs insert query.
     *
     * @param array $values
     * @param bool $skipSort
     *
     * @return bool
     * @throws \Ptx\ClickhouseBuilder\Exceptions\GrammarException
     *
     */
    public function insert(array $values, bool $skipSort = false)
    {
        if (empty($values)) {
            return false;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        } /*
         * Here, we will sort the insert keys for every record so that each insert is
         * in the same order for the record. We need to make sure this is the case
         * so there are not any errors or problems when inserting these records.
         */
        elseif (!$skipSort) {
            foreach ($values as $key => $value) {
                ksort($value);
                $values[$key] = $value;
            }
        }

        return $this->connection->insert($this->grammar->compileInsert($this, $values));
    }

    /**
     * Performs ALTER TABLE `table` DELETE query.
     *
     * @return int
     * @throws \Ptx\ClickhouseBuilder\Exceptions\GrammarException
     *
     */
    public function delete()
    {
        return $this->connection->delete($this->grammar->compileDelete($this));
    }

    /**
     * Paginate the given query.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return LengthAwarePaginator
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     *
     */
    public function paginate(int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $count = (int)$this->getConnection()
            ->table($this->cloneWithout(['columns' => [], 'orders' => [], 'limit' => null])
                ->select(new Expression('1')))
            ->count();

        $results = $this->limit($perPage, $perPage * ($page - 1))->get();

        return new LengthAwarePaginator(
            $results,
            $count,
            $perPage,
            $page
        );
    }

    /**
     * @return \stdClass
     * @throws \Tinderbox\Clickhouse\Exceptions\ClientException
     * 分页
     */
    public function apiPages()
    {
        $page = request()->input('page', 1);
        $page_size = request()->input('row', 10);
        $offset = ($page - 1) * $page_size;
        $data = new \stdClass();
        $data->count = $this->count();
        $data->data = $this->limit($page_size, $offset)->get();
        $data->page = $page;
        return $data;
    }

    /**
     * Get last query statistics from the connection.
     *
     * @return QueryStatistic
     * @throws \Ptx\ClickhouseBuilder\Exceptions\BuilderException
     *
     */
    public function getLastQueryStatistics(): QueryStatistic
    {
        return $this->getConnection()->getLastQueryStatistic();
    }

    /**
     * Get connection.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
