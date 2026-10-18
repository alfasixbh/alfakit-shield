<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Support;

use AlfaSix\Alfakit\Resources\Resource;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Writes a policy per resource model whose methods check the resource's permissions.
 */
class PolicyGenerator
{
    /** Prefixes whose policy method receives no record. */
    public const MODEL_PREFIXES = ['view_any', 'create', 'delete_any', 'restore_any', 'force_delete_any', 'reorder'];

    /** Prefixes whose policy method always receives a record. */
    public const RECORD_PREFIXES = ['view', 'update', 'delete', 'restore', 'force_delete', 'replicate'];

    public function __construct(
        protected Filesystem $files,
    ) {}

    /**
     * @param  class-string<resource>  $resource
     * @return array{path: string, status: string}
     */
    public function generate(PermissionRegistry $registry, string $resource, bool $force = false): array
    {
        $path = $this->getPath($resource::getModel());

        if ($this->files->exists($path) && ! $force) {
            return ['path' => $path, 'status' => 'skipped (exists)'];
        }

        $existed = $this->files->exists($path);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $this->render($registry, $resource));

        return ['path' => $path, 'status' => $existed ? 'overwritten' : 'created'];
    }

    /**
     * @param  class-string<resource>  $resource
     */
    public function render(PermissionRegistry $registry, string $resource): string
    {
        $model = $resource::getModel();
        $modelClass = class_basename($model);
        $methods = '';

        foreach ($registry->resourcePrefixes($resource) as $prefix) {
            $stub = match (true) {
                in_array($prefix, self::MODEL_PREFIXES, true), str_ends_with($prefix, '_any') => 'policy-method',
                in_array($prefix, self::RECORD_PREFIXES, true) => 'policy-record-method',
                default => 'policy-optional-record-method',
            };

            $methods .= $this->fill($this->stub($stub), [
                'method' => Str::camel($prefix),
                'permission' => $registry->resourcePermission($resource, $prefix),
                'modelClass' => $modelClass,
                'modelVariable' => Str::camel($modelClass),
            ]);
        }

        return $this->fill($this->stub('policy'), [
            'namespace' => $this->getNamespace(),
            'class' => "{$modelClass}Policy",
            'model' => ltrim($model, '\\'),
            'authUserModel' => ltrim((string) config('alfakit-shield.policies.user_model', \Illuminate\Foundation\Auth\User::class), '\\'),
            'methods' => rtrim($methods),
        ]);
    }

    public function getPath(string $model): string
    {
        $directory = rtrim((string) config('alfakit-shield.policies.path', app_path('Policies')), '/\\');

        return $directory.DIRECTORY_SEPARATOR.class_basename($model).'Policy.php';
    }

    public function getNamespace(): string
    {
        return trim((string) config('alfakit-shield.policies.namespace', 'App\\Policies'), '\\');
    }

    public function getPolicyClass(string $model): string
    {
        return $this->getNamespace().'\\'.class_basename($model).'Policy';
    }

    /**
     * A published stub (`stubs/alfakit-shield/{name}.stub`) wins over the package's.
     */
    protected function stub(string $name): string
    {
        $published = base_path("stubs/alfakit-shield/{$name}.stub");

        return $this->files->get($this->files->exists($published) ? $published : dirname(__DIR__, 2)."/stubs/{$name}.stub");
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function fill(string $stub, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $stub = str_replace('{{ '.$key.' }}', $value, $stub);
        }

        return $stub;
    }
}
