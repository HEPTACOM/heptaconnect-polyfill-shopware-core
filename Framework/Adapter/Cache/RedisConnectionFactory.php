<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Cache;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Traits\RedisClusterProxy;
use Symfony\Component\Cache\Traits\RedisProxy;

class RedisConnectionFactory
{
    /**
     * This static variable is not reset on purpose, as we may reuse existing redis connections over multiple requests
     */
    private static array $connections = [];

    private ?string $prefix;

    /**
     * @internal
     */
    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return \Redis|\RedisArray|\RedisCluster|RedisClusterProxy|RedisProxy
     */
    public function create(string $dsn, array $options = [])
    {
        $configHash = md5(json_encode($options, \JSON_THROW_ON_ERROR));
        $key = $dsn . $configHash . $this->prefix;

        if (!isset(self::$connections[$key]) || self::$connections[$key]->isConnected() === false) {
            /** @var \Redis|\RedisArray|\RedisCluster|RedisClusterProxy|RedisProxy $redis */
            $redis = RedisAdapter::createConnection($dsn, $options);

            if ($this->prefix) {
                $redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
            }

            self::$connections[$key] = $redis;
        }

        return self::$connections[$key];
    }

    /**
     * @return \Redis|\RedisArray|\RedisCluster|RedisClusterProxy|RedisProxy
     */
    public static function createConnection(string $dsn, array $options = [])
    {
        return (new self())->create($dsn, $options);
    }
}
