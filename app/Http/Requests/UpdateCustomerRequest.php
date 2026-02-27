<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Crypt;
use App\Services\EInvoiceService;

class UpdateCustomerRequest extends FormRequest
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

    protected $eInvoiceService;
    
    public function __construct(EInvoiceService $eInvoiceService)
    {
        $this->eInvoiceService = $eInvoiceService;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('customer');
        $rules = [
            'code' => 'required|string|max:255|unique:customers,code,'.Crypt::decrypt($id),
            'company' => 'required|string|max:255|string|max:255',
            'paymentterm' => 'required',
            'phone' => 'nullable|string|max:20|nullable|string|max:20',
            'address' => 'nullable|string|max:65535|nullable|string|max:65535',
            'status' => 'required',
            'created_at' => 'nullable|nullable',
            'updated_at' => 'nullable|nullable'
        ];
        
        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }
                
        return $rules;
    }
}
