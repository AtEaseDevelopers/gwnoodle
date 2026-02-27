<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Vendor;
use Illuminate\Support\Facades\Crypt;

class UpdateVendorRequest extends FormRequest
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
        $id = $this->route('vendor');
        $rules = [
            'code' => 'required|string|max:255|unique:vendors,code,'.Crypt::decrypt($id),
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'status' => 'required',
            'remark' => 'nullable|string|max:255',
            'STR_UDF1' => 'nullable|string',
            'STR_UDF2' => 'nullable|string',
            'STR_UDF3' => 'nullable|string',
            'INT_UDF1' => 'nullable|integer',
            'INT_UDF2' => 'nullable|integer',
            'INT_UDF3' => 'nullable|integer',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
            'deleted_at' => 'nullable'
        ];
        
        return $rules;
    }
}
