<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use App\Models\Invoice;

class UpdateInvoiceRequest extends FormRequest
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

        // Checked here so a clash shows a normal form error instead of the
        // database's unique-index violation crashing the request.
        $rules['invoiceno'] = [
            'nullable', 'string', 'max:255',
            Rule::unique('invoices', 'invoiceno')->ignore($this->invoiceId()),
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'invoiceno.unique' => 'Invoice No :input already exists.',
        ];
    }

    /**
     * The route carries the encrypted invoice id (see InvoiceController::update()).
     */
    private function invoiceId()
    {
        try {
            return Crypt::decrypt($this->route('invoice'));
        } catch (\Throwable $e) {
            return null;
        }
    }
}
