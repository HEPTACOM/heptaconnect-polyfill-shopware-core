<?php declare(strict_types=1);

namespace Shopware\Core\System\SystemConfig;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Shopware\Core\Framework\Bundle;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ConfigJsonField;
use Shopware\Core\System\SystemConfig\Exception\InvalidKeyException;
use Shopware\Core\System\SystemConfig\Exception\InvalidSettingValueException;

class SystemConfigService
{
    private const SQL_GET_ID = <<<'SQL'
SELECT id
FROM system_config
WHERE configuration_key = :key
LIMIT 1
SQL;

    private const SQL_GET_VALUE = <<<'SQL'
SELECT configuration_value
FROM system_config
WHERE configuration_key = :key
LIMIT 1
SQL;

    private const SQL_SET_VALUE = <<<'SQL'
INSERT INTO system_config (id, configuration_key, configuration_value, created_at)
VALUES (:id, :key, :value, :now)
ON DUPLICATE KEY UPDATE configuration_value = :value, updated_at = :now
SQL;

    private const SQL_DELETE_VALUE = <<<'SQL'
DELETE FROM system_config WHERE id = :id
SQL;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public static function buildName(string $key): string
    {
        return 'config.' . $key;
    }

    public function get(string $key, ?string $salesChannelId = null)
    {
        $result = $this->connection->executeQuery(self::SQL_GET_VALUE, ['key' => $key])->fetchOne();

        if (!\is_string($result)) {
            return null;
        }

        $value = \json_decode($result, true, \JSON_THROW_ON_ERROR);

        return $value[ConfigJsonField::STORAGE_KEY] ?? null;
    }

    public function set(string $key, $value, ?string $salesChannelId = null): void
    {
        $id = $this->getId($key);

        if ($value === null) {
            if ($id !== null) {
                $this->connection->executeStatement(self::SQL_DELETE_VALUE, ['id' => $id]);
            }

            return;
        }

        $value = \json_encode([ConfigJsonField::STORAGE_KEY => $value], \JSON_THROW_ON_ERROR);

        $this->connection->executeStatement(self::SQL_SET_VALUE, [
            'id' => $id === null ? \Ramsey\Uuid\Uuid::uuid4()->getBytes() : $id,
            'key' => $key,
            'value' => $value,
            'now' => DateTime::nowToStorage(),
        ]);
    }

    public function delete(string $key, ?string $salesChannel = null): void
    {
        $this->set($key, null);
    }

    public function getString(string $key, ?string $salesChannelId = null): string
    {
        $value = $this->get($key, $salesChannelId);
        if (!\is_array($value)) {
            return (string) $value;
        }

        throw new InvalidSettingValueException($key, 'string', \gettype($value));
    }

    public function getInt(string $key, ?string $salesChannelId = null): int
    {
        $value = $this->get($key, $salesChannelId);
        if (!\is_array($value)) {
            return (int) $value;
        }

        throw new InvalidSettingValueException($key, 'int', \gettype($value));
    }

    public function getFloat(string $key, ?string $salesChannelId = null): float
    {
        $value = $this->get($key, $salesChannelId);
        if (!\is_array($value)) {
            return (float) $value;
        }

        throw new InvalidSettingValueException($key, 'float', \gettype($value));
    }

    public function getBool(string $key, ?string $salesChannelId = null): bool
    {
        return (bool) $this->get($key, $salesChannelId);
    }

    public function all(?string $salesChannelId = null): array
    {
        return [];
    }

    public function getDomain(string $domain, ?string $salesChannelId = null, bool $inherit = false): array
    {
        return [];
    }

    public function savePluginConfiguration(Bundle $bundle, bool $override = false): void
    {
    }

    public function saveConfig(array $config, string $prefix, bool $override): void
    {
    }

    public function deletePluginConfiguration(Bundle $bundle): void
    {
    }

    public function deleteExtensionConfiguration(string $extensionName, array $config): void
    {
    }

    public function trace(string $key, \Closure $param)
    {
        return $param();
    }

    public function getTrace(string $key): array
    {
        return [];
    }

    private function getId(string $key): ?string
    {
        $key = $this->validate($key);

        $id = $this->connection->executeQuery(self::SQL_GET_ID, ['key' => $key])->fetchOne();

        if (!\is_string($id)) {
            return null;
        }

        return $id;
    }

    private function validate(string $key): string
    {
        $key = \trim($key);

        if ($key === '') {
            throw new InvalidKeyException('key may not be empty');
        }

        return $key;
    }
}
