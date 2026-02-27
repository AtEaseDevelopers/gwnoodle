<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Crypt;

class UpdateCompanyRequest extends FormRequest
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
        $id = $this->route('company');
        $rules = [
            'code' => 'required|string|max:255|string|max:255',
            'name' => 'required|string|max:255|string|max:255',
            'ssm' => 'required|string|max:255|string|max:255',
            'address1' => 'nullable|string|max:255|nullable|string|max:255',
            'address2' => 'nullable|string|max:255|nullable|string|max:255',
            'address3' => 'nullable|string|max:255|nullable|string|max:255',
            'address4' => 'nullable|string|max:255|nullable|string|max:255',
            'group_id' => 'required|unique:companies,group_id,'.Crypt::decrypt($id),
            'created_at' => 'nullable|nullable',
            'updated_at' => 'nullable|nullable'
        ];
        
        return $rules;
    }
}
