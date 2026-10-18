<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Traits;

use AlfaSix\AlfakitShield\Shield;

/**
 * Use on a page so only users with its `view_{page}` permission can open it (and see it
 * in the navigation).
 */
trait HasPageShield
{
    public static function canAccess(): bool
    {
        return Shield::userCan(static::getShieldPermission()) && parent::canAccess();
    }

    public static function getShieldPermission(): string
    {
        return Shield::registry()->pagePermission(static::class);
    }
}
