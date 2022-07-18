<?php

namespace Ptx\ClickhouseBuilder;

use PHPUnit\Framework\TestCase;
use Ptx\ClickhouseBuilder\Query\Identifier;

class IdentifierTest extends TestCase
{
    public function testToString()
    {
        $identifier = new Identifier('column');

        $this->assertEquals('column', (string) $identifier);
    }
}
