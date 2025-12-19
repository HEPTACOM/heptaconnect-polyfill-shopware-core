<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Rule;

abstract class Rule
{
    public const OPERATOR_GTE = '>=';

    public const OPERATOR_LTE = '<=';

    public const OPERATOR_GT = '>';

    public const OPERATOR_LT = '<';

    public const OPERATOR_EQ = '=';

    public const OPERATOR_NEQ = '!=';

    public const OPERATOR_EMPTY = 'empty';
}
