<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests\Fixtures;

use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Resources\Pages\CreateRecord;
use AlfaSix\Alfakit\Resources\Pages\EditRecord;
use AlfaSix\Alfakit\Resources\Pages\ListRecords;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Tables\Columns\TextColumn;
use AlfaSix\Alfakit\Tables\Table;
use AlfaSix\Alfakit\Tests\Fixtures\Models\Post;

class ShieldPostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $slug = 'posts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('slug')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ShieldListPosts::route('/'),
            'create' => ShieldCreatePost::route('/create'),
            'edit' => ShieldEditPost::route('/{record}/edit'),
        ];
    }
}

class ShieldListPosts extends ListRecords
{
    protected static string $resource = ShieldPostResource::class;
}

class ShieldCreatePost extends CreateRecord
{
    protected static string $resource = ShieldPostResource::class;
}

class ShieldEditPost extends EditRecord
{
    protected static string $resource = ShieldPostResource::class;
}
