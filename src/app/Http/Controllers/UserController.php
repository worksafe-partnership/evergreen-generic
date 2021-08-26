<?php

namespace Evergreen\Generic\App\Http\Controllers;

use Bhash;
use App\User;
use Controller;
use Illuminate\Http\Request;
use Evergreen\Generic\App\Role;
use Evergreen\Generic\App\Http\Requests\UserRequest;

class UserController extends Controller
{
    protected $identifierPath = "user";

    public function bladeHook()
    {
        $this->customValues['roles'] = Role::pluck("name", "id");
        $currentRoles = [];
        if (!is_null($this->record)) {
            $roles = $this->record['roles'];
            if (!is_null($roles)) {
                foreach ($roles as $role) {
                    $currentRoles[$role->id] = 1;
                }
            }
        }

        $this->customValues['currentRoles'] = $currentRoles;
    }

    public function store(UserRequest $request)
    {
        $hash = new Bhash();
        $request->merge(['password' => $hash->make($request->password)]);
        return parent::_store([$request->all()]);
    }

    public function created($insert, $request, $args)
    {
        $attach = [];
        foreach ($request['roles'] as $role => $checked) {
            if ($checked) {
                $attach[] = $role;
            }
        }

        $insert->roles()->attach($attach);
    }

    public function update(UserRequest $request, $id)
    {
        $hash = new Bhash();
        // check to see if there has been any change on the hash
        if ($hash->needsRehash($request->password) && $request->password != '') {
            $request->merge(['password' => $hash->make($request->password)]);
            $r = $request->all();
        } else {
            $r = $request->all();
            unset($r['password']);
            unset($r['password_confirmation']);
        }
        return parent::_update([$r, $id]);
    }

    public function updated($update, $original, $request, $args)
    {
        $attach = [];
        $detach = [];
        if (isset($request['roles'])) {
            foreach ($request['roles'] as $role => $checked) {
                $detach[] = $role;
                if ($checked) {
                    $attach[] = $role;
                }
            }

            $update->roles()->detach($detach);
            $update->roles()->attach($attach);
        }
    }

    public function search(Request $request)
    {
        $results = [];
        if (isset($request->search)) {
            $search = User::where("name", "LIKE", "%".$request->search."%")
                          ->orWhere("email", "LIKE", "%".$request->search."%")
                          ->get();
            foreach ($search as $value) {
                $results[] = [
                    'url' => '/user/'.$value->id,
                    'value' => $value->name.' ('.$value->email.')'
                ];
            }
        }
        return $results;
    }
}
