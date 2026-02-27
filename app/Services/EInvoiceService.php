<?php

namespace App\Services;

class EInvoiceService
{
    protected $isEnabled;
    
    public function __construct()
    {
        $this->isEnabled = config('services.e_invoice.enabled', false);
    }
    
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
    
    public function requiredProductFields(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        
        return [
            'classification_code' => 'required|string|max:10',
        ];
    }
    
    public function requiredFields(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        
        return [
            'tin' => 'required|string|max:50',
            'registration_no' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'city' => 'required|string|max:255',
            'postcode' => 'required|string|max:20',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'msic' => 'nullable|string|max:50',
            'sst_registration_no' => 'nullable|string|max:50',
            'tourism_tax_registration' => 'nullable|string|max:50',
        ];
    }
}

?>