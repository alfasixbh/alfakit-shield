<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Contracts;

/**
 * Implement on a resource to choose its permission prefixes. They replace the plugin's
 * `resourcePermissionPrefixes()`, so spread the defaults to add to them:
 *
 * ```php
 * public static function getPermissionPrefixes(): array
 * {
 *     return [...ShieldPlugin::DEFAULT_RESOURCE_PREFIXES, 'publish'];
 * }
 * ```
 *
 * Each prefix becomes a permission (`publish_post`) and a policy method (`publish()`).
 */
interface HasShieldPermissions
{
    /**
     * @return list<string>
     */
    public static function getPermissionPrefixes(): array;
}
