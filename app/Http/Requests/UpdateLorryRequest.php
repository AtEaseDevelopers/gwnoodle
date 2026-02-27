<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Lorry;
use Illuminate\Support\Facades\Crypt;

class UpdateLorryRequest extends FormRequest
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
        $id = $this->route('lorry');
        $rules = [
            'lorryno' => 'required|string|max:255|unique:lorrys,lorryno,'.Crypt::decrypt($id),
            'status' => 'required',
            'remark' => 'nullable|string|max:255',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
            'deleted_at' => 'nullable'
        ];
        
        return $rules;
    }
}
