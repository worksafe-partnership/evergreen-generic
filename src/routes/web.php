<?php
if (! function_exists('buildStructureRoutesRecursive')) {
    function buildStructureRoutesRecursive($structure, $route = "")
    {
        if (!empty($structure)) {
            $parent_route = $route;

            foreach ($structure as $key => $s) {
                if (isset($s['config']) && $key != "config") {
                    $config = $s['config'];
                    $middleware = [];
                    if (isset($config['middleware']) && is_array($config['middleware'])) {
                        $middleware = $config['middleware'];
                    }

                    if ((!isset($config['auth']) || $config['auth']) && empty($config['middleware'])) {
                        $middleware[] = 'auth';
                    }


                    Route::group(['middleware' => $middleware], function () use ($config, $s, $route) {
                        if (isset($config['route_type']) && isset($config['identifier_path'])) {
                            // set all routes for this level

                            $identifierPath = $config['identifier_path'];
                            $splitPath = array_filter(explode(".", $identifierPath));
                            $identifier = end($splitPath);

                            $permissions = [];
                            if (isset($config['permissions']) && ($config['permissions'] === true || (is_array($config['permissions']) && isset($config['permissions']['on']) && $config['permissions']['on']))) {
                                $permissions = [
                                    'create-'.$identifierPath,
                                    'view-'.$identifierPath,
                                    'edit-'.$identifierPath,
                                    'delete-'.$identifierPath,
                                    'permanentlyDelete-'.$identifierPath,
                                    'restore-'.$identifierPath,
                                    'list-'.$identifierPath
                                ];
                                $exclude = [];
                                if (is_array($config['permissions']) && isset($config['permissions']['exclude']) && is_array($config['permissions']['exclude'])) {
                                    foreach ($config['permissions']['exclude'] as $exclude) {
                                        $key = array_search($exclude, $permissions);
                                        if (isset($permissions[$key])) {
                                            unset($permissions[$key]);
                                        }
                                    }
                                }
                            }

                            if (!isset($config['exclude_routes'])) {
                                $config['exclude_routes'] = [];
                            }
                            if (!isset($config['override_route_actions'])) {
                                $config['override_route_actions'] = [];
                            }

                            if ($config['route_type'] == "resource" && isset($config['controller'])) {
                                if (!in_array("list", $config['exclude_routes'])) {
                                    $method = isset($config['override_route_actions']['_index']) ? $config['override_route_actions']['_index'] : "_index";
                                    $get = Route::get($route."/".$identifier, $config['controller'].'@'.$method);
                                    if (array_search('list-'.$identifierPath, $permissions) !== false) {
                                        $get->middleware("can:list-".$identifierPath);
                                    }
                                }

                                if (!in_array("create", $config['exclude_routes'])) {
                                    $createRoute = "/".$identifier."/create";

                                    $method = isset($config['override_route_actions']['_create']) ? $config['override_route_actions']['_create'] : "_create";
                                    $get = Route::get($route.$createRoute, $config['controller'].'@'.$method);

                                    $method = isset($config['override_route_actions']['_store']) ? $config['override_route_actions']['_store'] : "store";
                                    $post = Route::post($route.$createRoute, $config['controller'].'@'.$method);

                                    if (array_search('create-'.$identifierPath, $permissions) !== false) {
                                        $get->middleware("can:create-".$identifierPath);
                                        $post->middleware("can:create-".$identifierPath);
                                    }
                                }

                                if (!in_array("edit", $config['exclude_routes'])) {
                                    $editRoute = "/".$identifier."/{".$identifier."_id}/edit";

                                    $method = isset($config['override_route_actions']['_edit']) ? $config['override_route_actions']['_edit'] : "_edit";
                                    $get = Route::get($route.$editRoute, $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');

                                    $method = isset($config['override_route_actions']['_update']) ? $config['override_route_actions']['_update'] : "update";
                                    $post = Route::post($route.$editRoute, $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');
                                    if (array_search('edit-'.$identifierPath, $permissions) !== false) {
                                        $get->middleware("can:edit-".$identifierPath);
                                        $post->middleware("can:edit-".$identifierPath);
                                    }
                                }

                                if (!in_array("view", $config['exclude_routes'])) {
                                    $viewRoute = "/".$identifier."/{".$identifier."_id}";
                                    $method = isset($config['override_route_actions']['_view']) ? $config['override_route_actions']['_view'] : "_view";

                                    $r1 = Route::get($route.$viewRoute."/view", $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');
                                    $r2 = Route::get($route.$viewRoute, $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');

                                    if (array_search('view-'.$identifierPath, $permissions) !== false) {
                                        $r1->middleware("can:view-".$identifierPath);
                                        $r2->middleware("can:view-".$identifierPath);
                                    }
                                }

                                if (!in_array("delete", $config['exclude_routes'])) {
                                    $method = isset($config['override_route_actions']['_delete']) ? $config['override_route_actions']['_delete'] : "_delete";
                                    $rDelete = Route::get($route."/".$identifier."/{".$identifier."_id}/delete", $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');
                                    if (array_search('delete-'.$identifierPath, $permissions) !== false) {
                                        $rDelete->middleware("can:delete-".$identifierPath);
                                    }
                                }

                                if (!in_array("datatable_all", $config['exclude_routes'])) {
                                    $method = isset($config['override_route_actions']['_datatableAll']) ? $config['override_route_actions']['_datatableAll'] : "_datatableAll";
                                    $datatableAll = Route::get($route."/".$identifier.".datatable.json", $config['controller'].'@'.$method);
                                    if (array_search('list-'.$identifierPath, $permissions) !== false) {
                                        $datatableAll->middleware("can:list-".$identifierPath);
                                    }
                                }

                                $method = isset($config['override_route_actions']['_jsonAll']) ? $config['override_route_actions']['_jsonAll'] : "jsonAll";
                                $jsonAll = Route::get($route."/".$identifier.".json", $config['controller'].'@'.$method);

                                $method = isset($config['override_route_actions']['_jsonRow']) ? $config['override_route_actions']['_jsonRow'] : "jsonRow";
                                $jsonRow = Route::get($route."/".$identifier."/{".$identifier."_id}.json", $config['controller'].'@'.$method);

                                if (array_search('list-'.$identifierPath, $permissions) !== false) {
                                    $jsonAll->middleware("can:list-".$identifierPath);
                                    $jsonRow->middleware("can:list-".$identifierPath);
                                }

                                if (!in_array("permanentlyDelete", $config['exclude_routes'])) {
                                    $method = isset($config['override_route_actions']['_permanentlyDelete']) ? $config['override_route_actions']['_permanentlyDelete'] : "_permanentlyDelete";
                                    $rDelete = Route::get($route."/".$identifier."/{".$identifier."_id}/permanentlyDelete", $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');
                                    if (array_search('permanentlyDelete-'.$identifierPath, $permissions) !== false) {
                                        $rDelete->middleware("can:permanentlyDelete-".$identifierPath);
                                    }
                                }

                                if (!in_array("restore", $config['exclude_routes'])) {
                                    $method = isset($config['override_route_actions']['_restore']) ? $config['override_route_actions']['_restore'] : "_restore";
                                    $rDelete = Route::get($route."/".$identifier."/{".$identifier."_id}/restore", $config['controller'].'@'.$method)->where($identifier.'_id', '[0-9]+');
                                    if (array_search('restore-'.$identifierPath, $permissions) !== false) {
                                        $rDelete->middleware("can:restore-".$identifierPath);
                                    }
                                }

                                $viewRoute = "/".$identifier."/{".$identifier."_id}";
                            } elseif ($config['route_type'] == "resource-page") {
                                if (!in_array("view", $config['exclude_routes'])) {
                                    $viewRoute = "/".$identifier;
                                    $method = isset($config['override_route_actions']['_view']) ? $config['override_route_actions']['_view'] : "_view";
                                    $get = Route::get($route.$viewRoute, $config['controller'].'@'.$method);
                                    if (array_search('view-'.$identifierPath, $permissions) !== false) {
                                        $get->middleware("can:view-".$identifierPath);
                                    }
                                }

                                if (!in_array("edit", $config['exclude_routes'])) {
                                    $editRoute = "/".$identifier."/edit";

                                    $method = isset($config['override_route_actions']['_edit']) ? $config['override_route_actions']['_edit'] : "_edit";
                                    $get = Route::get($route.$editRoute, $config['controller'].'@'.$method);

                                    $method = isset($config['override_route_actions']['_update']) ? $config['override_route_actions']['_update'] : "update";
                                    $post = Route::post($route.$editRoute, $config['controller'].'@'.$method);

                                    if (array_search('edit-'.$identifierPath, $permissions) !== false) {
                                        $get->middleware("can:edit-".$identifierPath);
                                        $post->middleware("can:edit-".$identifierPath);
                                    }
                                }
                            } elseif ($config['route_type'] == "prefix" || $config['route_type'] == "module") {
                                $viewRoute = "/".$identifier;
                            } elseif ($config['route_type'] == "index" && isset($config['controller'])) {
                                $indexRoute = "/".$identifier;
                                $method = isset($config['override_route_actions']['_index']) ? $config['override_route_actions']['_index'] : "_index";

                                $get = Route::get($route.$indexRoute, $config['controller'].'@'.$method);
                                if (array_search('list-'.$identifierPath, $permissions) !== false) {
                                    $get->middleware("can:list-".$identifierPath);
                                }

                                if (!in_array("datatable_all", $config['exclude_routes'])) {
                                    $datatableAllRoute = "/".$identifier.".datatable.json";
                                    $method = isset($config['override_route_actions']['_datatable_all']) ? $config['override_route_actions']['datatable_all'] : "_datatableAll";
                                    $datatableAll = Route::get($route.$datatableAllRoute, $config['controller'].'@'.$method);
                                    if (array_search('list-'.$identifierPath, $permissions) !== false) {
                                        $datatableAll->middleware("can:list-".$identifierPath);
                                    }
                                }
                            }

                            // set the route to the view route, in order to allow the next route to attach onto it
                            if (isset($viewRoute)) {
                                $route .= $viewRoute;
                            }

                            // go further down the tree
                            buildStructureRoutesRecursive($s, $route);
                        }
                    });
                }

                // set the route back to the previous parent to keep the structure intact
                $route = $parent_route;
            }
        }
    }
}

Route::group(['middleware' => ['web']], function () {
    buildStructureRoutesRecursive(config("structure"));
});

Route::get('/download/{file}', function ($fileId) {
    return EGFiles::download($fileId);
});

Route::get('image/{file}', function ($fileId) {
    return EGFiles::image($fileId);
});

Route::get('', function () {
    return redirect("/user");
});

Route::get('/autocomplete/{context}/{query?}', function ($context, $query = '') {
    // TODO check validity of the model strings
    $modelStrings = explode('.', $context);
    $model = "\App\\$modelStrings[0]";
    $method = $modelStrings[1]."List";

    return ['results' => $model::{$method}($query)];
});

Route::post(config("egc.search.url"), "\Evergreen\Generic\App\Http\Controllers\UserController@search");
