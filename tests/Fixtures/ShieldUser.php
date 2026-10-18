<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Contracts\CanAccessPanel;
use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\AlfakitShield\Traits\HasPanelShield;
use Spatie\Permission\Traits\HasRoles;

class ShieldUser extends User implements CanAccessPanel
{
    use HasPanelShield;
    use HasRoles;

    protected $table = 'users';
}
