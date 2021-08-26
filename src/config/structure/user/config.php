<?php

return [
    'singular' => 'User',
    'plural' => 'Users',
    'identifier_path' => 'user',
    'db' => [
        'model' => 'User',
        'id' => 'id',
        'column' => 'name'
    ],
    'icon' => 'account_circle',
    'controller' => '\Evergreen\Generic\App\Http\Controllers\UserController',
    'route_type' => 'resource',
    'datatable' => [
        "columns" => [
            "id" => ["visible" => false, "searchable" => false],
            "name" => ["label" => "Name"],
            "email" => ["label" => "Email"]
        ],
        "href" => "user/%ID%"
    ],
    'custom_blade' => "egl::modules.user.display",
    'sidebar' => [],
    'permissions' => true
];
