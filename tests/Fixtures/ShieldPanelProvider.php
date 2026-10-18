<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\PanelProvider;
use AlfaSix\AlfakitShield\ShieldPlugin;

class ShieldPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('shield')
            ->path('shield')
            ->login()
            ->middleware(['web'])
            ->resources([ShieldPostResource::class, ShieldCategoryResource::class])
            ->pages([Dashboard::class, ReportsPage::class])
            ->widgets([SalesWidget::class])
            ->plugin(
                ShieldPlugin::make()
                    ->navigationGroup('Settings')
                    ->navigationSort(5)
                    ->customPermissions(['export_reports' => 'Export reports']),
            );
    }
}
