<?php

return [
    'singular' => 'Your Detail',
    'plural' => 'Your Details',
    'identifier_path' => 'your_details',
    'db' => [
        'model' => 'User',
        'id' => 'id',
        'column' => 'name'
    ],
    'icon' => 'user',
    'controller' => '\Evergreen\Generic\App\Http\Controllers\YourDetailsController',
    'route_type' => 'resource-page',
    'custom_blade' => "egl::modules.user.display",
    'sidebar' => []
];
