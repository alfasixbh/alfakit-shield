<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\Alfakit\Tests\Fixtures\Models\TenantUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class ShieldTenantUser extends TenantUser
{
    use HasRoles;

    /**
     * spatie's `HasRoles` brings its own `teams()` with teams on; the panel's tenant
     * memberships stay on `team_user`.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }
}
