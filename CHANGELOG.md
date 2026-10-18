# Changelog

All notable changes to `alfasix/alfakit-shield` are documented here.

## Unreleased

- First release: `ShieldPlugin` with a role resource (tabs for resources, pages, widgets and custom permissions, per-section and global "select all"), permission discovery from the panel's resources, pages and widgets, `HasShieldPermissions` for per-resource prefixes, `HasPageShield` / `HasWidgetShield` / `HasPanelShield` traits, a super admin role through `Gate::before()` (or with every permission), an optional panel user role, tenant-scoped roles through spatie teams, and the `alfakit-shield:install`, `alfakit-shield:generate` and `alfakit-shield:super-admin` commands.
