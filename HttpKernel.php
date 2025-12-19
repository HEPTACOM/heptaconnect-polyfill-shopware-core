<?php declare(strict_types=1);

namespace Shopware\Core;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Adapter\Cache\CacheIdLoader;
use Shopware\Core\Framework\Adapter\Database\MySQLFactory;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\DbalKernelPluginLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * @psalm-import-type Params from DriverManager
 */
class HttpKernel
{
    protected static ?Connection $connection = null;

    /**
     * @var class-string<Kernel>
     */
    protected static string $kernelClass = Kernel::class;

    /**
     * @var class-string<HttpCache>
     */
    protected static string $httpCacheClass = HttpCache::class;

    protected ?ClassLoader $classLoader;

    protected string $environment;

    protected bool $debug;

    protected ?string $projectDir = null;

    protected ?KernelPluginLoader $pluginLoader = null;

    protected ?KernelInterface $kernel = null;

    public function __construct(string $environment, bool $debug, ?ClassLoader $classLoader = null)
    {
        $this->classLoader = $classLoader;
        $this->environment = $environment;
        $this->debug = $debug;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true): HttpKernelResult
    {
        try {
            return $this->doHandle($request, (int) $type, (bool) $catch);
        } catch (Exception $e) {
            /** @var Params|array{url?: string} $connectionParams */
            $connectionParams = self::getConnection()->getParams();

            $message = str_replace([$connectionParams['url'] ?? null, $connectionParams['password'] ?? null, $connectionParams['user'] ?? null], '******', $e->getMessage());

            throw new \RuntimeException(sprintf('Could not connect to database. Message from SQL Server: %s', $message));
        }
    }

    public function getKernel(): KernelInterface
    {
        return $this->createKernel();
    }

    /**
     * Allows to switch the plugin loading.
     */
    public function setPluginLoader(KernelPluginLoader $pluginLoader): void
    {
        $this->pluginLoader = $pluginLoader;
    }

    public static function getConnection(): Connection
    {
        if (self::$connection) {
            return self::$connection;
        }

        self::$connection = MySQLFactory::create();

        return self::$connection;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!$this->kernel instanceof TerminableInterface) {
            return;
        }

        $this->kernel->terminate($request, $response);
    }

    private function doHandle(Request $request, int $type, bool $catch): HttpKernelResult
    {
        // create core kernel which contains bootstrapping for plugins etc.
        $kernel = $this->createKernel();
        $kernel->boot();

        $response = $kernel->handle($request, $type, $catch);

        return new HttpKernelResult($request, $response);
    }

    private function createKernel(): KernelInterface
    {
        if ($this->kernel !== null) {
            return $this->kernel;
        }

        if (InstalledVersions::isInstalled('shopware/platform')) {
            $shopwareVersion = InstalledVersions::getVersion('shopware/platform')
                . '@' . InstalledVersions::getReference('shopware/platform');
        } else {
            $shopwareVersion = InstalledVersions::getVersion('shopware/core')
                . '@' . InstalledVersions::getReference('shopware/core');
        }

        $connection = self::getConnection();

        $pluginLoader = $this->createPluginLoader($connection);

        $cacheId = (new CacheIdLoader())->load();

        return $this->kernel = new static::$kernelClass(
            $this->environment,
            $this->debug,
            $pluginLoader,
            $cacheId,
            $shopwareVersion,
            $connection,
            $this->getProjectDir()
        );
    }

    private function getProjectDir(): string
    {
        if ($this->projectDir === null) {
            if ($dir = $_ENV['PROJECT_ROOT'] ?? $_SERVER['PROJECT_ROOT'] ?? false) {
                return $this->projectDir = $dir;
            }

            $r = new \ReflectionObject($this);

            /** @var string $dir */
            $dir = $r->getFileName();
            if (!file_exists($dir)) {
                throw new \LogicException(sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name));
            }

            $dir = $rootDir = \dirname($dir);
            while (!file_exists($dir . '/vendor')) {
                if ($dir === \dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = \dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    private function createPluginLoader(Connection $connection): KernelPluginLoader
    {
        if ($this->pluginLoader) {
            return $this->pluginLoader;
        }

        if (!$this->classLoader) {
            throw new \RuntimeException('No plugin loader and no class loader provided');
        }

        $this->pluginLoader = new DbalKernelPluginLoader($this->classLoader, null, $connection);

        return $this->pluginLoader;
    }
}
