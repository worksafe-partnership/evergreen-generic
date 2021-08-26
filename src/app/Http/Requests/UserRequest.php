<?php

namespace Evergreen\Generic\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email|unique:users,email,'.$this->get('id').',id',
            'name'  => 'required',
            'password' => 'nullable|min:8|confirmed',
            'password_confirmation' => 'nullable|min:8',
        ];
    }
}
