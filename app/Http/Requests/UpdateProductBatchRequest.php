<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductBatch;

class UpdateProductBatchRequest extends FormRequest
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
        $rules = ProductBatch::$rules;
        
        // Make some fields optional for update
        $rules['quantity'] = 'sometimes|integer|min:0';
        
        return $rules;
    }
}