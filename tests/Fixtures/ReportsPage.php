<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Pages\Page;
use AlfaSix\AlfakitShield\Traits\HasPageShield;

class ReportsPage extends Page
{
    use HasPageShield;

    protected static ?string $slug = 'reports';

    protected static ?string $navigationLabel = 'Reports';
}
