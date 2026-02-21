<?php declare(strict_types=1);

namespace Shopware\Core\Framework;

use Shopware\Core\Framework\DependencyInjection\CompilerPass\DefaultTransportCompilerPass;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\FeatureFlagCompilerPass;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\FilesystemConfigMigrationCompilerPass;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\FrameworkMigrationReplacementCompilerPass;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\RedisPrefixCompilerPass;
use Shopware\Core\Framework\DependencyInjection\CompilerPass\TwigLoaderConfigCompilerPass;
use Shopware\Core\Framework\DependencyInjection\FrameworkExtension;
use Shopware\Core\Framework\Migration\MigrationCompilerPass;
use Shopware\Core\Kernel;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @internal
 */
class Framework extends Bundle
{
    public function getTemplatePriority(): int
    {
        return -1;
    }

    public function getContainerExtension(): Extension
    {
        return new FrameworkExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->setParameter('locale', 'en-GB');
        $environment = (string) $container->getParameter('kernel.environment');

        $this->buildConfig($container, $environment);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/DependencyInjection/'));
        $loader->load('heptaconnect.xml');
        $loader->load('services.xml');
        $loader->load('api.xml');
        $loader->load('filesystem.xml');
        $loader->load('message-queue.xml');
        $loader->load('plugin.xml');

        // make sure to remove services behind a feature flag, before some other compiler passes may reference them, therefore the high priority
        $container->addCompilerPass(new FeatureFlagCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new MigrationCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new DefaultTransportCompilerPass());
        $container->addCompilerPass(new TwigLoaderConfigCompilerPass());
        $container->addCompilerPass(new FilesystemConfigMigrationCompilerPass());
        $container->addCompilerPass(new RedisPrefixCompilerPass());

        $container->addCompilerPass(new FrameworkMigrationReplacementCompilerPass());

        parent::build($container);
    }

    public function boot(): void
    {
        parent::boot();

        $featureFlags = $this->container->getParameter('shopware.feature.flags');
        if (!\is_array($featureFlags)) {
            throw new \RuntimeException('Container parameter "shopware.feature.flags" needs to be an array');
        }
        Feature::registerFeatures($featureFlags);

        $cacheDir = $this->container->getParameter('kernel.cache_dir');
        if (!\is_string($cacheDir)) {
            throw new \RuntimeException('Container parameter "kernel.cache_dir" needs to be a string');
        }
    }

    /**
     * @return string[]
     */
    protected function getCoreMigrationPaths(): array
    {
        return [
            __DIR__ . '/../Migration' => 'Shopware\Core\Migration',
        ];
    }

    private function buildConfig(ContainerBuilder $container, string $environment): void
    {
        $cacheDir = $container->getParameter('kernel.cache_dir');
        if (!\is_string($cacheDir)) {
            throw new \RuntimeException('Container parameter "kernel.cache_dir" needs to be a string');
        }

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = $this->getPath() . '/Resources/config';

        $configLoader->load($confDir . '/{packages}/*' . Kernel::CONFIG_EXTS, 'glob');
        $configLoader->load($confDir . '/{packages}/' . $environment . '/*' . Kernel::CONFIG_EXTS, 'glob');
        if ($environment === 'e2e') {
            $configLoader->load($confDir . '/{packages}/prod/*' . Kernel::CONFIG_EXTS, 'glob');
        }
    }
}
