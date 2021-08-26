<?php

namespace Evergreen\Generic\App\Http\Controllers;

use Controller;
use Gate;

use Evergreen\Generic\App\Http\Requests\RoleRequest;

class RoleController extends Controller
{
    protected $identifierPath = "role";
    protected $permissionsList = [];

    public function bladeHook()
    {
        $abilities = [];
        foreach (Gate::abilities() as $ability => $closure) {
            $abilities[$ability] = str_replace(['-', '.'], " ", $ability);
        }

        $this->customValues['abilities'] = $abilities;

        $this->permissionsList = $this->buildAbilities();
        $this->customValues['abilitiesList'] = $this->permissionsList;
        $this->setUpColTickAll();
    }

    public function setUpColTickAll()
    {
        $notTickedCol = [
            'list' => 0,
            'view' => 0,
            'create' => 0,
            'edit' => 0,
            'delete' => 0,
            'restore' => 0,
            'permanentlyDelete' => 0,
            'extras' => 0,
        ];
        $noExtras = true;
        foreach ($this->permissionsList as $ability) {
            foreach ($ability['permissions'] as $key => $perm) {
                if ($perm != 'EXCLUDE') {
                    switch (true) {
                        case strpos($key, 'list-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['list']++;
                            }
                            break;
                        case strpos($key, 'view-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['view']++;
                            }
                            break;
                        case strpos($key, 'create-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['create']++;
                            }
                            break;
                        case strpos($key, 'edit-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['edit']++;
                            }
                            break;
                        case strpos($key, 'delete-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['delete']++;
                            }
                            break;
                        case strpos($key, 'restore-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['restore']++;
                            }
                            break;
                        case strpos($key, 'permanentlyDelete-') !== false:
                            if (!isset($this->record['permissions'][$key]) || $this->record['permissions'][$key] != "1") {
                                $notTickedCol['permanentlyDelete']++;
                            }
                            break;
                        case strpos($key, 'extras') !== false:
                            foreach ($perm as $k => $extra) {
                                if (!isset($this->record['permissions'][$k]) || $this->record['permissions'][$k] != "1") {
                                    $notTickedCol['extras']++;
                                }
                                $noExtras = false;
                            }
                            break;
                    }
                }
            }
        }

        if ($noExtras) {
            $notTickedCol['extras'] = 1;
        }

        $this->customValues['notTickedCol'] = $notTickedCol;
    }
    
    public function buildAbilities($config = null, $parentKey = null, $prevParentKey = null)
    {
        if (is_null($config)) {
            $config = config("structure");
        }
        $thisLevelPerms = [];

        $c = $config['config'] ?? null;
        if (!is_null($c)) {
            if (!isset($c['permissions']) ||
                (isset($c['permissions']) && $c['permissions'] === true) ||
                (is_array($c['permissions']) && isset($c['permissions']['on']) && $c['permissions']['on'] == true)) {
                $thisPerms = [
                    'title' => $c['plural'],
                    'identifier' => $c['identifier_path'],
                    'parent' => $prevParentKey ?? null,
                    'permissions' => [
                        'list-'.$c['identifier_path'] => 'List',
                        'view-'.$c['identifier_path'] => 'View',
                        'create-'.$c['identifier_path'] => 'Create',
                        'edit-'.$c['identifier_path'] => 'Edit',
                        'delete-'.$c['identifier_path'] => 'Delete',
                        'restore-'.$c['identifier_path'] => 'Restore',
                        'permanentlyDelete-'.$c['identifier_path'] => 'Permanently Delete',
                        'extras' => [],
                    ]
                ];
                if (isset($c['permissions']) && is_array($c['permissions'])) {
                    // Custom Permissions
                    if (isset($c['permissions']['extra'])) {
                        foreach ($c['permissions']['extra'] as $perm) {
                            $thisPerms['permissions']['extras'][$perm.'-'.$c['identifier_path']] = $perm;
                        }
                    }
                    // Exclude Permissions
                    if (isset($c['permissions']['exclude'])) {
                        foreach ($c['permissions']['exclude'] as $perm) {
                            $thisPerms['permissions'][$perm.'-'.$c['identifier_path']] = 'EXCLUDE';
                        }
                    }
                }
                $thisLevelPerms[] = $thisPerms;
                $prevParentKey = $parentKey;
            }
        }

        foreach ($config as $key => $c) {
            $nextLevelPerms = [];
            if ($key != 'config') {
                // We have another level
                if (count($c) == 1) {
                    //config only at the next level
                    $nextLevelPerms = $this->buildAbilities($c, $parentKey, $prevParentKey);
                } else {
                    // config + others at the next level
                    $prevParentKey = $parentKey;
                    $parentKey = $key;
                    $nextLevelPerms = $this->buildAbilities($c, $parentKey, $prevParentKey);
                }
                foreach ($nextLevelPerms as $item) {
                    $thisLevelPerms[] = $item;
                }
                $parentKey = null;
            }
        }
        return $thisLevelPerms;
    }

    public function store(RoleRequest $request)
    {
        return parent::_store(func_get_args());
    }

    public function update(RoleRequest $request, $id)
    {
        return parent::_update(func_get_args());
    }
}
