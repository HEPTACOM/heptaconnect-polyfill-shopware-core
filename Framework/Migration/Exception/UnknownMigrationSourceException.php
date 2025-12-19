<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Migration\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class UnknownMigrationSourceException extends ShopwareHttpException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;

        parent::__construct(
            'No source registered for "{{ name }}"',
            ['name' => $name]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_MIGRATION_SOURCE';
    }

    public function getParameters(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
