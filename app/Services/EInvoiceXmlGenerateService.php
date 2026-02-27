<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use DateTime;
use DateTimeZone;
use DOMDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class EInvoiceXmlGenerateService
{
    protected $supplierParams;

    public function __construct()
    {
        $this->supplierParams = $this->getSupplierParams();
    }

    protected function getSupplierParams()
    {
        $addressLines = [];
        $addressLine1 = config('e-invoices.supplier_address_line_1');
        $addressLine2 = config('e-invoices.supplier_address_line_2');
        
        if ($addressLine1) $addressLines[] = $addressLine1;
        if ($addressLine2) $addressLines[] = $addressLine2;
        
        if (empty($addressLines)) {
            $addressLines = ['NA'];
        }

        // Use same fallback logic as MyInvoisService to ensure TIN consistency
        $sellerTIN = config('e-invoices.supplier_tin') ?? env('E_INVOICE_SUPPLIER_TIN');
        $sellerTIN = trim($sellerTIN, '"\' ');
        // Remove curly quotes and other quote variants to match authentication cache key
        $sellerTIN = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{201F}\x{2033}\x{2036}"\'"\s]/u', '', $sellerTIN);
        
        if (empty($sellerTIN)) {
            Log::error('E-Invoice Supplier TIN is empty!');
        }
        
        Log::info('E-Invoice Supplier TIN Configuration', [
            'sellerTIN' => $sellerTIN,
            'sellerTIN_length' => strlen($sellerTIN),
            'sellerTIN_hex' => bin2hex($sellerTIN),
            'sellerTIN_from_config' => config('e-invoices.supplier_tin'),
            'sellerTIN_from_env' => env('E_INVOICE_SUPPLIER_TIN'),
            'client_id' => config('e-invoices.client_id'),
        ]);
        
        // Get phone and company with fallback to env
        $phone = config('e-invoices.supplier_phone') ?? env('E_INVOICE_SUPPLIER_PHONE');
        $company = config('e-invoices.supplier_company') ?? env('E_INVOICE_SUPPLIER_COMPANY');
        
        if (empty($phone)) {
            Log::error('E-Invoice Supplier Phone is required but missing!');
        }
        
        if (empty($company)) {
            Log::error('E-Invoice Supplier Company Name is required but missing!');
        }
        
        // Get supplier params with fallback to env variables
        $supplierParams = [
            'additionalAccountID'       => config('e-invoices.supplier_additional_account_id') ?? env('E_INVOICE_SUPPLIER_ADDITIONAL_ACCOUNT_ID', '1'),
            'company'                   => $company ?? env('E_INVOICE_SUPPLIER_COMPANY', ''),
            'industryClassificationCode'=> config('e-invoices.supplier_industry_code') ?? env('E_INVOICE_SUPPLIER_INDUSTRY_CODE', '49230'),
            'name'                      => config('e-invoices.supplier_name') ?? env('E_INVOICE_SUPPLIER_NAME', 'Freight transport by road'),
            'sellerTIN'                 => $sellerTIN,
            'BRN'                       => config('e-invoices.supplier_brn') ?? env('E_INVOICE_SUPPLIER_BRN', 'NA'),
            'NRIC'                      => config('e-invoices.supplier_nric') ?? env('E_INVOICE_SUPPLIER_NRIC', ''),
            'SST'                       => config('e-invoices.supplier_sst') ?? env('E_INVOICE_SUPPLIER_SST', 'NA'),
            'cityName'                  => config('e-invoices.supplier_city') ?? env('E_INVOICE_SUPPLIER_CITY', ''),
            'postalZone'                => config('e-invoices.supplier_postal_zone') ?? env('E_INVOICE_SUPPLIER_POSTAL_ZONE', ''),
            'countrySubentityCode'      => config('e-invoices.supplier_country_subentity_code') ?? env('E_INVOICE_SUPPLIER_COUNTRY_SUBENTITY_CODE', ''),
            'addressLines'              => $addressLines,
            'identificationCode'        => config('e-invoices.supplier_identification_code') ?? env('E_INVOICE_SUPPLIER_IDENTIFICATION_CODE', 'MYS'),
            'listID'                    => config('e-invoices.supplier_list_id') ?? env('E_INVOICE_SUPPLIER_LIST_ID', 'ISO3166-1'),
            'listAgencyID'              => config('e-invoices.supplier_list_agency_id') ?? env('E_INVOICE_SUPPLIER_LIST_AGENCY_ID', '6'),
            'phone'                     => $phone ?? env('E_INVOICE_SUPPLIER_PHONE', ''),
            'email'                     => config('e-invoices.supplier_email') ?? env('E_INVOICE_SUPPLIER_EMAIL', '')
        ];
        
        // Validate required fields
        $requiredFields = ['company', 'sellerTIN', 'cityName', 'postalZone', 'countrySubentityCode', 'phone', 'name'];
        foreach ($requiredFields as $field) {
            if (empty($supplierParams[$field])) {
                Log::error("E-Invoice Supplier Parameter Missing: {$field}", [
                    'field' => $field,
                    'value' => $supplierParams[$field] ?? 'NOT SET',
                ]);
            }
        }
        
        Log::info('E-Invoice Supplier Parameters', [
            'company' => $company,
            'phone' => $phone ? 'SET' : 'MISSING',
            'phone_length' => $phone ? strlen($phone) : 0,
            'sellerTIN' => $sellerTIN,
            'has_company' => !empty($company),
            'has_phone' => !empty($phone),
        ]);

        return $supplierParams;
    }

    public function generateEInvoiceXml($eInvoice,$currencyRate)
    {
        $invoice = $eInvoice->invoice;
        $invoice->load(['customer', 'invoicedetail.product']);

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $invoiceElement = $xml->createElement('Invoice');
        $invoiceElement->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $invoiceElement->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $invoiceElement->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->appendChild($invoiceElement);

        $cbcId = $xml->createElement('cbc:ID', $eInvoice->sku);
        $invoiceElement->appendChild($cbcId);

        $invoiceDate = \Carbon\Carbon::parse($invoice->date);
        $invoiceCreatedAt = new DateTime($invoiceDate->format('Y-m-d H:i:s'), new DateTimeZone("UTC"));
        $invoiceCreatedAt->modify('-5 second');

        $currentTime = new DateTime("now", new DateTimeZone("UTC"));
        $currentTime->modify('-5 second');

        $timeDiff = $currentTime->getTimestamp() - $invoiceCreatedAt->getTimestamp();
        $hoursDiff = $timeDiff / 3600;
        
        if ($hoursDiff > 72 || $timeDiff < 0) {
            $dateTime = $currentTime;
        } else {
            $dateTime = $invoiceCreatedAt;
        }
        
        $currentDate = $dateTime->format("Y-m-d");

        $cbcIssueDate = $xml->createElement('cbc:IssueDate', $currentDate);
        $invoiceElement->appendChild($cbcIssueDate);
        
        $currentTimeFormatted = $dateTime->format("H:i:s") . "Z";
        $cbcIssueTime = $xml->createElement('cbc:IssueTime', $currentTimeFormatted);
        $invoiceElement->appendChild($cbcIssueTime);

        $invoiceTypeCode = $xml->createElement('cbc:InvoiceTypeCode', '01');
        $invoiceTypeCode->setAttribute('listVersionID', '1.0');
        $invoiceElement->appendChild($invoiceTypeCode);

        $currency = strtoupper($eInvoice->currency ?? 'MYR');
        $currencyCode = $xml->createElement('cbc:DocumentCurrencyCode', $currency);
        $invoiceElement->appendChild($currencyCode);

        $billingReference = $this->createBillingReference($xml, $eInvoice->sku);
        $invoiceElement->appendChild($billingReference);

        $additionalDocumentReference1 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1', 'documentType' => 'CustomsImportForm']);
        $invoiceElement->appendChild($additionalDocumentReference1);

        $additionalDocumentReference2 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'FTA', 'documentType' => 'FreeTradeAgreement', 'documentDescription' => 'Sample Description11']);
        $invoiceElement->appendChild($additionalDocumentReference2);

        $additionalDocumentReference3 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1', 'documentType' => 'K2']);
        $invoiceElement->appendChild($additionalDocumentReference3);

        $additionalDocumentReference4 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1']);
        $invoiceElement->appendChild($additionalDocumentReference4);

        $signatureElementParams = [
            'signatureId' => 'urn:oasis:names:specification:ubl:signature:Invoice',
            'signatureMethod' => 'urn:oasis:names:specification:ubl:dsig:enveloped:xades'
        ];
        $signatureElement = $this->createSignatureElement($xml, $signatureElementParams);

        $invoiceElement->appendChild($signatureElement);
        
        $accountingSupplierParty = $this->createAccountingSupplierPartyElement($xml, $this->supplierParams);
        $invoiceElement->appendChild($accountingSupplierParty);

        $customer = $invoice->customer;
        $customerEmail = is_string($customer->email) ? $customer->email : (is_array($customer->email) ? ($customer->email[0] ?? null) : null);
        
        $customerTIN = $customer->tin ?? 'NA';
        
        $isGeneralTIN = str_starts_with($customerTIN, 'EI0');
        
        if ($customerTIN !== 'NA' && !$isGeneralTIN) {
            Log::warning('E-Invoice Customer TIN Format Warning', [
                'customer_id' => $customer->id,
                'customer_company' => $customer->company,
                'customer_tin' => $customerTIN,
                'message' => 'Customer TIN does not start with EI0 (General TIN format like EI00000000010 required)',
                'expected_format' => 'EI00000000010',
            ]);
        }
        
        Log::info('E-Invoice Customer TIN Configuration', [
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_tin' => $customerTIN,
            'tin_starts_with_EI0' => $isGeneralTIN,
            'is_general_tin_format' => $isGeneralTIN,
        ]);
        
        $customerParams = [
            'TIN' => $customerTIN,
            'BRN' => $customer->registration_no ?? 'NA',
            'SST' => $customer->sst_registration_no ?? 'NA',
            'postalAddress' => [
                'cityName' => $customer->city ?? 'NA',
                'postalZone' => $customer->postcode ?? 'NA',
                'countrySubentityCode' => $customer->countrySubentityCode(),
            ],
            'addressLines' => [
                $customer->address ?? 'NA',
            ],
            'IdentificationCode' => $customer->countryIdentificationCode(),
            'listID' => 'ISO3166-1',
            'listAgencyID' => '6',
            'name' => $customer->company ?? 'NA',
            'phone' => $customer->phone ?? 'NA',
            'email' => $customerEmail ?? 'NA',
        ];
        $accountingCustomerParty = $this->createAccountingCustomerPartyElement($xml,$customerParams);
        $invoiceElement->appendChild($accountingCustomerParty);

        $delievryParams = [
            'TIN' => $customerTIN,
            'BRN' => $customer->registration_no ?? 'NA',
            'postalAddress' => [
                'cityName' => $customer->city ?? 'Shah Alam',
                'postalZone' => $customer->postcode ?? '40100',
                'countrySubentityCode' => $customer->countrySubentityCode(),
            ],
            'addressLines' => [
                $customer->address ?? 'NA'
            ],
            'identificationCode' => $customer->countryIdentificationCode(),
            'listID' => 'ISO3166-1',
            'listAgencyID' => '6',
            'name' => $customer->company ?? 'NA',
        ];        
        $deliveryElement = $this->createDeliveryElement($xml, $delievryParams);
        $invoiceElement->appendChild($deliveryElement);

        $paymentMeansElement = $this->createPaymentMeansElement($xml, '03');
        $invoiceElement->appendChild($paymentMeansElement);

        $totalPaymentAmount = 0;
        $totalTaxAmount = 0;
        $gstRate = 0.06;

        foreach ($invoice->invoicedetail as $detail) {
            if (!$detail->product) continue;
            
            $lineTotal = $detail->totalprice ?? ($detail->price * $detail->quantity);
            $taxAmount = $lineTotal * $gstRate;
            
            $totalPaymentAmount += $lineTotal;
            $totalTaxAmount += $taxAmount;
        }

        if($currencyRate != null){
            $taxExchangeRateParams = [
                'sourceCurrencyCode' => 'SGD',
                'targetCurrencyCode' => 'MYR',
                'calculationRate' => $currencyRate,
            ];

            $taxExchangeRate = $this->createTaxExchangeRate($xml, $taxExchangeRateParams);
            $invoiceElement->appendChild($taxExchangeRate);
        }

        $taxTotalParams = [
            'taxAmount' => $totalTaxAmount,
            'taxableAmount' => $totalPaymentAmount,
            'currencyID' => $currency,
            'taxSchemeID' => 'SST',
            'schemeID' => 'UN/ECE 5153',
            'schemeAgencyID' => '6',
        ];
        $taxTotal = $this->createTaxTotalElement($xml, $taxTotalParams);
        $invoiceElement->appendChild($taxTotal);

        $legalMonetaryTotalElementParams = [
            'lineExtensionAmount'  => $totalPaymentAmount,
            'taxExclusiveAmount'   => $totalPaymentAmount,
            'taxInclusiveAmount'   => $totalPaymentAmount + $totalTaxAmount,  
            'allowanceTotalAmount' => 0,
            'chargeTotalAmount'    => 0,
            'payableAmount'        => $totalPaymentAmount + $totalTaxAmount,
            'currency'             => $currency,
        ];
        $legalMonetaryTotal = $this->createLegalMonetaryTotalElement($xml, $legalMonetaryTotalElementParams);
        $invoiceElement->appendChild($legalMonetaryTotal);

        foreach ($invoice->invoicedetail as $detail) {
            if (!$detail->product) continue;

            $lineTotal = $detail->totalprice ?? ($detail->price * $detail->quantity);
            $taxAmount = $lineTotal * $gstRate;
            $unitPrice = $detail->price ?? 0;

            $codes = [];
            $classificationCode = $detail->product->classification_code ?? '003';
            
            if ($classificationCode === '004') {
                Log::warning('E-Invoice - Product using Consolidated classification code', [
                    'product_id' => $detail->product->id,
                    'product_code' => $detail->product->code,
                    'product_name' => $detail->product->name,
                    'classification_code' => $classificationCode,
                    'invoice_id' => $invoice->id,
                    'einvoice_sku' => $eInvoice->sku,
                    'message' => 'Classification code 004 (Consolidated e-Invoice) detected in individual invoice - this will cause MyInvois to treat it as consolidated',
                ]);
            }
            
            Log::info('E-Invoice - Product Classification Code', [
                'product_id' => $detail->product->id,
                'product_code' => $detail->product->code,
                'product_name' => $detail->product->name,
                'classification_code' => $classificationCode,
                'is_consolidated_code' => $classificationCode === '004',
            ]);
            
            $codes[] = ['code' => $classificationCode];

            $invoiceItemParams = [
                'id' => $detail->product->code ?? $detail->product->id,
                'invoicedQuantity' => $detail->quantity ?? 1,
                'unitCode' => 'C62',
                'lineExtensionAmount' => (string) $lineTotal,
                'currencyID' => $currency,
                'allowanceCharges' => null,
                'taxAmount' => (string) $taxAmount,
                'taxableAmount' => (string) $lineTotal,
                'taxExemptionReason' => 'exemption',
                'description' => $detail->product->name ?? ($detail->remark ?? 'Item'),
                'originCountryCode' => 'MYS',
                'itemClassificationCodes' => $codes,
                'priceAmount' => (string) $unitPrice,
                'amount' => (string) $unitPrice
            ];
        
            $invoiceLine = $this->createInvoiceLineElement($xml, $invoiceItemParams);
            $invoiceElement->appendChild($invoiceLine);
        }
        
        $xmlContent = $xml->saveXML();
        $filename = str_replace('/', '-', $eInvoice->sku);
        Storage::put('/public/lhdn/xml/e-invoice/' . $filename . '.xml', $xmlContent);
        return $xmlContent;
    }

    public function generateConsolidatedXml($selectedInvoices, $consolidated, $currencyRate)
    {
        $invoices = Invoice::whereIn('id', $selectedInvoices)->with(['customer', 'invoicedetail.product'])->get();
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $invoiceElement = $xml->createElement('Invoice');
        $invoiceElement->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $invoiceElement->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $invoiceElement->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->appendChild($invoiceElement);

        $cbcId = $xml->createElement('cbc:ID', $consolidated->sku);
        $invoiceElement->appendChild($cbcId);

        $dateTime = new DateTime("now", new DateTimeZone("UTC"));
        $dateTime->modify('-5 second');

        $currentDate = $dateTime->format("Y-m-d");
        $cbcIssueDate = $xml->createElement('cbc:IssueDate', $currentDate);
        $invoiceElement->appendChild($cbcIssueDate);
        
        $currentTimeFormatted = $dateTime->format("H:i:s") . "Z";
        $cbcIssueTime = $xml->createElement('cbc:IssueTime', $currentTimeFormatted);
        $invoiceElement->appendChild($cbcIssueTime);

        $invoiceTypeCode = $xml->createElement('cbc:InvoiceTypeCode', '01');
        $invoiceTypeCode->setAttribute('listVersionID', '1.0');
        $invoiceElement->appendChild($invoiceTypeCode);

        $currency = strtoupper($consolidated->currency ?? 'MYR');
        $currencyCode = $xml->createElement('cbc:DocumentCurrencyCode', $currency);
        $invoiceElement->appendChild($currencyCode);

        foreach ($invoices as $invoice) {
            $billingReference = $this->createBillingReference($xml, $invoice->sku);
            $invoiceElement->appendChild($billingReference);
        }

        $additionalDocumentReference1 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1', 'documentType' => 'CustomsImportForm']);
        $invoiceElement->appendChild($additionalDocumentReference1);

        $additionalDocumentReference2 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'FTA', 'documentType' => 'FreeTradeAgreement', 'documentDescription' => 'Sample Description11']);
        $invoiceElement->appendChild($additionalDocumentReference2);

        $additionalDocumentReference3 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1', 'documentType' => 'K2']);
        $invoiceElement->appendChild($additionalDocumentReference3);

        $additionalDocumentReference4 = $this->createAdditionalDocumentReference($xml, ['documentId' => 'L1']);
        $invoiceElement->appendChild($additionalDocumentReference4);

        $signatureElementParams = [
            'signatureId' => 'urn:oasis:names:specification:ubl:signature:Invoice',
            'signatureMethod' => 'urn:oasis:names:specification:ubl:dsig:enveloped:xades'
        ];
        $signatureElement = $this->createSignatureElement($xml, $signatureElementParams);

        $invoiceElement->appendChild($signatureElement);
        
        $accountingSupplierParty = $this->createAccountingSupplierPartyElement($xml, $this->supplierParams);
        $invoiceElement->appendChild($accountingSupplierParty);

        // For consolidated e-invoices, General TIN (010) EI00000000010 with BRN/NRIC = NA
        // is ONLY allowed when ALL items use Classification Code 004
        $customerParams = [
            'TIN' => 'EI00000000010',  // General TIN (010) - required for consolidated e-invoices
            'BRN' => 'NA',
            'SST' => 'NA',
            'postalAddress' => [
                'cityName' => 'NA',
                'postalZone' => 'NA',
                'countrySubentityCode' => '17',
            ],
            'addressLines' => [
                'NA',
            ],
            'IdentificationCode' => 'MYS',
            'listID' => 'ISO3166-1',
            'listAgencyID' => '6',
            'name' => 'NA',
            'phone' => 'NA',
            'email' => 'NA',
        ];
        $accountingCustomerParty = $this->createAccountingCustomerPartyElement($xml,$customerParams);
        $invoiceElement->appendChild($accountingCustomerParty);

        $delievryParams = [
            'TIN' => 'EI00000000010',
            'BRN' => 'NA',
            'postalAddress' => [
                'cityName' => 'NA',
                'postalZone' => 'NA',
                'countrySubentityCode' => '17',
            ],
            'addressLines' => [
                'NA'
            ],
            'identificationCode' => 'MYS',
            'listID' => 'ISO3166-1',
            'listAgencyID' => '6',
            'name' => 'NA',
        ];        
        $deliveryElement = $this->createDeliveryElement($xml, $delievryParams);
        $invoiceElement->appendChild($deliveryElement);

        $paymentMeansElement = $this->createPaymentMeansElement($xml, '03');
        $invoiceElement->appendChild($paymentMeansElement);
        
        $totalPaymentAmount = 0;
        $totalTaxAmount = 0;
        $gstRate = 0.06;

        foreach ($invoices as $invoice) {
            foreach ($invoice->invoicedetail as $detail) {
                if (!$detail->product) continue;
                
                $lineTotal = $detail->totalprice ?? ($detail->price * $detail->quantity);
                $taxAmount = $lineTotal * $gstRate;
                
                $totalPaymentAmount += $lineTotal;
                $totalTaxAmount += $taxAmount;
            }
        }

        if($currencyRate != null){
            $taxExchangeRateParams = [
                'sourceCurrencyCode' => 'SGD',
                'targetCurrencyCode' => 'MYR',
                'calculationRate' => $currencyRate,
            ];

            $taxExchangeRate = $this->createTaxExchangeRate($xml, $taxExchangeRateParams);
            $invoiceElement->appendChild($taxExchangeRate);
        }

        $taxTotalParams = [
            'taxAmount' => $totalTaxAmount,
            'taxableAmount' => $totalPaymentAmount,
            'currencyID' => $currency,
            'taxSchemeID' => 'SST',
            'schemeID' => 'UN/ECE 5153',
            'schemeAgencyID' => '6',
        ];
        $taxTotal = $this->createTaxTotalElement($xml, $taxTotalParams);
        $invoiceElement->appendChild($taxTotal);

        $legalMonetaryTotalElementParams = [
            'lineExtensionAmount'  => $totalPaymentAmount,
            'taxExclusiveAmount'   => $totalPaymentAmount,
            'taxInclusiveAmount'   => $totalPaymentAmount + $totalTaxAmount,  
            'allowanceTotalAmount' => 0,
            'chargeTotalAmount'    => 0,
            'payableAmount'        => $totalPaymentAmount + $totalTaxAmount,
            'currency' => $currency
        ];
        $legalMonetaryTotal = $this->createLegalMonetaryTotalElement($xml, $legalMonetaryTotalElementParams);
        $invoiceElement->appendChild($legalMonetaryTotal);

        foreach ($invoices as $invoice) {
            foreach ($invoice->invoicedetail as $detail) {
                if (!$detail->product) continue;

                $lineTotal = $detail->totalprice ?? ($detail->price * $detail->quantity);
                $taxAmount = $lineTotal * $gstRate;
                $unitPrice = $detail->price ?? 0;

                // For consolidated e-invoices, ALL items MUST use Classification Code 004
                // General TIN (010) EI00000000010 with BRN/NRIC = NA is ONLY allowed for Classification Code 004
                $codes = [];
                $codes[] = ['code' => '004'];  // Force Classification Code 004 for all items in consolidated e-invoice

                $invoiceItemParams = [
                    'id' => $invoice->invoiceno . '-' . ($detail->product->code ?? $detail->product->id),
                    'invoicedQuantity' => $detail->quantity ?? 1,
                    'unitCode' => 'C62',
                    'lineExtensionAmount' => (string) $lineTotal,
                    'currencyID' => $currency,
                    'allowanceCharges' => null,
                    'taxAmount' => (string) $taxAmount,
                    'taxableAmount' => (string) $lineTotal,
                    'taxExemptionReason' => 'exemption',
                    'description' => $detail->product->name ?? ($detail->remark ?? 'Item'),
                    'originCountryCode' => 'MYS',
                    'itemClassificationCodes' => $codes,
                    'priceAmount' => (string) $unitPrice,
                    'amount' => (string) $unitPrice
                ];
            
                $invoiceLine = $this->createInvoiceLineElement($xml, $invoiceItemParams);
                $invoiceElement->appendChild($invoiceLine);
            }
        }
        
        $xmlContent = $xml->saveXML();
        $filename = str_replace('/', '-', $consolidated->sku);
        Storage::put('/public/lhdn/xml/conso-e-invoice/' . $filename . '.xml', $xmlContent);
        return $xmlContent;
    }

    public function generateNoteXml($note,$changes,$invoiceType,$currency)
    {
        // Ensure note has sku
        if (empty($note->sku)) {
            throw new \Exception('Credit note SKU is required but not set.');
        }

        // Load relationships if not already loaded
        if (!$note->relationLoaded('einvoices')) {
            $note->load('einvoices');
        }
        if (!$note->relationLoaded('consolidatedEinvoice')) {
            $note->load('consolidatedEinvoice');
        }

        $invoice = null;
        $eInvoices = collect();
        
        if($invoiceType == 'consolidated'){
            $eInvoices = $note->consolidatedEinvoice ?? collect();
        }else{
            $eInvoices = $note->einvoices ?? collect();
            foreach($eInvoices as $eInvoice){
                // Load invoice relationship if not loaded
                if (!$eInvoice->relationLoaded('invoice')) {
                    $eInvoice->load('invoice');
                }
                if ($eInvoice->invoice) {
                    // Load customer if invoice exists
                    if (!$eInvoice->invoice->relationLoaded('customer')) {
                        $eInvoice->invoice->load('customer');
                    }
                $invoice = $eInvoice->invoice;
                    break; // Use first invoice
                }
            }
        }

        // Validate that we have e-invoices
        if ($eInvoices->isEmpty()) {
            throw new \Exception('No e-invoices found for this credit note. Please ensure e-invoices are attached.');
        }

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $invoiceElement = $xml->createElement('Invoice');
        $invoiceElement->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $invoiceElement->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $invoiceElement->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->appendChild($invoiceElement);

        // Credit Note v1.0 does NOT require signature (signature validation is disabled)
        // According to MyInvois documentation: "v1.0 of the Credit Note document type structure is maintained as v1.1, 
        // the only difference is that signature validation is disabled on this version."
        // No UBLExtensions needed for v1.0

        $cbcId = $xml->createElement('cbc:ID', $note->sku);
        $invoiceElement->appendChild($cbcId);

        $dateTime = new DateTime("now", new DateTimeZone("UTC"));
        $dateTime->modify('-5 second');

        $currentDate = $dateTime->format("Y-m-d");
        $cbcIssueDate = $xml->createElement('cbc:IssueDate', $currentDate);
        $invoiceElement->appendChild($cbcIssueDate);
        
        $currentTimeFormatted = $dateTime->format("H:i:s") . "Z";
        $cbcIssueTime = $xml->createElement('cbc:IssueTime', $currentTimeFormatted);
        $invoiceElement->appendChild($cbcIssueTime);

        // Credit Note v1.0 uses listVersionID="1.0" (signature validation disabled) - InvoiceTypeCode '02'
        // Debit Note v1.0 uses listVersionID="1.0" (signature validation disabled) - InvoiceTypeCode '03'
        // Using v1.0 for both as they don't require signature
        $invoiceTypeCodeValue = '03'; // Default to debit note
        if ($note instanceof \App\Models\CreditNote) {
            $invoiceTypeCodeValue = '02'; // Credit note
        } elseif ($note instanceof \App\Models\DebitNote) {
            $invoiceTypeCodeValue = '03'; // Debit note
        }
        
        $invoiceTypeCode = $xml->createElement('cbc:InvoiceTypeCode', $invoiceTypeCodeValue);
        $invoiceTypeCode->setAttribute('listVersionID', '1.0');
        $invoiceElement->appendChild($invoiceTypeCode);

        $totalTaxAmount = 0;
        $totalAmount = 0;

        foreach ($changes as $item) {
            $changeAmount = floatval($item['changes'] ?? 0);
            // For credit notes, ensure amounts are negative
            // For debit notes, amounts should be positive
            if ($note instanceof CreditNote && $changeAmount > 0) {
                $changeAmount = -abs($changeAmount);
            } elseif ($note instanceof DebitNote && $changeAmount < 0) {
                $changeAmount = abs($changeAmount);
            }
            $totalAmount += $changeAmount;
        }

        $currencyCode = $xml->createElement('cbc:DocumentCurrencyCode', strtoupper($currency));
        $invoiceElement->appendChild($currencyCode);

        $billingReferencesAdded = 0;
        foreach ($eInvoices as $eInvoice) {
            // Validate e-invoice has required fields
            if (empty($eInvoice->sku)) {
                Log::warning('E-Invoice missing SKU in credit note', [
                    'einvoice_id' => $eInvoice->id,
                    'credit_note_sku' => $note->sku,
                ]);
                continue;
            }
            if (empty($eInvoice->uuid)) {
                Log::warning('E-Invoice missing UUID in credit note', [
                    'einvoice_id' => $eInvoice->id,
                    'einvoice_sku' => $eInvoice->sku,
                    'credit_note_sku' => $note->sku,
                ]);
                continue;
            }
            $billingReference = $this->createInvoiceDocumentReference($xml, ['documentId' => $eInvoice->sku, 'documentUUID' => $eInvoice->uuid]);
            $invoiceElement->appendChild($billingReference);
            $billingReferencesAdded++;
        }
        
        // Ensure at least one billing reference was added
        if ($billingReferencesAdded === 0) {
            throw new \Exception('No valid billing references could be created. All e-invoices must have both SKU and UUID.');
        }

        // Credit notes don't need AdditionalDocumentReferences (L1, FTA, K2) - these are for regular invoices only
        // Removed AdditionalDocumentReferences for credit notes
        
        // Credit Note v1.0 does NOT require signature (signature validation is disabled)
        
        $accountingSupplierParty = $this->createAccountingSupplierPartyElement($xml, $this->supplierParams);
        $invoiceElement->appendChild($accountingSupplierParty);

        $customer = $invoiceType == 'consolidated' ? null : ($invoice ? $invoice->customer : null);
        if($invoiceType == 'consolidated' || !$customer){
            $customerParams = [
                'TIN' => 'EI00000000010',
                'BRN' => 'NA',
                'SST' => 'NA',
                'postalAddress' => [
                    'cityName' => 'NA',
                    'postalZone' => 'NA',
                    'countrySubentityCode' => '17',
                ],
                'addressLines' => [
                    'NA',
                ],
                'IdentificationCode' => 'MYS',
                'listID' => 'ISO3166-1',
                'listAgencyID' => '6',
                'name' => 'NA',
                'phone' => 'NA',
                'email' => 'NA',
            ];
        }else{
            $customerEmail = is_string($customer->email) ? $customer->email : (is_array($customer->email) ? ($customer->email[0] ?? null) : null);
            $customerParams = [
                'TIN' => $customer->tin ?? 'NA',
                'BRN' => $customer->registration_no ?? 'NA',
                'SST' => $customer->sst_registration_no ?? 'NA',
                'postalAddress' => [
                    'cityName' => $customer->city ?? 'NA',
                    'postalZone' => $customer->postcode ?? 'NA',
                    'countrySubentityCode' => $customer->countrySubentityCode(),
                ],
                'addressLines' => [
                    $customer->address ?? 'NA',
                ],
                'IdentificationCode' => $customer->countryIdentificationCode(),
                'listID' => 'ISO3166-1',
                'listAgencyID' => '6',
                'name' => $customer->company ?? 'NA',
                'phone' => $customer->phone ?? 'NA',
                'email' => $customerEmail ?? 'NA',
            ];
        }
        $accountingCustomerParty = $this->createAccountingCustomerPartyElement($xml,$customerParams);
        $invoiceElement->appendChild($accountingCustomerParty);

        if($invoiceType == 'consolidated' || !$customer){
            $delievryParams = [
                'TIN' => 'EI00000000010',
                'BRN' => 'NA',
                'postalAddress' => [
                    'cityName' => 'NA',
                    'postalZone' => 'NA',
                    'countrySubentityCode' => '17',
                ],
                'addressLines' => [
                    'NA',
                ],
                'identificationCode' => 'MYS',
                'listID' => 'ISO3166-1',
                'listAgencyID' => '6',
                'name' => 'NA',
            ];        
        }else{
            $delievryParams = [
                'TIN' => $customer->tin ?? 'NA',
                'BRN' => $customer->registration_no ?? 'NA',
                'postalAddress' => [
                    'cityName' => $customer->city ?? 'Shah Alam',
                    'postalZone' => $customer->postcode ?? '40100',
                    'countrySubentityCode' => $customer->countrySubentityCode(),
                ],
                'addressLines' => [
                    $customer->address ?? 'NA'
                ],
                'identificationCode' => $customer->countryIdentificationCode(),
                'listID' => 'ISO3166-1',
                'listAgencyID' => '6',
                'name' => $customer->company ?? 'NA',
            ];        
        }
        
        $deliveryElement = $this->createDeliveryElement($xml, $delievryParams);
        $invoiceElement->appendChild($deliveryElement);

        $paymentMeansElement = $this->createPaymentMeansElement($xml, '03');
        $invoiceElement->appendChild($paymentMeansElement);

        if($currency != 'myr'){
            $taxExchangeRateParams = [
                'sourceCurrencyCode' => 'SGD',
                'targetCurrencyCode' => 'MYR',
                'calculationRate' => '3.2',
            ];

            $taxExchangeRate = $this->createTaxExchangeRate($xml, $taxExchangeRateParams);
            $invoiceElement->appendChild($taxExchangeRate);
        }

        $taxTotalParams = [
            'taxAmount' => $totalTaxAmount,
            'taxableAmount' => $totalTaxAmount,
            'currencyID' => strtoupper($currency),
            'taxSchemeID' => 'SST',
            'schemeID' => 'UN/ECE 5153',
            'schemeAgencyID' => '6',
        ];
        $taxTotal = $this->createTaxTotalElement($xml, $taxTotalParams);
        $invoiceElement->appendChild($taxTotal);

        $legalMonetaryTotalElementParams = [
            'lineExtensionAmount'  => $totalAmount,
            'taxExclusiveAmount'   => $totalAmount,
            'taxInclusiveAmount'   => $totalAmount + $totalTaxAmount,  
            'allowanceTotalAmount' => 0,
            'chargeTotalAmount'    => $totalAmount,
            'payableAmount'        => $totalAmount + $totalTaxAmount,
            'currency' => strtoupper($currency)
        ];
        $legalMonetaryTotal = $this->createLegalMonetaryTotalElement($xml, $legalMonetaryTotalElementParams);
        $invoiceElement->appendChild($legalMonetaryTotal);

        $lineIndex = 1;
        foreach ($changes as $item) {
            $codes = [];
            if($invoiceType == 'consolidated'){
                $codes[] = ['code' => '004']; 
            }else{
                $codes[] = ['code' => '009']; 
            }

            // Generate a unique line ID using the note SKU and line index
            $lineId = $note->sku . '-LINE-' . $lineIndex;

            // Ensure description is not empty (DOMDocument will handle XML escaping)
            $description = !empty($item['description']) ? trim($item['description']) : ($note instanceof CreditNote ? 'Credit note adjustment' : 'Debit note adjustment');
            
            // For credit notes, amounts should be negative
            // For debit notes, amounts should be positive
            $changeAmount = floatval($item['changes'] ?? 0);
            if ($note instanceof CreditNote && $changeAmount > 0) {
                $changeAmount = -abs($changeAmount);
            } elseif ($note instanceof DebitNote && $changeAmount < 0) {
                $changeAmount = abs($changeAmount);
            }

            $invoiceItemParams = [
                'id' => $lineId,
                'invoicedQuantity' => 1,
                'unitCode' => 'C62',
                'lineExtensionAmount' => (String) $changeAmount,
                'currencyID' => strtoupper($currency),
                'allowanceCharges' => [],
                'taxAmount' => (String) 0,
                'taxableAmount' => (String) $changeAmount,
                'taxExemptionReason' => 'excemption',
                'description' => $description,
                'originCountryCode' => 'MYS',
                'itemClassificationCodes' => $codes,
                'priceAmount' => (String) $changeAmount,
                'amount' => (String) $changeAmount
            ];
        
            $invoiceLine = $this->createInvoiceLineElement($xml, $invoiceItemParams);
            $invoiceElement->appendChild($invoiceLine);
            $lineIndex++;
        }
        
        // Validate XML before saving
        // Ensure UTF-8 encoding without BOM
        $xml->encoding = 'UTF-8';
        $xmlContent = $xml->saveXML(null, LIBXML_NOEMPTYTAG);
        
        // Remove BOM if present
        if (substr($xmlContent, 0, 3) === "\xEF\xBB\xBF") {
            $xmlContent = substr($xmlContent, 3);
        }
        
        // Ensure XML declaration is present and correct
        if (strpos($xmlContent, '<?xml') !== 0) {
            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xmlContent;
        }
        
        // Check if XML is valid
        if ($xmlContent === false || empty($xmlContent)) {
            throw new \Exception('Failed to generate XML content for credit note.');
        }
        
        // Validate XML structure
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $loaded = $dom->loadXML($xmlContent, LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$loaded) {
            $errors = libxml_get_errors();
            $errorMessages = array_map(function($error) {
                return trim($error->message);
            }, $errors);
            libxml_clear_errors();
            Log::error('Credit Note XML Validation Failed', [
                'credit_note_sku' => $note->sku,
                'errors' => $errorMessages,
                'xml_preview' => substr($xmlContent, 0, 1000), // First 1000 chars for debugging
            ]);
            throw new \Exception('Generated XML is invalid: ' . implode('; ', $errorMessages));
        }
        
        // Log full XML content for debugging (save to file for easier inspection)
        $debugFilename = 'credit-note-debug-' . str_replace('/', '-', $note->sku) . '.xml';
        Storage::put('/public/lhdn/xml/debug/' . $debugFilename, $xmlContent);
        
        Log::info('Credit Note XML Generated - Full Content', [
            'credit_note_sku' => $note->sku,
            'xml_length' => strlen($xmlContent),
            'xml_full' => $xmlContent, // Log full XML for debugging
            'debug_file' => $debugFilename,
        ]);
        
        $filename = str_replace('/', '-', $note->sku);
        $type = $note instanceof CreditNote ? 'credit-note' : 'debit-note';
        Storage::put('/public/lhdn/xml/'.$type.'/' . $filename . '.xml', $xmlContent);
        
        Log::info('Credit Note XML Saved Successfully', [
            'credit_note_sku' => $note->sku,
            'filename' => $filename,
        ]);
        
        return $xmlContent;
    }

    private function createSignatureInformation($xml)
    {
        // Use createElementNS with proper namespace for SignatureInformation
        $sacNamespace = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2';
        $signatureInformation = $xml->createElementNS($sacNamespace, 'sac:SignatureInformation');

        $cbcID = $xml->createElement('cbc:ID', 'urn:oasis:names:specification:ubl:signature:1');
        $signatureInformation->appendChild($cbcID);
        
        // Use createElementNS for ReferencedSignatureID
        $sbcNamespace = 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2';
        $referencedSignatureID = $xml->createElementNS($sbcNamespace, 'sbc:ReferencedSignatureID', 'urn:oasis:names:specification:ubl:signature:Invoice');
        $signatureInformation->appendChild($referencedSignatureID);

        // Use createElementNS for Signature with proper namespace
        $dsNamespace = 'http://www.w3.org/2000/09/xmldsig#';
        $signature = $xml->createElementNS($dsNamespace, 'ds:Signature');
        // Set Id attribute to 'DocSig' as required by MyInvois specification
        $signature->setAttribute('Id', 'DocSig');
        
        $signedInfo = $this->createSignedInfo($xml);
        $signature->appendChild($signedInfo);

        // SignatureValue must be base64Binary - using valid Base64 placeholder
        // In production, this should be the actual signed hash value
        // Using a valid Base64 string (SHA256 hash encoded)
        $signatureValuePlaceholder = base64_encode(hash('sha256', 'signature_placeholder', true));
        $signatureValue = $xml->createElement('ds:SignatureValue', $signatureValuePlaceholder);
        $signatureValue->setAttribute('Id', 'DocSigValue');
        $signature->appendChild($signatureValue);

        $keyInfo = $this->createKeyInfo($xml);
        $signature->appendChild($keyInfo);

        $object = $this->createObject($xml);
        $signature->appendChild($object);

        $signatureInformation->appendChild($signature);

        return $signatureInformation;
    }

    private function createSignedInfo($xml)
    {
        $signedInfo = $xml->createElement('ds:SignedInfo');

        $canonicalizationMethod = $xml->createElement('ds:CanonicalizationMethod');
        $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $signedInfo->appendChild($canonicalizationMethod);

        $signatureMethod = $xml->createElement('ds:SignatureMethod');
        $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $signedInfo->appendChild($signatureMethod);

        $reference1 = $this->createReferenceId($xml, 'id-doc-signed-data', '');
        $signedInfo->appendChild($reference1);

        $reference2 = $this->createReferenceType($xml, 'id-xades-signed-props', 'http://www.w3.org/2000/09/xmldsig#SignatureProperties');
        $signedInfo->appendChild($reference2);

        return $signedInfo;
    }

    private function createReferenceId($xml, $id, $uri = '')
    {
        $reference = $xml->createElement('ds:Reference');
        $reference->setAttribute('Id', $id);
        $reference->setAttribute('URI', $uri);

        $transforms1 = $xml->createElement('ds:Transforms');
        $reference->appendChild($transforms1);

        $transform1_1 = $xml->createElement('ds:Transform');
        $transform1_1->setAttribute('Algorithm', 'http://www.w3.org/TR/1999/REC-xpath-19991116');
        $xpath1_1 = $xml->createElement('ds:XPath', 'not(//ancestor-or-self::ext:UBLExtensions)');
        $transform1_1->appendChild($xpath1_1);
        $transforms1->appendChild($transform1_1);

        $transform1_2 = $xml->createElement('ds:Transform');
        $transform1_2->setAttribute('Algorithm', 'http://www.w3.org/TR/1999/REC-xpath-19991116');
        $xpath1_2 = $xml->createElement('ds:XPath', 'not(//ancestor-or-self::cac:Signature)');
        $transform1_2->appendChild($xpath1_2);
        $transforms1->appendChild($transform1_2);

        $transform1_3 = $xml->createElement('ds:Transform');
        $transform1_3->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $transforms1->appendChild($transform1_3);

        $digestMethod = $xml->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        // Use valid Base64 placeholder (SHA256 of empty string = valid Base64)
        $digestValue = $xml->createElement('ds:DigestValue', base64_encode(hash('sha256', '', true)));
        $reference->appendChild($digestMethod);
        $reference->appendChild($digestValue);

        return $reference;
    }

    private function createReferenceType($xml, $type, $uri)
    {
        $reference = $xml->createElement('ds:Reference');
        $reference->setAttribute('Type', $type);
        $reference->setAttribute('URI', $uri);

        $digestMethod = $xml->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        // Use valid Base64 placeholder (SHA256 of empty string = valid Base64)
        $digestValue = $xml->createElement('ds:DigestValue', base64_encode(hash('sha256', '', true)));
        $reference->appendChild($digestMethod);
        $reference->appendChild($digestValue);

        return $reference;
    }

    private function createKeyInfo($xml)
    {
        $keyInfo = $xml->createElement('ds:KeyInfo');
        $x509Data = $xml->createElement('ds:X509Data');
        // Use valid Base64 placeholder (longer valid Base64 string)
        // This is a placeholder - in production, use actual certificate
        $x509Certificate = $xml->createElement('ds:X509Certificate', base64_encode(str_repeat('0', 200))); // Valid Base64 placeholder
        $x509Data->appendChild($x509Certificate);
        $keyInfo->appendChild($x509Data);

        return $keyInfo;
    }

    private function createObject($xml)
    {
        $object = $xml->createElement('ds:Object');

        $qualifyingProperties = $xml->createElement('xades:QualifyingProperties');
        $qualifyingProperties->setAttribute('xmlns:xades', 'http://uri.etsi.org/01903/v1.3.2#');
        $qualifyingProperties->setAttribute('Target', 'signature');

        $signedProperties = $xml->createElement('xades:SignedProperties');
        $signedProperties->setAttribute('Id', 'id-xades-signed-props');

        $signedSignatureProperties = $xml->createElement('xades:SignedSignatureProperties');

        $signingTime = $xml->createElement('xades:SigningTime', '2024-07-23T16:31:06Z');
        $signedSignatureProperties->appendChild($signingTime);

        $signingCertificate = $xml->createElement('xades:SigningCertificate');
        $cert = $xml->createElement('xades:Cert');

        $certDigest = $xml->createElement('xades:CertDigest');
        $digestMethod = $xml->createElement('ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $digestValue = $xml->createElement('ds:DigestValue', 'KKBSTyiPKGkGl1AFqcPziKCEIDYGtnYUTQN4ukO7G40=');

        $certDigest->appendChild($digestMethod);
        $certDigest->appendChild($digestValue);

        $cert->appendChild($certDigest);

        $issuerSerial = $xml->createElement('xades:IssuerSerial');
        $x509IssuerName = $xml->createElement('ds:X509IssuerName', 'CN=Trial LHDNM Sub CA V1, OU=Terms of use at http://www.posdigicert.com.my, O=LHDNM, C=MY');
        $x509SerialNumber = $xml->createElement('ds:X509SerialNumber', '162880276254639189035871514749820882117');

        $issuerSerial->appendChild($x509IssuerName);
        $issuerSerial->appendChild($x509SerialNumber);

        $cert->appendChild($issuerSerial);

        $signingCertificate->appendChild($cert);

        $signedSignatureProperties->appendChild($signingCertificate);

        $signedProperties->appendChild($signedSignatureProperties);

        $qualifyingProperties->appendChild($signedProperties);

        $object->appendChild($qualifyingProperties);

        return $object;
    }

    private function createUBLExtensions($xml)
    {
        // Create UBLExtensions element with proper namespace
        // Use local name only - namespace prefix is handled by the namespace declaration on root element
        $extNamespace = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';
        $ublExtensions = $xml->createElementNS($extNamespace, 'UBLExtensions');
        
        $UBLExtension = $xml->createElementNS($extNamespace, 'UBLExtension');
        $ublExtensions->appendChild($UBLExtension);

        $ExtensionURI = $xml->createElementNS($extNamespace, 'ExtensionURI', 'urn:oasis:names:specification:ubl:dsig:enveloped:xades');
        $UBLExtension->appendChild($ExtensionURI);

        $ExtensionContent = $xml->createElementNS($extNamespace, 'ExtensionContent');
        $UBLExtension->appendChild($ExtensionContent);

        $ublDocumentSignatures = $xml->createElementNS(
            'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2', 
            'sig:UBLDocumentSignatures'
        );
        $ublDocumentSignatures->setAttribute('xmlns:sig', 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2');
        $ublDocumentSignatures->setAttribute('xmlns:sac', 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2');
        $ublDocumentSignatures->setAttribute('xmlns:sbc', 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2');
        $ExtensionContent->appendChild($ublDocumentSignatures);

        $signatureInformation = $this->createSignatureInformation($xml);
        $ublDocumentSignatures->appendChild($signatureInformation);
        
        // Verify signature was created with correct Id
        $signatureElement = $signatureInformation->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->item(0);
        if ($signatureElement && $signatureElement->getAttribute('Id') === 'DocSig') {
            Log::info('Credit Note - Signature element created successfully', [
                'signature_id' => $signatureElement->getAttribute('Id'),
                'signature_namespace' => $signatureElement->namespaceURI,
            ]);
        } else {
            Log::warning('Credit Note - Signature element not found or missing Id attribute', [
                'signature_found' => $signatureElement !== null,
                'signature_id' => $signatureElement ? $signatureElement->getAttribute('Id') : 'NOT FOUND',
            ]);
        }

        return $ublExtensions;
    }
    
    public function createInvoicePeriod($xml, $params)
    {
        //three of these optional
        $invoicePeriod = $xml->createElement('cac:InvoicePeriod');
        
        $startDateElement = $xml->createElement('cbc:StartDate', $params['startDate']);
        $invoicePeriod->appendChild($startDateElement);
        
        $endDateElement = $xml->createElement('cbc:EndDate', $params['endDate']);
        $invoicePeriod->appendChild($endDateElement);
        
        $descriptionElement = $xml->createElement('cbc:Description', $params['description']);
        $invoicePeriod->appendChild($descriptionElement);
        
        return $invoicePeriod;
    }

    public function createBillingReference($xml, $documentId)
    {
        $billingReference = $xml->createElement('cac:BillingReference');
        
        $additionalDocumentReference = $xml->createElement('cac:AdditionalDocumentReference');
        
        $idElement = $xml->createElement('cbc:ID', $documentId);
        $additionalDocumentReference->appendChild($idElement);
        
        $billingReference->appendChild($additionalDocumentReference);
        
        return $billingReference;
    }

    public function createInvoiceDocumentReference($xml, $params)
    {
        $billingReference = $xml->createElement('cac:BillingReference');
        
        $additionalDocumentReference = $xml->createElement('cac:InvoiceDocumentReference');
        
        $idElement = $xml->createElement('cbc:ID', $params['documentId']);
        $additionalDocumentReference->appendChild($idElement);

        $uuidElement = $xml->createElement('cbc:UUID', $params['documentUUID']);
        $additionalDocumentReference->appendChild($uuidElement);
        
        $billingReference->appendChild($additionalDocumentReference);
        
        return $billingReference;
    }

    public function createAdditionalDocumentReference($xml, $params)
    {
        $additionalDocumentReference = $xml->createElement('cac:AdditionalDocumentReference');
        
        $idElement = $xml->createElement('cbc:ID', $params['documentId']);
        $additionalDocumentReference->appendChild($idElement);
        
        if (isset($params['documentType'])) {
            $documentTypeElement = $xml->createElement('cbc:DocumentType', $params['documentType']);
            $additionalDocumentReference->appendChild($documentTypeElement);
        }
        
        if (isset($params['documentDescription'])) {
            $documentDescriptionElement = $xml->createElement('cbc:DocumentDescription', $params['documentDescription']);
            $additionalDocumentReference->appendChild($documentDescriptionElement);
        }
        
        return $additionalDocumentReference;
    }

    public function createSignatureElement($xml, $params)
    {
        $signatureElement = $xml->createElement('cac:Signature');
        
        $idElement = $xml->createElement('cbc:ID', $params['signatureId']);
        $signatureElement->appendChild($idElement);
        
        $signatureMethodElement = $xml->createElement('cbc:SignatureMethod', $params['signatureMethod']);
        $signatureElement->appendChild($signatureMethodElement);
        
        return $signatureElement;
    }

    /**
     * Create the <cac:AccountingSupplierParty> XML element based on the provided parameters.
     *
     * This function builds a complex XML structure representing the accounting supplier party,
     * including the identification codes (TIN, NRIC, SST), postal address, legal entity details,
     * and contact information based on the input parameters.
     * The resulting <cac:AccountingSupplierParty> element is then returned as a DOMElement.
     *
     * @param DOMDocument $xml    The base XML DOMDocument object to create elements from.
     * @param array $params       An associative array containing the following supplier details:
     *                            - 'additionalAccountID' (string): The AdditionalAccountID value to be used in the XML.
     *                            - 'company' (string): The name of the company (used in multiple places).
     *                            - 'industryClassificationCode' (string): The industry classification code.
     *                            - 'name' (string): The name associated with the industry classification.
     *                            - 'sellerTIN' (string): The Supplier's Tax Identification Number (TIN).
     *                            - 'NRIC' (string): The Supplier's National Registration Identity Card (NRIC) number.
     *                            - 'SST' (string): The Supplier's Sales and Service Tax (SST) number.
     *                            - 'cityName' (string): The city name for the supplier's address.
     *                            - 'postalZone' (string): The postal code for the supplier's address.
     *                            - 'countrySubentityCode' (string): The state or sub-entity code for the address.
     *                            - 'addressLines' (array): A list of address lines (strings) for the supplier's address.
     *                            - 'identificationCode' (string): The identification code for the country of the supplier.
     *                            - 'listID' (string): The ID for the list that the country code belongs to.
     *                            - 'listAgencyID' (string): The ID of the agency maintaining the country list.
     *                            - 'phone' (string): The contact phone number for the supplier.
     *                            - 'email' (string): The contact email address for the supplier.
     *
     * @return \DOMNode The constructed <cac:AccountingSupplierParty> XML element.
     */
    public function createAccountingSupplierPartyElement($xml, $params)
    {
        $accountingSupplierParty = $xml->createElement('cac:AccountingSupplierParty');

        $additionalAccountID = $xml->createElement('cbc:AdditionalAccountID', $params['additionalAccountID']);
        $additionalAccountID->setAttribute('schemeAgencyName', $params['company']);
        $accountingSupplierParty->appendChild($additionalAccountID);

        $party = $xml->createElement('cac:Party');
        $accountingSupplierParty->appendChild($party);

        $industryClassificationCode = $xml->createElement('cbc:IndustryClassificationCode', $params['industryClassificationCode']);
        // Ensure name attribute is not empty (required by MyInvois)
        $nameValue = !empty($params['name']) ? $params['name'] : 'Freight transport by road';
        $industryClassificationCode->setAttribute('name', $nameValue);
        $party->appendChild($industryClassificationCode);

        Log::info('E-Invoice XML - Supplier TIN in XML', [
            'sellerTIN' => $params['sellerTIN'],
            'sellerTIN_length' => strlen($params['sellerTIN']),
        ]);
        
        // Validate TIN is not empty
        if (empty($params['sellerTIN'])) {
            Log::error('E-Invoice XML - Supplier TIN is empty in XML generation!', [
                'params' => $params,
            ]);
            throw new \Exception('Supplier TIN is required but is empty. Please configure E_INVOICE_SUPPLIER_TIN in your .env file.');
        }
        
        $partyIdentifications = [
            ['schemeID' => 'TIN', 'ID' => $params['sellerTIN']],
        ];

        if (isset($params['NRIC']) && !empty($params['NRIC'])) {
            $partyIdentifications[] = ['schemeID' => 'NRIC', 'ID' => $params['NRIC']];
            Log::info('E-Invoice XML - Supplier using NRIC', ['nric' => $params['NRIC']]);
        } elseif (isset($params['BRN']) && !empty($params['BRN'])) {
            $partyIdentifications[] = ['schemeID' => 'BRN', 'ID' => $params['BRN']];
            Log::info('E-Invoice XML - Supplier using BRN (NRIC empty)', ['brn' => $params['BRN']]);
        }

        $partyIdentifications[] = ['schemeID' => 'SST', 'ID' => $params['SST']];

        foreach ($partyIdentifications as $identification) {
            $partyIdentification = $xml->createElement('cac:PartyIdentification');
            $idElement = $xml->createElement('cbc:ID', $identification['ID']);
            $idElement->setAttribute('schemeID', $identification['schemeID']);
            $partyIdentification->appendChild($idElement);
            $party->appendChild($partyIdentification);
        }

        $postalAddress = $xml->createElement('cac:PostalAddress');
        // Ensure required address fields are not empty
        $cityName = $xml->createElement('cbc:CityName', !empty($params['cityName']) ? $params['cityName'] : 'NA');
        $postalZone = $xml->createElement('cbc:PostalZone', !empty($params['postalZone']) ? $params['postalZone'] : 'NA');
        $countrySubentityCode = $xml->createElement('cbc:CountrySubentityCode', !empty($params['countrySubentityCode']) ? $params['countrySubentityCode'] : 'NA');
        $postalAddress->appendChild($cityName);
        $postalAddress->appendChild($postalZone);
        $postalAddress->appendChild($countrySubentityCode);
        
        foreach ($params['addressLines'] as $line) {
            $addressLine = $xml->createElement('cac:AddressLine');
            $lineElement = $xml->createElement('cbc:Line', $line);
            $addressLine->appendChild($lineElement);
            $postalAddress->appendChild($addressLine);
        }

        $country = $xml->createElement('cac:Country');
        $identificationCode = $xml->createElement('cbc:IdentificationCode', $params['identificationCode']);
        $identificationCode->setAttribute('listID', $params['listID']);
        $identificationCode->setAttribute('listAgencyID', $params['listAgencyID']);
        $country->appendChild($identificationCode);
        $postalAddress->appendChild($country);

        $party->appendChild($postalAddress);

        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        // Ensure company name is not empty (required by MyInvois)
        $companyName = !empty($params['company']) ? $params['company'] : 'NA';
        $registrationName = $xml->createElement('cbc:RegistrationName', $companyName);
        $partyLegalEntity->appendChild($registrationName);
        $party->appendChild($partyLegalEntity);

        $contact = $xml->createElement('cac:Contact');
        // Ensure phone is not empty (required by MyInvois)
        $phoneValue = !empty($params['phone']) ? $params['phone'] : 'NA';
        $telephone = $xml->createElement('cbc:Telephone', $phoneValue);
        $emailValue = !empty($params['email']) ? $params['email'] : 'NA';
        $email = $xml->createElement('cbc:ElectronicMail', $emailValue);
        $contact->appendChild($telephone);
        $contact->appendChild($email);
        $party->appendChild($contact);

        return $accountingSupplierParty;
    }

    /**
     * Create the <cac:AccountingCustomerParty> XML element based on the provided parameters.
     *
     * This function constructs a complex XML structure for the accounting customer party,
     * including multiple identification codes (e.g., TIN, BRN, SST), postal address with address lines,
     * legal entity registration name, and contact information.
     *
     * @param DOMDocument $xml    The base XML DOMDocument object used to create the XML elements.
     * @param array $params       An associative array containing the customer's details:
     *                            - 'TIN' (string): Tax Identification Number.
     *                            - 'BRN' (string): Business Registration Number.
     *                            - 'SST' (string): Sales and Service Tax number.
     *                            - 'postalAddress' (array): An array containing:
     *                                - 'cityName' (string): City name.
     *                                - 'postalZone' (string): Postal code.
     *                                - 'countrySubentityCode' (string): State or region code.
     *                            - 'addressLines' (array): A list of address lines (each a string).
     *                            - 'IdentificationCode' (string): Country identification code.
     *                            - 'listID' (string): Country list ID.
     *                            - 'listAgencyID' (string): Agency ID for the list.
     *                            - 'name' (string): Legal name of the customer.
     *                            - 'phone' (string): Contact telephone number.
     *                            - 'email' (string): Contact email address.
     *
     * @return \DOMNode The constructed <cac:AccountingCustomerParty> XML element.
     */
    public function createAccountingCustomerPartyElement($xml, $params)
    {
        $accountingCustomerParty = $xml->createElement('cac:AccountingCustomerParty');

        $party = $xml->createElement('cac:Party');
        $accountingCustomerParty->appendChild($party);

        $partyIdentifications = [
            ['schemeID' => 'TIN', 'ID' => $params['TIN']],
            ['schemeID' => 'BRN', 'ID' => $params['BRN']],
            ['schemeID' => 'SST', 'ID' => $params['SST']],
        ];

        foreach ($partyIdentifications as $identification) {
            $partyIdentification = $xml->createElement('cac:PartyIdentification');
            $idElement = $xml->createElement('cbc:ID', $identification['ID']);
            $idElement->setAttribute('schemeID', $identification['schemeID']);
            $partyIdentification->appendChild($idElement);
            $party->appendChild($partyIdentification);
        }

        $address = $params['postalAddress'];
        $postalAddress = $xml->createElement('cac:PostalAddress');
        $cityName = $xml->createElement('cbc:CityName', $address['cityName']);
        $postalZone = $xml->createElement('cbc:PostalZone', $address['postalZone']);
        $countrySubentityCode = $xml->createElement('cbc:CountrySubentityCode', $address['countrySubentityCode']);
        $postalAddress->appendChild($cityName);
        $postalAddress->appendChild($postalZone);
        $postalAddress->appendChild($countrySubentityCode);

        foreach ($params['addressLines'] as $line) {
            $addressLine = $xml->createElement('cac:AddressLine');
            $lineElement = $xml->createElement('cbc:Line', $line);
            $addressLine->appendChild($lineElement);
            $postalAddress->appendChild($addressLine);
        }

        $country = $xml->createElement('cac:Country');
        $identificationCode = $xml->createElement('cbc:IdentificationCode', $params['IdentificationCode']);
        $identificationCode->setAttribute('listID', $params['listID']);
        $identificationCode->setAttribute('listAgencyID', $params['listAgencyID']);
        $country->appendChild($identificationCode);
        $postalAddress->appendChild($country);

        $party->appendChild($postalAddress);

        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        $registrationName = $xml->createElement('cbc:RegistrationName');
        $registrationName->appendChild($xml->createTextNode($params['name']));
        $partyLegalEntity->appendChild($registrationName);
        $party->appendChild($partyLegalEntity);

        $contact = $xml->createElement('cac:Contact');
        $telephone = $xml->createElement('cbc:Telephone',$params['phone']);
        $email = $xml->createElement('cbc:ElectronicMail', $params['email']);
        $contact->appendChild($telephone);
        $contact->appendChild($email);
        $party->appendChild($contact);

        return $accountingCustomerParty;
    }

    /**
     * Create the <cac:Delivery> XML element for UBL invoice generation.
     *
     * This function builds a complete <cac:Delivery> element with nested structure:
     * - Party identifications (TIN, BRN)
     * - Postal address including city, postal zone, state, and address lines
     * - Country information with identification code and attributes
     * - Legal entity registration name
     *
     * @param DOMDocument $xml    The DOMDocument instance used to generate XML elements.
     * @param array $params       An associative array containing delivery party details:
     *                            - 'TIN' (string): Tax Identification Number.
     *                            - 'BRN' (string): Business Registration Number.
     *                            - 'postalAddress' (array): Must include:
     *                                - 'cityName' (string): City of delivery.
     *                                - 'postalZone' (string): Postal code.
     *                                - 'countrySubentityCode' (string): State/region code.
     *                            - 'addressLines' (array): A list of address lines (strings).
     *                            - 'identificationCode' (string): Country code (e.g., MY).
     *                            - 'listID' (string): Country code list ID (e.g., ISO3166-1).
     *                            - 'listAgencyID' (string): Country code list agency ID.
     *                            - 'name' (string): Legal entity name of the delivery party.
     *
     * @return \DOMNode The constructed <cac:Delivery> XML element.
     */
    public function createDeliveryElement($xml, $params)
    {
        $delivery = $xml->createElement('cac:Delivery');

        $deliveryParty = $xml->createElement('cac:DeliveryParty');

        $partyIdentifications = [
            ['schemeID' => 'TIN', 'ID' => $params['TIN']],
            ['schemeID' => 'BRN', 'ID' => $params['BRN']],
        ];

        foreach ($partyIdentifications as $identification) {
            $partyIdentification = $xml->createElement('cac:PartyIdentification');
            $idElement = $xml->createElement('cbc:ID', $identification['ID']);
            $idElement->setAttribute('schemeID', $identification['schemeID']);
            $partyIdentification->appendChild($idElement);
            $deliveryParty->appendChild($partyIdentification);
        }

        $address = $params['postalAddress'];
        $postalAddress = $xml->createElement('cac:PostalAddress');
        $postalAddress->appendChild($xml->createElement('cbc:CityName', $address['cityName']));
        $postalAddress->appendChild($xml->createElement('cbc:PostalZone', $address['postalZone']));
        $postalAddress->appendChild($xml->createElement('cbc:CountrySubentityCode', $address['countrySubentityCode']));
        
        $addressLines = $params['addressLines'];
        foreach ($addressLines as $line) {
            $addressLine = $xml->createElement('cac:AddressLine');
            $lineElement = $xml->createElement('cbc:Line', $line);
            $addressLine->appendChild($lineElement);
            $postalAddress->appendChild($addressLine);
        }

        $country = $xml->createElement('cac:Country');
        $identificationCode = $xml->createElement('cbc:IdentificationCode', $params['identificationCode']);
        $identificationCode->setAttribute('listID', $params['listID']);
        $identificationCode->setAttribute('listAgencyID', $params['listAgencyID']);
        $country->appendChild($identificationCode);
        $postalAddress->appendChild($country);

        $deliveryParty->appendChild($postalAddress);

        $partyLegalEntity = $xml->createElement('cac:PartyLegalEntity');
        $partyLegalEntity->appendChild($xml->createElement('cbc:RegistrationName', $params['name']));
        $deliveryParty->appendChild($partyLegalEntity);

        $delivery->appendChild($deliveryParty);

        return $delivery;
    }

    public function createPaymentMeansElement($xml, $paymentMode  = '03')
    {
        $paymentMeans = $xml->createElement('cac:PaymentMeans');

        $paymentMeansCode = $xml->createElement('cbc:PaymentMeansCode', $paymentMode);
        $paymentMeans->appendChild($paymentMeansCode);

        // $payeeFinancialAccount = $xml->createElement('cac:PayeeFinancialAccount');
        // $accountID = $xml->createElement('cbc:ID', '1234567890');
        // $payeeFinancialAccount->appendChild($accountID);
        
        // $paymentMeans->appendChild($payeeFinancialAccount);

        return $paymentMeans;
    }

    public function createPaymentTermsElement($xml, $noteText)
    {
        $paymentTerms = $xml->createElement('cac:PaymentTerms');

        $note = $xml->createElement('cbc:Note', $noteText);
        $paymentTerms->appendChild($note);

        return $paymentTerms;
    }

    public function createPrepaidPaymentElement($xml, $params)
    {
        $prepaidPayment = $xml->createElement('cac:PrepaidPayment');

        $id = $xml->createElement('cbc:ID', $params['id']);
        $prepaidPayment->appendChild($id);

        $paidAmount = $xml->createElement('cbc:PaidAmount', $params['paidAmount']);
        $paidAmount->setAttribute('currencyID', 'MYR');
        $prepaidPayment->appendChild($paidAmount);

        $paidDate = $xml->createElement('cbc:PaidDate', $params['paidDate']);
        $prepaidPayment->appendChild($paidDate);

        $paidTime = $xml->createElement('cbc:PaidTime', $params['paidTime']);
        $prepaidPayment->appendChild($paidTime);

        return $prepaidPayment;
    }

    /**
     * Create the <cac:AllowanceCharge> XML element.
     *
     * This function builds a <cac:AllowanceCharge> element with:
     * - A charge indicator (true for charge, false for allowance)
     * - A reason for the charge or allowance
     * - An amount with specified currency
     *
     * Typically used in UBL invoices to represent discounts, charges, or adjustments.
     *
     * @param DOMDocument $xml    The DOMDocument instance used to create XML elements.
     * @param array $params       An associative array with the following keys:
     *                            - 'chargeIndicator' (bool): Indicates if it is a charge (true) or allowance (false).
     *                            - 'reason' (string): The reason for the charge or allowance.
     *                            - 'amount' (string|float): The monetary amount.
     *                            - 'currency' (string): Currency code (e.g., 'MYR', 'USD').
     *
     * @return \DOMNode The constructed <cac:AllowanceCharge> XML element.
     */
    public function createAllowanceChargeElement($xml, $params)
    {
        $allowanceCharge = $xml->createElement('cac:AllowanceCharge');

        $chargeIndicatorElement = $xml->createElement('cbc:ChargeIndicator', $params['chargeIndicator']);
        $allowanceCharge->appendChild($chargeIndicatorElement);

        $allowanceChargeReason = $xml->createElement('cbc:AllowanceChargeReason', $params['reason']);
        $allowanceCharge->appendChild($allowanceChargeReason);

        $amountElement = $xml->createElement('cbc:Amount', $params['amount']);
        $amountElement->setAttribute('currencyID', $params['currency']);
        $allowanceCharge->appendChild($amountElement);

        return $allowanceCharge;
    }

    /**
     * Create the <cac:TaxTotal> XML element.
     *
     * This function builds a complete <cac:TaxTotal> element structure, including:
     * - Total tax amount
     * - Two <cac:TaxSubtotal> entries (with tax category ID "01" and "02")
     * - Taxable amounts, tax amounts, and tax scheme identifiers with relevant metadata
     *
     * Commonly used in UBL-compliant invoices to represent detailed tax breakdowns.
     *
     * @param DOMDocument $xml    The DOMDocument instance used to create XML elements.
     * @param array $params       An associative array with the following keys:
     *                            - 'taxAmount' (string|float): Total tax amount (same value used in sub-elements).
     *                            - 'taxableAmount' (string|float): Taxable amount (same value used in both subtotals).
     *                            - 'currencyID' (string): Currency code (e.g., 'MYR', 'USD').
     *                            - 'taxSchemeID' (string): Identifier for the tax scheme (e.g., 'GST').
     *                            - 'schemeID' (string): The scheme identifier attribute (e.g., 'UN/ECE 5153').
     *                            - 'schemeAgencyID' (string): The scheme agency ID attribute (e.g., '6').
     *
     * @return \DOMNode The constructed <cac:TaxTotal> XML element.
     */
    public function createTaxTotalElement($xml, $params)
    {
        $taxTotal = $xml->createElement('cac:TaxTotal');

        $taxAmountElement = $xml->createElement('cbc:TaxAmount', $params['taxAmount']);
        $taxAmountElement->setAttribute('currencyID', $params['currencyID']);
        $taxTotal->appendChild($taxAmountElement);

        $taxSubtotal = $xml->createElement('cac:TaxSubtotal');

        $taxableAmountElement = $xml->createElement('cbc:TaxableAmount', $params['taxableAmount']);
        $taxableAmountElement->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal->appendChild($taxableAmountElement);

        $taxAmountSubtotalElement = $xml->createElement('cbc:TaxAmount', $params['taxAmount']);
        $taxAmountSubtotalElement->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal->appendChild($taxAmountSubtotalElement);

        $taxCategory = $xml->createElement('cac:TaxCategory');

        $taxCategoryID = $xml->createElement('cbc:ID', '01');
        $taxCategory->appendChild($taxCategoryID);

        $taxScheme = $xml->createElement('cac:TaxScheme');
        $taxSchemeIDElement = $xml->createElement('cbc:ID', $params['taxSchemeID']);
        $taxSchemeIDElement->setAttribute('schemeID', $params['schemeID']);
        $taxSchemeIDElement->setAttribute('schemeAgencyID', $params['schemeAgencyID']);
        $taxScheme->appendChild($taxSchemeIDElement);

        $taxCategory->appendChild($taxScheme);
        $taxSubtotal->appendChild($taxCategory);

        $taxSubtotal2 = $xml->createElement('cac:TaxSubtotal');
        $taxableAmountElement2 = $xml->createElement('cbc:TaxableAmount', $params['taxableAmount']);
        $taxableAmountElement2->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal2->appendChild($taxableAmountElement2);

        $taxAmountSubtotalElement2 = $xml->createElement('cbc:TaxAmount', $params['taxAmount']);
        $taxAmountSubtotalElement2->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal2->appendChild($taxAmountSubtotalElement2);

        $taxCategory2 = $xml->createElement('cac:TaxCategory');

        $taxCategoryID2 = $xml->createElement('cbc:ID', '02');
        $taxCategory2->appendChild($taxCategoryID2);

        $taxScheme2 = $xml->createElement('cac:TaxScheme');
        $taxSchemeIDElement2 = $xml->createElement('cbc:ID', $params['taxSchemeID']);
        $taxSchemeIDElement2->setAttribute('schemeID', $params['schemeID']);
        $taxSchemeIDElement2->setAttribute('schemeAgencyID', $params['schemeAgencyID']);
        $taxScheme2->appendChild($taxSchemeIDElement2);

        $taxCategory2->appendChild($taxScheme2);
        
        $taxSubtotal2->appendChild($taxCategory2);
        $taxTotal->appendChild($taxSubtotal2);

        $taxTotal->appendChild($taxSubtotal);

        return $taxTotal;
    }

    /**
     * Creates the Legal Monetary Total element in XML.
     *
     * This function generates the `cac:LegalMonetaryTotal` element in XML, containing multiple monetary-related amounts.
     * Each amount element includes the `currencyID` attribute, set to 'MYR' by default, to indicate the currency.
     * 
     * @param \DOMDocument $xml      The DOMDocument object used to create the XML
     * @param array $params          An associative array containing the amounts to be included in the Legal Monetary Total element:
     *                              - 'lineExtensionAmount'      The total amount excluding tax
     *                              - 'taxExclusiveAmount'       The amount excluding tax (tax-exclusive)
     *                              - 'taxInclusiveAmount'       The amount including tax (tax-inclusive)
     *                              - 'allowanceTotalAmount'     The total allowance (discount) amount
     *                              - 'chargeTotalAmount'        The total charge amount (excluding allowance)
     *                              - 'payableAmount'            The total payable amount (final amount, including tax and allowances)
     * 
     * @return \DOMNode           Returns the created `cac:LegalMonetaryTotal` element
     */
    public function createLegalMonetaryTotalElement($xml, $params){
        $legalMonetaryTotal = $xml->createElement('cac:LegalMonetaryTotal');
    
        // customer真正给的价钱
        $lineExtensionAmountElement = $xml->createElement('cbc:LineExtensionAmount', $params['lineExtensionAmount']);
        $lineExtensionAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($lineExtensionAmountElement);
    
        //好像跟上面一样
        $taxExclusiveAmountElement = $xml->createElement('cbc:TaxExclusiveAmount', $params['taxExclusiveAmount']);
        $taxExclusiveAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($taxExclusiveAmountElement);
    
        //全部包括tax
        $taxInclusiveAmountElement = $xml->createElement('cbc:TaxInclusiveAmount', $params['taxInclusiveAmount']);
        $taxInclusiveAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($taxInclusiveAmountElement);
    
        //total discount多少
        $allowanceTotalAmountElement = $xml->createElement('cbc:AllowanceTotalAmount', $params['allowanceTotalAmount']);
        $allowanceTotalAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($allowanceTotalAmountElement);
    
        //税前charge的费用
        $chargeTotalAmountElement = $xml->createElement('cbc:ChargeTotalAmount', $params['chargeTotalAmount']);
        $chargeTotalAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($chargeTotalAmountElement);
    
    
        //总共费用，包括tax和discount，不包括提前给的费用
        $payableAmountElement = $xml->createElement('cbc:PayableAmount', $params['payableAmount']);
        $payableAmountElement->setAttribute('currencyID', $params['currency']);
        $legalMonetaryTotal->appendChild($payableAmountElement);
    
        return $legalMonetaryTotal;
    }

    /**
     * 创建并返回一个完整的 UBL 发票明细（InvoiceLine）XML 元素。
     *
     * 此函数会根据传入的参数组装一个符合 UBL 标准的 <cac:InvoiceLine> 元素，
     * 包含商品数量、金额、税务信息、折扣/附加费用、商品信息、价格等。
     *
     * @param DOMDocument $xml   当前的 DOMDocument 实例，用于创建 XML 元素。
     * @param array $params      包含发票明细信息的数组，结构如下：
     * 
     * - id (string): 发票行编号
     * - invoicedQuantity (float|int): 开票数量
     * - unitCode (string): 单位代码（如 'EA' 表示 Each）
     * - lineExtensionAmount (float): 发票行净额（不含税）
     * - currencyID (string): 货币代码，如 'MYR'
     * - allowanceCharges (array): 折扣或附加费用的数组，每个元素包含：
     *     - chargeIndicator (bool): 是否是附加费用（true 表示附加费用，false 表示折扣）
     *     - reason (string): 原因说明
     *     - amount (float): 金额
     * - taxAmount (float): 税额总计
     * - taxableAmount (float): 应纳税金额
     * - taxExemptionReason (string): 免税原因说明
     * - description (string): 商品描述
     * - originCountryCode (string): 原产国代码（如 'MY'）
     * - itemClassificationCodes (array): 
     *     - code (string): 分类代码
     * - priceAmount (float): 单价
     * - amount (float): 金额
     * 
     * @return \DOMNode 返回创建好的 <cac:InvoiceLine> 元素
     */
    public function createInvoiceLineElement($xml, $params) {
        $invoiceLine = $xml->createElement('cac:InvoiceLine');
    
        $idElement = $xml->createElement('cbc:ID', $params['id']);
        $invoiceLine->appendChild($idElement);
    
        $invoicedQuantityElement = $xml->createElement('cbc:InvoicedQuantity', $params['invoicedQuantity']);
        $invoicedQuantityElement->setAttribute('unitCode', $params['unitCode']);
        $invoiceLine->appendChild($invoicedQuantityElement);
    
        $lineExtensionAmountElement = $xml->createElement('cbc:LineExtensionAmount', $params['lineExtensionAmount']);
        $lineExtensionAmountElement->setAttribute('currencyID', $params['currencyID']);
        $invoiceLine->appendChild($lineExtensionAmountElement);

        $allowanceCharges = $params['allowanceCharges'];
        if($allowanceCharges){
            foreach ($allowanceCharges as $charge) {
                $allowanceCharge = $xml->createElement('cac:AllowanceCharge');
        
                $chargeIndicator = $xml->createElement('cbc:ChargeIndicator', $charge['chargeIndicator'] ? 'true' : 'false');
                $allowanceCharge->appendChild($chargeIndicator);
        
                $allowanceChargeReason = $xml->createElement('cbc:AllowanceChargeReason', $charge['reason']);
                $allowanceCharge->appendChild($allowanceChargeReason);

                $amount = $xml->createElement('cbc:Amount', number_format($charge['amount'], 2, '.', ''));
                $amount->setAttribute('currencyID', $params['currencyID']);
                $allowanceCharge->appendChild($amount);
        
                $invoiceLine->appendChild($allowanceCharge);
            }
        }

    
        $taxTotal = $xml->createElement('cac:TaxTotal');
        $taxAmountElement = $xml->createElement('cbc:TaxAmount', number_format($params['taxAmount'], 2, '.', ''));
        $taxAmountElement->setAttribute('currencyID', $params['currencyID']);
        $taxTotal->appendChild($taxAmountElement);
    
        $taxSubtotal = $xml->createElement('cac:TaxSubtotal');
        $taxableAmountElement = $xml->createElement('cbc:TaxableAmount', number_format($params['taxableAmount'], 2, '.', ''));
        $taxableAmountElement->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal->appendChild($taxableAmountElement);
    
        $taxAmountElement2 = $xml->createElement('cbc:TaxAmount', number_format($params['taxAmount'], 2, '.', ''));
        $taxAmountElement2->setAttribute('currencyID', $params['currencyID']);
        $taxSubtotal->appendChild($taxAmountElement2);
    
        $taxCategory = $xml->createElement('cac:TaxCategory');
        $taxCategoryId = $xml->createElement('cbc:ID', 'E');
        $taxCategory->appendChild($taxCategoryId);
    
        $taxExemptionReasonElement = $xml->createElement('cbc:TaxExemptionReason', $params['taxExemptionReason']);
        $taxCategory->appendChild($taxExemptionReasonElement);
    
        $taxScheme = $xml->createElement('cac:TaxScheme');
        $taxSchemeId = $xml->createElement('cbc:ID', 'OTH');
        $taxSchemeId->setAttribute('schemeID', 'UN/ECE 5153');
        $taxSchemeId->setAttribute('schemeAgencyID', '6');
        $taxScheme->appendChild($taxSchemeId);
        $taxCategory->appendChild($taxScheme);
    
        $taxSubtotal->appendChild($taxCategory);
        $taxTotal->appendChild($taxSubtotal);
        $invoiceLine->appendChild($taxTotal);
    
        $item = $xml->createElement('cac:Item');
        $descriptionElement = $xml->createElement('cbc:Description', $params['description']);
        $item->appendChild($descriptionElement);
    
        $originCountry = $xml->createElement('cac:OriginCountry');
        $originCountryCodeElement = $xml->createElement('cbc:IdentificationCode', $params['originCountryCode']);
        $originCountry->appendChild($originCountryCodeElement);
        $item->appendChild($originCountry);
    
        $itemClassificationCodes = $params['itemClassificationCodes'];
        foreach ($itemClassificationCodes as $itemClassificationCode) {
            $commodityClassification2 = $xml->createElement('cac:CommodityClassification');
            $itemClassificationCode2 = $xml->createElement('cbc:ItemClassificationCode', $itemClassificationCode['code']);
            $itemClassificationCode2->setAttribute('listID', 'CLASS');
            $commodityClassification2->appendChild($itemClassificationCode2);
            $item->appendChild($commodityClassification2);
        }
        
        $invoiceLine->appendChild($item);
    
        $price = $xml->createElement('cac:Price');
        $priceAmountElement = $xml->createElement('cbc:PriceAmount', number_format($params['priceAmount'], 2, '.', ''));
        $priceAmountElement->setAttribute('currencyID', $params['currencyID']);
        $price->appendChild($priceAmountElement);
        $invoiceLine->appendChild($price);
    
        $itemPriceExtension = $xml->createElement('cac:ItemPriceExtension');
        $amountElement = $xml->createElement('cbc:Amount', number_format($params['amount'], 2, '.', ''));
        $amountElement->setAttribute('currencyID', $params['currencyID']);
        $itemPriceExtension->appendChild($amountElement);
        $invoiceLine->appendChild($itemPriceExtension);
    
        return $invoiceLine;
    }

    public function createTaxExchangeRate($xml, $params)
    {
        $taxExchangeRate = $xml->createElement('cac:TaxExchangeRate');

        $sourceCurrencyCodeElement = $xml->createElement('cbc:SourceCurrencyCode', $params['sourceCurrencyCode']);
        $taxExchangeRate->appendChild($sourceCurrencyCodeElement);

        $targetCurrencyCodeElement = $xml->createElement('cbc:TargetCurrencyCode', $params['targetCurrencyCode']);
        $taxExchangeRate->appendChild($targetCurrencyCodeElement);

        $calculationRateElement = $xml->createElement('cbc:CalculationRate', $params['calculationRate']);
        $taxExchangeRate->appendChild($calculationRateElement);

        return $taxExchangeRate;
    }
}