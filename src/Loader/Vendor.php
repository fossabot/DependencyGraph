<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Vendor as VendorModel,
    Exception\NoPublishedVersion,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\Str;

final class Vendor
{
    private $fulfill;
    private $load;

    public function __construct(Transport $fulfill, Package $load)
    {
        $this->fulfill = $fulfill;
        $this->load = $load;
    }

    public function __invoke(VendorModel\Name $name): VendorModel
    {
        $url = "https://packagist.org/search.json?q=$name/";
        $results = [];

        do {
            $request = new Request(
                Url::fromString($url),
                Method::get(),
                new ProtocolVersion(2, 0)
            );
            $response = ($this->fulfill)($request);
            $content = Json::decode((string) $response->body());
            $results = \array_merge($results, $content['results']);
            $url = $content['next'] ?? null;
        } while (isset($content['next']));

        $packages = [];

        foreach ($results as $result) {
            if (!Str::of($result['name'])->matches("~^$name/~")) {
                continue;
            }

            if ($result['virtual'] ?? false === true) {
                continue;
            }

            try {
                $packages[] = ($this->load)(PackageModel\Name::of($result['name']));
            } catch (NoPublishedVersion $e) {
                // do not expose the package if no tag found
            }
        }

        return new VendorModel(...$packages);
    }
}
