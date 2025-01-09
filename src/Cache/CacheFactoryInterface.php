<?php

declare(strict_types=1);

namespace PhpSpellcheck\Cache;

use PhpSpellcheck\Cache\Stores\StoreInterface;

interface CacheFactoryInterface
{
    /**
     * Get a cache store instance by name.
     *
     * @param array<string, mixed> $storeArgs
     */
    public static function create(?string $name = null, array $storeArgs = []): StoreInterface;
}