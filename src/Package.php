<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Name,
    Package\Relation,
    Vendor,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Package
{
    private $name;
    private $packagist;
    private $repository;
    private $relations;

    public function __construct(
        Name $name,
        UrlInterface $packagist,
        UrlInterface $repository,
        Relation ...$relations
    ) {
        $this->name = $name;
        $this->packagist = $packagist;
        $this->repository = $repository;
        $this->relations = Set::of(Relation::class, ...$relations);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function packagist(): UrlInterface
    {
        return $this->packagist;
    }

    public function repository(): UrlInterface
    {
        return $this->repository;
    }

    /**
     * @return SetInterface<Relation>
     */
    public function relations(): SetInterface
    {
        return $this->relations;
    }

    public function dependsOn(Name $name): bool
    {
        return $this->relations->reduce(
            false,
            static function(bool $dependsOn, Relation $relation) use ($name): bool {
                return $dependsOn || $relation->name()->equals($name);
            }
        );
    }

    /**
     * Remove all the relations not from the given set
     */
    public function keep(Name ...$packages): self
    {
        $packages = Set::of(Name::class, ...$packages);

        $self = clone $this;
        $self->relations = $this->relations->filter(static function(Relation $relation) use ($packages): bool {
            return $packages->reduce(
                false,
                static function(bool $inSet, Name $package) use ($relation): bool {
                    return $inSet || $relation->name()->equals($package);
                }
            );
        });

        return $self;
    }

    public function removeRelations(): self
    {
        $self = clone $this;
        $self->relations = $this->relations->clear();

        return $self;
    }

    /**
     * Remove all the relations not from the given set of vendors
     */
    public function keepVendors(Vendor\Name ...$vendors): self
    {
        $vendors = Set::of(Vendor\Name::class, ...$vendors);

        $self = clone $this;
        $self->relations = $this->relations->filter(static function(Relation $relation) use ($vendors): bool {
            return $vendors->reduce(
                false,
                static function(bool $inSet, Vendor\Name $vendor) use ($relation): bool {
                    return $inSet || $relation->name()->vendor()->equals($vendor);
                }
            );
        });

        return $self;
    }
}
