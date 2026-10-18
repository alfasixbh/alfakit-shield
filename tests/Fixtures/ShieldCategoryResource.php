<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Resources\Pages\ListRecords;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Tables\Columns\TextColumn;
use AlfaSix\Alfakit\Tables\Table;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Category;
use AlfaSix\AlfakitShield\Contracts\HasShieldPermissions;

/**
 * Declares its own permission prefixes, including a custom `publish`.
 */
class ShieldCategoryResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Category::class;

    protected static ?string $slug = 'categories';

    public static function getPermissionPrefixes(): array
    {
        return ['view_any', 'view', 'create', 'update', 'publish'];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }

    public static function getPages(): array
    {
        return ['index' => ShieldListCategories::route('/')];
    }
}

class ShieldListCategories extends ListRecords
{
    protected static string $resource = ShieldCategoryResource::class;
}
