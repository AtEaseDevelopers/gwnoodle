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
        $id = Crypt::decrypt($id);
        $rules = [
            'code' => 'required|string|max:255|unique:customers,code,' . $id,
            'company' => 'required|string|max:255',
            'paymentterm' => 'required',
            'group' => 'nullable|array',
            'driver_id' => 'required|integer|exists:drivers,id',
            'phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'status' => 'required|boolean',
            'category' => 'nullable|string|max:100',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,id',
        ];
        
        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }
                
        return $rules;
    }
}
