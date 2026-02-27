<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Item;
use Illuminate\Support\Facades\Crypt;

class UpdateItemRequest extends FormRequest
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
        $id = $this->route('item');
        $rules = [
            'code' => 'required|string|max:255|unique:items,code,'.Crypt::decrypt($id),
            'name' => 'required|string|max:255',
            'billingrate' => 'required|numeric|min:0',
            'commissionrate' => 'required|numeric|min:0',
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
