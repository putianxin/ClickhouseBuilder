<?php

namespace Ptx\ClickhouseBuilder\Query\Traits;

use Ptx\ClickhouseBuilder\Query\BaseBuilder as Builder;
use Ptx\ClickhouseBuilder\Query\TwoElementsLogicExpression;

trait HavingsComponentCompiler
{
    /**
     * Compiles havings to string to pass this string in query.
     *
     * @param Builder                      $builder
     * @param TwoElementsLogicExpression[] $havings
     *
     * @return string
     */
    public function compileHavingsComponent(Builder $builder, array $havings): string
    {
        $result = $this->compileTwoElementLogicExpressions($havings);

        return "HAVING {$result}";
    }
}
