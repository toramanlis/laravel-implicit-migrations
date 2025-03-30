<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Exception as BaseException;
use Throwable;

class Exception extends BaseException
{
    final public const CODE_NONE = 0;

    public const CODE_COL_NO_NAME = 1;

    public const CODE_COL_NO_TYPE = 2;

    public const CODE_COL_GENERIC = 3;

    public const CODE_FK_NO_MODEL = 4;

    public const CODE_FK_NO_COL = 5;

    public const CODE_IDX_NO_TYPE = 6;

    public const CODE_IDX_NO_COL = 7;

    public const CODE_RL_UNKNOWN = 8;

    public const CODE_RL_NO_PIVOT = 9;

    public const CODE_RL_NO_PARENT = 10;

    public const CODE_RL_NO_RELATED = 11;

    public const CODE_RL_NO_FOREIGN = 12;

    public const CODE_RL_NO_LOCAL = 13;

    protected const MESSAGES = [
        0 => '',
        1 => 'Cannot create a column without name: %s.???',
        2 => 'Cannot create a column without type: %s.%s',
        3 => 'There was an error while processing a column: %s.%s',
        4 => 'Cannot detect the referenced model for foreign key: %s',
        5 => 'There was an error while detecting a foreign key\'s column: %s on table: %s',
        6 => 'Invalid type for index: %s',
        7 => 'Cannot create an index without columns: %s.%s',
        8 => 'Unknown relationship type: %s',
        9 => 'Unable to detect pivot table for relationship',
        10 => 'Unable to detect parent table for relationship',
        11 => 'Unable to detect related table for relationship',
        12 => 'Unable to detect foreign key for relationship',
        13 => 'Unable to detect local key for relationship',
    ];

    public function __construct(int $code = self::CODE_NONE, array $context = [], ?Throwable $previous = null)
    {
        $code = isset(static::MESSAGES[$code]) ? $code : self::CODE_NONE;
        $message = sprintf(static::MESSAGES[$code], ...$context);

        parent::__construct($message, $code, $previous);
    }
}
