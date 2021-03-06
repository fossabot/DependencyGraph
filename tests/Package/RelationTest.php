<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Relation,
    Package\Name,
    Vendor,
};
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    public function testInterface()
    {
        $relation = new Relation(
            $name = new Name(new Vendor\Name('foo'), 'bar')
        );

        $this->assertSame($name, $relation->name());
    }
}
