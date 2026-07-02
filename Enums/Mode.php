<?php

declare(strict_types=1);

namespace DolarBipolar\Enums;

enum Mode: string
{
    private const OPTIONS_KEY_MODE = 'mode';

    case DEBUG = 'debug';
    case FORCE = 'force';
    case DEFAULT = 'default';

    public static function fromOptions(array $options): self
    {
        if (empty($options[self::OPTIONS_KEY_MODE])) {
            return self::DEFAULT;
        }

        return self::tryFrom($options[self::OPTIONS_KEY_MODE]) ?? self::DEFAULT;
    }
}
