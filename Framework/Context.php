<?php declare(strict_types=1);

namespace Shopware\Core\Framework;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Struct\StateAwareTrait;
use Shopware\Core\Framework\Struct\Struct;

class Context extends Struct
{
    use StateAwareTrait;

    public const SYSTEM_SCOPE = 'system';
    public const USER_SCOPE = 'user';
    public const CRUD_API_SCOPE = 'crud';

    public const STATE_ELASTICSEARCH_AWARE = 'elasticsearchAware';
    public const SKIP_TRIGGER_FLOW = 'skipTriggerFlow';

    /**
     * @param array<string> $languageIdChain
     * @param array<string> $ruleIds
     */
    public function __construct(
        $source = null,
        array $ruleIds = [],
        string $currencyId = Defaults::CURRENCY,
        array $languageIdChain = [Defaults::LANGUAGE_SYSTEM],
        string $versionId = Defaults::LIVE_VERSION,
        float $currencyFactor = 1.0,
        bool $considerInheritance = false,
        string $taxState = 'gross',
        $rounding = null
    ) {
    }

    public static function createDefaultContext(): self
    {
        return new self();
    }

    public function getApiAlias(): string
    {
        return 'context';
    }
}
