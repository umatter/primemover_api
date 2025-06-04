<?php

namespace App\Modules\Agent\Model;

enum IdentificationEnum: string
{
    case CHROME = 'chrome';
    case FIREFOX = 'firefox';
    case MULTILOGIN = 'multilogin';
    case MULTILOGINX = 'mlx';

    public function current(): string
    {
        return match ($this) {
            self::CHROME => 'chrome',
            self::FIREFOX => 'firefox',
            self::MULTILOGIN => 'multilogin',
            self::MULTILOGINX => 'mlx',
        };
    }

    public static function chrome(): string
    {
        return self::CHROME->value;
    }

    public static function firefox(): string
    {
        return self::FIREFOX->value;
    }

    public static function multilogin(): string
    {
        return self::MULTILOGIN->value;
    }

    public static function default(): string
    {
        return self::MULTILOGINX->value;
    }

    public static function multiloginx(): string
    {
        return self::MULTILOGINX->value;
    }

    public static function values(): array
    {
        return [
            self::CHROME->value,
            self::FIREFOX->value,
            self::MULTILOGIN->value,
            self::MULTILOGINX->value,
        ];
    }

    public static function getByValue(int|string $value): ?IdentificationEnum
    {
        return self::tryFrom($value);
    }
}
