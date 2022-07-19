<?php

namespace Ptx\ClickhouseBuilder\Query;

use Ptx\ClickhouseBuilder\Query\Enums\Operator;

class TwoElementsLogicExpression
{
    /**
     * First element.
     *
     * May be array or TwoElementsLogicExpression.
     *
     * @var mixed
     */
    public $firstElement;

    /**
     * Operator.
     *
     * @var Operator
     */
    public $operator;

    /**
     * Second element.
     *
     * @var mixed
     */
    public $secondElement;

    /**
     * Operator which concatenates main statement.
     *
     * May be OR or AND
     *
     * @var Operator
     */
    public $concatenationOperator;

    /**
     * Builder.
     *
     * @var Builder
     */
    private $query;

    /**
     * TwoElementsLogicExpression constructor.
     *
     * @param BaseBuilder $query
     */
    public function __construct(BaseBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Set first element.
     *
     * @param mixed $element
     *
     * @return $this
     */
    public function firstElement($element)
    {
        $this->firstElement = $element;

        return $this;
    }

    /**
     * Operator between two elements.
     *
     * @param string $operator
     *
     * @return $this
     */
    public function operator(string $operator)
    {
        $this->operator = new Operator($operator);

        return $this;
    }

    /**
     * Set second element.
     *
     * @param mixed $element
     *
     * @return $this
     */
    public function secondElement($element)
    {
        $this->secondElement = $element;

        return $this;
    }

    /**
     * Set concatenate operator.
     *
     * @param string $operator
     *
     * @return $this
     */
    public function concatOperator(string $operator)
    {
        $this->concatenationOperator = new Operator($operator);

        return $this;
    }

    /**
     * Build query string for first element.
     *
     * @param \Closure|Builder $query
     *
     * @return TwoElementsLogicExpression
     */
    public function firstElementQuery($query): self
    {
        if ($query instanceof \Closure) {
            $query = tap($this->query->newQuery(), $query);
        }

        if ($query instanceof BaseBuilder) {
            $this->firstElement(new Expression("({$query->toSql()})"));
        }

        return $this;
    }

    /**
     * Build query string for second element.
     *
     * @param $query
     *
     * @return TwoElementsLogicExpression
     */
    public function secondElementQuery($query): self
    {
        if ($query instanceof \Closure) {
            $query = tap($this->query->newQuery(), $query);
        }

        if ($query instanceof BaseBuilder) {
            $this->secondElement(new Expression("({$query->toSql()})"));
        }

        return $this;
    }

    /**
     * Get first element.
     *
     * @return mixed
     */
    public function getFirstElement()
    {
        return $this->firstElement;
    }

    /**
     * Get operator.
     *
     * @return mixed
     */
    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    /**
     * Get seconds element.
     *
     * @return mixed
     */
    public function getSecondElement()
    {
        return $this->secondElement;
    }

    /**
     * Get concatenation operator.
     *
     * @return mixed
     */
    public function getConcatenationOperator(): Operator
    {
        return $this->concatenationOperator;
    }
}
