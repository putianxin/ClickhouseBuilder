<?php

namespace Ptx\ClickhouseBuilder\Query\Traits;

use Ptx\ClickhouseBuilder\Query\BaseBuilder as Builder;

trait FormatComponentCompiler
{
    /**
     * Compiles format to string to pass this string in query.
     *
     * @param Builder $builder
     * @param         $format
     *
     * @return string
     */
    public function compileFormatComponent(Builder $builder, $format): string
    {
        return "FORMAT {$format}";
    }
}
