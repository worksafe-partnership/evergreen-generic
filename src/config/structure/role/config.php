<?php

return [
    'singular' => 'Role',
    'plural' => 'Roles',
    'identifier_path' => 'role',
    'db' => [
        'model' => '\Evergreen\Generic\App\Role',
        'id' => 'id',
        'column' => 'name'
    ],
    'icon' => 'verified_user',
    'controller' => '\Evergreen\Generic\App\Http\Controllers\RoleController',
    'route_type' => 'resource',
    'datatable' => [
        "columns" => [
            "id" => ["visible" => false, "searchable" => false],
            "name" => ["label" => "Name"],
            "slug" => ["label" => "Slug"]
        ],
        "href" => "role/%ID%"
    ],
    'custom_blade' => "egl::modules.role.display",
    'sidebar' => [],
    'role' => 'admin',
    'permissions' => true
];
