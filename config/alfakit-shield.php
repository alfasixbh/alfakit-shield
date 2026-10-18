<?php

declare(strict_types=1);

return [

    /*
    | Where `alfakit-shield:generate` writes policy classes, and the user class their
    | methods type-hint.
    */
    'policies' => [
        'path' => app_path('Policies'),
        'namespace' => 'App\\Policies',
        'user_model' => Illuminate\Foundation\Auth\User::class,
    ],

    /*
    | Register `{policies.namespace}\RolePolicy` (when it exists) for the role model, so the
    | role resource itself is protected by the generated `*_role` permissions.
    */
    'register_role_policy' => true,

];
