<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Invoice;

class CreateInvoiceRequest extends FormRequest
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
        $rules = Invoice::$rules;

        $rules['invoiceno'] = [
            'nullable', 'string', 'max:255',
            Rule::unique('invoices', 'invoiceno'),
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'invoiceno.unique' => 'Invoice No :input already exists.',
        ];
    }
}
