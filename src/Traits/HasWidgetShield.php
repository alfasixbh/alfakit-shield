<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Traits;

use AlfaSix\AlfakitShield\Shield;

/**
 * Use on a widget so only users with its `view_{widget}` permission see it.
 */
trait HasWidgetShield
{
    public static function canView(): bool
    {
        return Shield::userCan(static::getShieldPermission()) && parent::canView();
    }

    public static function getShieldPermission(): string
    {
        return Shield::registry()->widgetPermission(static::class);
    }
}
