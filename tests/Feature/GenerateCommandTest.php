<?php

declare(strict_types=1);

use AlfaSix\AlfakitShield\ShieldPlugin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(AlfaSix\AlfakitShield\Tests\TestCase::class);

it('creates the permissions of every entity', function (): void {
    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'permissions'])->assertSuccessful();

    $names = Permission::query()->pluck('name')->all();

    expect($names)->toContain('view_any_post', 'force_delete_any_post', 'publish_category', 'view_any_role', 'view_reports_page', 'view_sales_widget', 'export_reports')
        ->and(Permission::query()->where('guard_name', '!=', 'web')->count())->toBe(0)
        ->and(is_dir((string) config('alfakit-shield.policies.path')))->toBeFalse();

    // Running it again doesn't duplicate anything.
    $count = Permission::query()->count();
    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'permissions'])->assertSuccessful();

    expect(Permission::query()->count())->toBe($count);
});

it('generates policies that check the permissions', function (): void {
    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'policies'])->assertSuccessful();

    $path = config('alfakit-shield.policies.path');
    $policy = file_get_contents("{$path}/PostPolicy.php");

    expect(Permission::query()->count())->toBe(0)
        ->and(file_exists("{$path}/CategoryPolicy.php"))->toBeTrue()
        ->and(file_exists("{$path}/RolePolicy.php"))->toBeTrue()
        ->and($policy)->toContain('namespace '.config('alfakit-shield.policies.namespace').';')
        ->and($policy)->toContain('use AlfaSix\Alfakit\Tests\Fixtures\Models\Post;')
        ->and($policy)->toContain("public function viewAny(AuthUser \$authUser): bool\n    {\n        return \$authUser->can('view_any_post');")
        ->and($policy)->toContain("public function update(AuthUser \$authUser, Post \$post): bool\n    {\n        return \$authUser->can('update_post');")
        ->and($policy)->toContain('public function forceDeleteAny(AuthUser $authUser): bool')
        ->and(file_get_contents("{$path}/CategoryPolicy.php"))->toContain('public function publish(AuthUser $authUser, ?Category $category = null): bool')
        ->and(fn () => token_get_all($policy, TOKEN_PARSE))->not->toThrow(ParseError::class);
});

it('skips existing policies unless forced', function (): void {
    $path = config('alfakit-shield.policies.path');
    @mkdir($path, 0777, true);
    file_put_contents("{$path}/PostPolicy.php", '<?php // mine');

    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'policies'])->assertSuccessful();
    expect(file_get_contents("{$path}/PostPolicy.php"))->toBe('<?php // mine');

    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'policies', '--force' => true])->assertSuccessful();
    expect(file_get_contents("{$path}/PostPolicy.php"))->toContain('class PostPolicy');
});

it('limits generation to some resources', function (): void {
    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--resource' => ['ShieldPostResource']])->assertSuccessful();

    $path = config('alfakit-shield.policies.path');

    expect(file_exists("{$path}/PostPolicy.php"))->toBeTrue()
        ->and(file_exists("{$path}/CategoryPolicy.php"))->toBeFalse()
        ->and(Permission::query()->where('name', 'view_any_post')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'view_any_category')->exists())->toBeFalse();
});

it('gives the super admin role every permission when it does not use the gate', function (): void {
    ShieldPlugin::get(alfakit()->getPanel('shield'))->superAdmin(gate: false);

    $this->artisan('alfakit-shield:generate', ['--panel' => 'shield', '--option' => 'permissions'])->assertSuccessful();

    $role = Role::findByName('super_admin', 'web');

    expect($role->permissions()->count())->toBe(Permission::query()->count());
});

it('rejects unknown panels, panels without the plugin and unknown options', function (): void {
    $this->artisan('alfakit-shield:generate', ['--panel' => 'nope'])->assertFailed();
    $this->artisan('alfakit-shield:generate', ['--panel' => 'admin'])->assertFailed();
    $this->artisan('alfakit-shield:generate', ['--option' => 'everything'])->assertFailed();
});

it('generates for every panel using the plugin by default', function (): void {
    $this->artisan('alfakit-shield:generate', ['--option' => 'permissions'])->assertSuccessful();

    expect(Permission::query()->where('name', 'view_any_post')->exists())->toBeTrue();
});
