<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;

return [

    /*
    | Where `alfakit-shield:generate` writes policy classes, and the user class their
    | methods type-hint.
    */
    'policies' => [
        'path' => app_path('Policies'),
        'namespace' => 'App\\Policies',
        'user_model' => User::class,
    ],

    /*
    | Register `{policies.namespace}\RolePolicy` (when it exists) for the role model, so the
    | role resource itself is protected by the generated `*_role` permissions.
    */
    'register_role_policy' => true,

];
