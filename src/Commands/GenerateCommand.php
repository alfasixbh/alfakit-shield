<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Commands;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use AlfaSix\AlfakitShield\Support\PermissionRegistry;
use AlfaSix\AlfakitShield\Support\PolicyGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'alfakit-shield:generate', description: 'Create the permissions and policies of the resources, pages and widgets of panels using Shield')]
class GenerateCommand extends Command
{
    protected $signature = 'alfakit-shield:generate
        {--panel= : Only this panel (default: every panel using the plugin)}
        {--option=policies_and_permissions : policies, permissions or policies_and_permissions}
        {--resource=* : Only these resources (class basenames or subjects)}
        {--force : Overwrite existing policies}';

    public function handle(PolicyGenerator $generator): int
    {
        $option = (string) $this->option('option');

        if (! in_array($option, ['policies', 'permissions', 'policies_and_permissions'], true)) {
            $this->components->error('--option must be policies, permissions or policies_and_permissions.');

            return self::FAILURE;
        }

        $panels = $this->resolvePanels();

        if ($panels === null) {
            return self::FAILURE;
        }

        $previous = alfakit()->getCurrentPanel();

        foreach ($panels as $panel) {
            alfakit()->setCurrentPanel($panel);

            try {
                /** @var ShieldPlugin $plugin */
                $plugin = ShieldPlugin::get($panel);
                $registry = $plugin->getPermissionRegistry($panel);

                $this->components->info("Panel [{$panel->getId()}]");

                if ($option !== 'policies') {
                    $this->generatePermissions($registry);
                }

                if ($option !== 'permissions') {
                    $this->generatePolicies($registry, $generator);
                }
            } finally {
                alfakit()->setCurrentPanel($previous);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return list<Panel>|null
     */
    protected function resolvePanels(): ?array
    {
        $id = $this->option('panel');

        if (is_string($id) && $id !== '') {
            if (! alfakit()->hasPanel($id)) {
                $this->components->error("Panel [{$id}] is not registered.");

                return null;
            }

            $panel = alfakit()->getPanel($id);

            if (! $panel->hasPlugin(ShieldPlugin::ID)) {
                $this->components->error("Panel [{$id}] doesn't use ShieldPlugin. Add ->plugin(ShieldPlugin::make()) to it.");

                return null;
            }

            return [$panel];
        }

        $panels = array_values(array_filter(alfakit()->getPanels(), static fn (Panel $panel): bool => $panel->hasPlugin(ShieldPlugin::ID)));

        if ($panels === []) {
            $this->components->error('No panel uses ShieldPlugin. Add ->plugin(ShieldPlugin::make()) to a panel.');

            return null;
        }

        return $panels;
    }

    protected function generatePermissions(PermissionRegistry $registry): void
    {
        $only = $this->onlyRequestedResources($registry);
        $permissions = [];

        if ($only === null) {
            $permissions = $registry->allPermissions();
        } else {
            foreach ($only as $resource) {
                array_push($permissions, ...array_keys($resource['permissions']));
            }
        }

        PermissionModels::ensurePermissions($permissions, $registry->getGuard());

        $plugin = $registry->getPlugin();

        // Without the gate, the super admin role holds every permission itself.
        if ($plugin->isSuperAdminEnabled() && ! $plugin->definesSuperAdminViaGate()) {
            $role = PermissionModels::findOrCreateRole($plugin->getSuperAdminRole(), $registry->getGuard(), getPermissionsTeamId());
            $role->givePermissionTo($permissions);
        }

        $this->components->twoColumnDetail('Permissions', (string) count($permissions));
    }

    protected function generatePolicies(PermissionRegistry $registry, PolicyGenerator $generator): void
    {
        $force = (bool) $this->option('force');

        foreach ($this->onlyRequestedResources($registry) ?? $registry->resources() as $resource) {
            $result = $generator->generate($registry, $resource['resource'], $force);

            $this->components->twoColumnDetail($result['path'], $result['status']);
        }
    }

    /**
     * @return array<string, array{resource: class-string<\AlfaSix\Alfakit\Resources\Resource>, model: string, label: string, permissions: array<string, string>}>|null
     */
    protected function onlyRequestedResources(PermissionRegistry $registry): ?array
    {
        /** @var list<string> $requested */
        $requested = (array) $this->option('resource');

        if ($requested === []) {
            return null;
        }

        return array_filter(
            $registry->resources(),
            static fn (array $resource, string $subject): bool => in_array($subject, $requested, true) || in_array(class_basename($resource['resource']), $requested, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
