<?php

namespace Evergreen\Generic\App\Http\Controllers;

use Auth;
use Bhash;
use Evergreen\Generic\App\Http\Requests\UserRequest;
use Evergreen\Generic\App\Http\Controllers\UserController as Controller;

class YourDetailsController extends Controller
{
    protected $identifierPath = "your_details";

    public function _setData($args)
    {
        $this->record = Auth::user();
        $this->id = $this->record->id;
    }

    public function update(UserRequest $request, $id = null)
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

        return parent::_update([$r]);
    }
}
