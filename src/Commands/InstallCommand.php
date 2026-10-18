<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Commands;

use AlfaSix\AlfakitShield\ShieldPlugin;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use Spatie\Permission\PermissionServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'alfakit-shield:install', description: 'Publish the permission tables and configuration, then generate permissions and policies')]
class InstallCommand extends Command
{
    protected $signature = 'alfakit-shield:install
        {--panel= : The panel to generate permissions for}
        {--teams : Turn on spatie teams, for panels with tenancy}
        {--migrate : Run the migrations}
        {--generate : Generate permissions and policies (needs the tables)}';

    public function handle(Filesystem $files): int
    {
        $spatie = dirname((string) (new ReflectionClass(PermissionServiceProvider::class))->getFileName(), 2);

        $this->components->task('Publishing the spatie/laravel-permission config', fn (): bool => $this->copyIfMissing($files, "{$spatie}/config/permission.php", config_path('permission.php')));

        $this->components->task('Publishing the permission tables migration', function () use ($files, $spatie): bool {
            if ($files->glob(database_path('migrations/*_create_permission_tables.php')) !== []) {
                return true;
            }

            return $this->copyIfMissing($files, "{$spatie}/database/migrations/create_permission_tables.php.stub", database_path('migrations/'.date('Y_m_d_His').'_create_permission_tables.php'));
        });

        $this->components->task('Publishing the Shield config', fn (): bool => $this->copyIfMissing($files, dirname(__DIR__, 2).'/config/alfakit-shield.php', config_path('alfakit-shield.php')));

        if ($this->option('teams')) {
            $this->components->task('Turning on teams in config/permission.php', function () use ($files): bool {
                $path = config_path('permission.php');

                if (! $files->exists($path)) {
                    return false;
                }

                $files->put($path, (string) preg_replace("/'teams'\s*=>\s*false/", "'teams' => true", $files->get($path)));
                config()->set('permission.teams', true);

                return true;
            });
        }

        if ($this->option('migrate')) {
            $this->call('migrate');
        }

        $panelId = $this->option('panel');
        $panels = is_string($panelId) && $panelId !== ''
            ? (alfakit()->hasPanel($panelId) ? [alfakit()->getPanel($panelId)] : [])
            : alfakit()->getPanels();

        $hasPlugin = array_filter($panels, static fn ($panel): bool => $panel->hasPlugin(ShieldPlugin::ID)) !== [];

        if (! $hasPlugin) {
            $this->components->warn('Register the plugin on your panel: ->plugin(\\AlfaSix\\AlfakitShield\\ShieldPlugin::make())');
        }

        if ($this->option('generate') && $hasPlugin) {
            $this->call('alfakit-shield:generate', array_filter(['--panel' => is_string($panelId) && $panelId !== '' ? $panelId : null]));
        }

        $this->components->bulletList([
            'Add Spatie\\Permission\\Traits\\HasRoles to your User model.',
            'Run php artisan migrate (unless you passed --migrate).',
            'Run php artisan alfakit-shield:generate to create permissions and policies.',
            'Run php artisan alfakit-shield:super-admin to give a user every permission.',
        ]);

        return self::SUCCESS;
    }

    protected function copyIfMissing(Filesystem $files, string $from, string $to): bool
    {
        if ($files->exists($to)) {
            return true;
        }

        $files->ensureDirectoryExists(dirname($to));

        return $files->copy($from, $to);
    }
}
