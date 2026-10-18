<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Widgets\StatsOverviewWidget;
use AlfaSix\Alfakit\Widgets\StatsOverviewWidget\Stat;
use AlfaSix\AlfakitShield\Traits\HasWidgetShield;

class SalesWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [Stat::make('Sales', 42)];
    }
}
