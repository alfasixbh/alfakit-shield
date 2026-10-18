<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

uses(AlfaSix\AlfakitShield\Tests\TestCase::class);

it('publishes the configuration and migrations, turns on teams and generates', function (): void {
    $directory = sys_get_temp_dir().'/alfakit-shield-install-'.uniqid();
    (new Filesystem)->ensureDirectoryExists("{$directory}/config");
    (new Filesystem)->ensureDirectoryExists("{$directory}/database/migrations");
    app()->useConfigPath("{$directory}/config");
    app()->useDatabasePath("{$directory}/database");

    try {
        $this->artisan('alfakit-shield:install', ['--panel' => 'shield', '--teams' => true, '--generate' => true])->assertSuccessful();

        expect(file_exists("{$directory}/config/permission.php"))->toBeTrue()
            ->and(file_get_contents("{$directory}/config/permission.php"))->toContain("'teams' => true")
            ->and(file_exists("{$directory}/config/alfakit-shield.php"))->toBeTrue()
            ->and(glob("{$directory}/database/migrations/*create_permission_tables.php"))->not->toBeEmpty()
            ->and(file_exists(config('alfakit-shield.policies.path').'/PostPolicy.php'))->toBeTrue();
    } finally {
        (new Filesystem)->deleteDirectory($directory);
    }
});
