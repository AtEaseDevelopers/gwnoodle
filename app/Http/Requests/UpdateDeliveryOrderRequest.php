<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\Crypt;

class UpdateDeliveryOrderRequest extends FormRequest
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
        $id = $this->route('deliveryOrder');        
        $rules = [
            // 'dono' => 'required|string|max:255|unique:deliveryorders,dono,'.Crypt::decrypt($id),
            'dono' => 'required|string|max:255',
            'date' => 'required',
            'driver_id' => 'required',
            'lorry_id' => 'required',
            'vendor_id' => 'required',
            'source_id' => 'required',
            'destinate_id' => 'required',
            'item_id' => 'required',
            'weight' => 'required|numeric',
            'shipweight' => 'nullable|numeric',
            // 'billingrate' => 'required|numeric',
            // 'commissionrate' => 'required|numeric',
            // 'fees' => 'required|numeric',
            // 'tol' => 'required|numeric',
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
