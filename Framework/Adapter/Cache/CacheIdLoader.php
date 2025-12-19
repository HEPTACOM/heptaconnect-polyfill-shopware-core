<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Cache;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;

class CacheIdLoader
{
    public function load(): string
    {
        $cacheId = EnvironmentHelper::getVariable('SHOPWARE_CACHE_ID');
        if ($cacheId) {
            return (string) $cacheId;
        }

        return 'live';
    }
}
