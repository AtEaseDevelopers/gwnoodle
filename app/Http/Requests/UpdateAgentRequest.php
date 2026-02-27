<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Agent;
use Illuminate\Support\Facades\Crypt;

class UpdateAgentRequest extends FormRequest
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
        $id = $this->route('agent');
        $rules = [
            'employeeid' => 'nullable|string|max:20|unique:agents,employeeid,'.Crypt::decrypt($id),
            'name' => 'required|string|max:255',
            'ic' => 'nullable|string|max:20|unique:agents,ic,'.Crypt::decrypt($id),
            'phone' => 'nullable|string|max:255',
            // 'commissionrate' => 'required|numeric|min:0|max:100',
            'bankdetails1' => 'nullable|string|max:255',
            'bankdetails2' => 'nullable|string|max:255',
            'firstvaccine' => 'nullable',
            'secondvaccine' => 'nullable',
            'temperature' => 'nullable|numeric',
            'status' => 'required',
            'remark' => 'nullable|string|max:255',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
            'deleted_at' => 'nullable'
        ];

        return $rules;
    }
}
