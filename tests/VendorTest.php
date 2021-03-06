<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Vendor,
    Package,
    Package\Relation,
    Package\Name,
    Exception\LogicException,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInterface()
    {
        $vendor = new Vendor(
            $bar = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class)
            ),
            $baz = new Package(
                new Name(new Vendor\Name('foo'), 'baz'),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertInstanceOf(Vendor\Name::class, $vendor->name());
        $this->assertSame('foo', (string) $vendor->name());
        $this->assertInstanceOf(UrlInterface::class, $vendor->packagist());
        $this->assertSame('https://packagist.org/packages/foo/', (string) $vendor->packagist());
        $this->assertInstanceOf(\Iterator::class, $vendor);
        $this->assertSame([$bar, $baz], iterator_to_array($vendor));
    }

    public function testThrowWhenPackagesDoNotBelongToTheSameVendor()
    {
        $this->expectException(LogicException::class);

        new Vendor(
            new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class)
            ),
            new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                $this->createMock(UrlInterface::class)
            )
        );
    }

    public function testGroup()
    {
        $vendors = Vendor::group(
            $foo = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class)
            ),
            $bar = new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertInstanceOf(SetInterface::class, $vendors);
        $this->assertSame(Vendor::class, (string) $vendors->type());
        $this->assertCount(2, $vendors);
        $this->assertSame([$foo], iterator_to_array($vendors->current()));
        $vendors->next();
        $this->assertSame([$bar], iterator_to_array($vendors->current()));
    }

    public function testDependsOn()
    {
        $vendor = new Vendor(
            new Package(
                Name::of('foo/bar'),
                $this->createMock(UrlInterface::class)
            ),
            new Package(
                Name::of('foo/baz'),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('bar/baz'))
            )
        );

        $this->assertTrue($vendor->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($vendor->dependsOn(Name::of('foo/baz')));
    }
}
