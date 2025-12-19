<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Dbal;

/**
 * This class acts only as helper/common class for all dbal operations for entity definitions.
 * It knows how an association should be joined, how a parent-child inheritance should act, how translation chains work, ...
 */
class EntityDefinitionQueryHelper
{
    public const HAS_TO_MANY_JOIN = 'has_to_many_join';

    public static function escape(string $string): string
    {
        if (mb_strpos($string, '`') !== false) {
            throw new \InvalidArgumentException('Backtick not allowed in identifier');
        }

        return '`' . $string . '`';
    }
}
