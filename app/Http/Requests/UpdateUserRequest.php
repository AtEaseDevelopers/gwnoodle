<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class UpdateUserRequest extends FormRequest
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
        $id = $this->route('user');
        $rules = [
          'name'     => 'required',
          'email'    => 'required|email|unique:users,email,'.Crypt::decrypt($id),
          'password' => 'confirmed',
          'role_id'  => 'required'
        ];
        
        return $rules;
    }
}
