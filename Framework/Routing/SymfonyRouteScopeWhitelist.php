<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Routing;

class SymfonyRouteScopeWhitelist implements RouteScopeWhitelistInterface
{
    public function applies(string $controllerClass): bool
    {
        return strncmp($controllerClass, 'Symfony\\', 8) === 0;
    }
}
