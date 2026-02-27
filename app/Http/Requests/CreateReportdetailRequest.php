<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Reportdetail;

class CreateReportdetailRequest extends FormRequest
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
        $report_id = $this->report_id;
        $rules = [
            'report_id' => 'required',
            'name' => 'required|string|max:255|regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-]*$)/u',
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'sequence' => 'required|unique:reportdetails,sequence,NULL,id,report_id,'.$report_id,
            'status' => 'required',
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
