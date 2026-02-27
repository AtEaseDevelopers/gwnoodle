<?php

namespace App\Http\Controllers;

use App\DataTables\EinvoiceDataTable;
use App\DataTables\ConsolidatedEinvoiceDataTable;
use App\Models\Einvoice;
use App\Models\ConsolidatedEinvoice;
use App\Models\Invoice;
use App\Services\MyInvoisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Flash;

class EInvoiceController extends Controller
{
    protected $myInvoisService;
    protected $xmlGenerator;

    public function __construct()
    {
        $this->myInvoisService = new MyInvoisService();
        if (class_exists(\App\Services\EInvoiceXmlGenerateService::class)) {
            $this->xmlGenerator = new \App\Services\EInvoiceXmlGenerateService();
        }
    }

    /**
     * Display a listing of the e-invoices.
     *
     * @param EinvoiceDataTable $einvoiceDataTable
     * @return \Illuminate\Http\Response
     */
    public function index(EinvoiceDataTable $einvoiceDataTable)
    {
        return $einvoiceDataTable->render('einvoices.index');
    }

    /**
     * Display a listing of the consolidated e-invoices.
     *
     * @param ConsolidatedEinvoiceDataTable $consolidatedEinvoiceDataTable
     * @return \Illuminate\Http\Response
     */
    public function indexConsolidated(ConsolidatedEinvoiceDataTable $consolidatedEinvoiceDataTable)
    {
        return $consolidatedEinvoiceDataTable->render('consolidated_einvoices.index');
    }

    /**
     * Submit selected invoices as e-invoice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submit(Request $request)
    {
        $request->validate([
            'invoices' => 'required|array|min:1',
            'invoices.*.id' => 'required|integer|exists:invoices,id',
            'invoices.*.with_sg_gst' => 'required',
            'currencyRate' => 'nullable|numeric|min:0',
        ]);
        
        $selectedInvoices = $request->input('invoices');
        
        foreach ($selectedInvoices as $key => $invoiceItem) {
            $selectedInvoices[$key]['with_sg_gst'] = filter_var($invoiceItem['with_sg_gst'], FILTER_VALIDATE_BOOLEAN);
        }
        
        $request->merge(['invoices' => $selectedInvoices]);

        $currencyRate = $request->input('currencyRate');

        $documents = [];
        $invoiceSkuMap = [];
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        try {
            foreach ($selectedInvoices as $invoiceItem) {
                $invoice = Invoice::with('customer')->find($invoiceItem['id']);
                if (!$invoice) {
                    continue;
                }

                $customer = $invoice->customer;
                if (!$customer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice #' . $invoice->invoiceno . ' has no customer assigned',
                    ], 400);
                }

                $validationError = $this->validateCustomerEinvoiceDetails($customer, $invoice->invoiceno);
                if ($validationError) {
                    return response()->json([
                        'success' => false,
                        'message' => $validationError,
                    ], 400);
                }

                // Generate unique SKU (without saving to DB)
                // Use forceUnique=true to ensure uniqueness even if previous submission failed
                $einvoiceTemp = new Einvoice();
                $sku = $einvoiceTemp->generateSku($invoice->id, true);
                
                Log::info('E-Invoice SKU Generated', [
                    'invoice_id' => $invoice->id,
                    'sku' => $sku,
                    'force_unique' => true,
                ]);

                // Store mapping of SKU to invoice data for later DB creation
                $invoiceSkuMap[$sku] = [
                    'invoice_id' => $invoice->id,
                    'with_sg_gst' => $invoiceItem['with_sg_gst'] ?? false,
                    'currency' => 'MYR',
                ];

                // Create temporary Einvoice model instance for XML generation (not saved)
                $einvoice = new Einvoice([
                    'sku' => $sku,
                    'invoice_batch_id' => $invoice->id,
                    'currency' => 'MYR',
                    'with_sg_gst' => $invoiceItem['with_sg_gst'] ?? false,
                ]);
                $einvoice->setRelation('invoice', $invoice);

                // Generate XML
                $xmlContent = $this->xmlGenerator->generateEInvoiceXml($einvoice, $currencyRate);

                // Prepare document
                $documents[] = $this->myInvoisService->prepareDocument($xmlContent, $sku);
            }

            if (empty($documents)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid invoices to submit',
                ], 400);
    }

            // Submit all documents to API
            $response = $this->myInvoisService->submitDocuments($documents);

            Log::info('E-Invoice Submission - Full API Response', [
                'response_keys' => array_keys($response),
                'has_accepted' => isset($response['acceptedDocuments']),
                'has_rejected' => isset($response['rejectedDocuments']),
                'accepted_count' => isset($response['acceptedDocuments']) ? count($response['acceptedDocuments']) : 0,
                'rejected_count' => isset($response['rejectedDocuments']) ? count($response['rejectedDocuments']) : 0,
                'full_response' => $response,
            ]);

            // Only save to DB if documents were accepted
            DB::beginTransaction();
            try {
                // Process accepted documents - CREATE records only for accepted ones
                if (isset($response['acceptedDocuments']) && !empty($response['acceptedDocuments'])) {
                    Log::info('E-Invoice Submission - Processing Accepted Documents', [
                        'count' => count($response['acceptedDocuments']),
                        'documents' => $response['acceptedDocuments'],
                    ]);

                    foreach ($response['acceptedDocuments'] as $index => $document) {
                        $uuid = $document['uuid'] ?? null;
                        $invoiceCodeNumber = $document['invoiceCodeNumber'] ?? null;

                        Log::info('E-Invoice Submission - Processing Accepted Document', [
                            'index' => $index,
                            'uuid' => $uuid,
                            'invoiceCodeNumber' => $invoiceCodeNumber,
                            'document' => $document,
                        ]);

                        if (!isset($invoiceSkuMap[$invoiceCodeNumber])) {
                            Log::warning('E-Invoice Submission - SKU not found in mapping', [
                                'invoiceCodeNumber' => $invoiceCodeNumber,
                                'available_skus' => array_keys($invoiceSkuMap),
                            ]);
                            continue;
                        }

                        $data = $invoiceSkuMap[$invoiceCodeNumber];

                        // Get document details
                        Log::info('E-Invoice Submission - Getting Document Details', [
                            'uuid' => $uuid,
                            'sku' => $invoiceCodeNumber,
                        ]);

                        $details = $this->myInvoisService->getDocumentDetails($uuid);

                        Log::info('E-Invoice Submission - Document Details Retrieved', [
                            'uuid' => $uuid,
                            'sku' => $invoiceCodeNumber,
                            'has_error' => isset($details['error']),
                            'has_longId' => isset($details['longId']),
                            'details' => $details,
                        ]);

                        if (!isset($details['error']) && isset($details['longId'])) {
                            // Create DB record only AFTER acceptance and validation
                            Einvoice::create([
                                'sku' => $invoiceCodeNumber,
                                'invoice_batch_id' => $data['invoice_id'],
                                'currency' => $data['currency'],
                                'status' => Einvoice::STATUS_VALID,
                                'uuid' => $uuid,
                                'longId' => $details['longId'],
                                'submission_date' => now(),
                                'validated_time' => $details['dateTimeValidated'] ?? null,
                                'with_sg_gst' => $data['with_sg_gst'],
                            ]);
                            $results['successful'][] = $invoiceCodeNumber;
                            Log::info('E-Invoice Submission - Document Saved Successfully', [
                                'sku' => $invoiceCodeNumber,
                                'uuid' => $uuid,
                                'longId' => $details['longId'],
                            ]);
                        } else {
                            // Create record with INVALID status if longId not available yet
                            Einvoice::create([
                                'sku' => $invoiceCodeNumber,
                                'invoice_batch_id' => $data['invoice_id'],
                                'currency' => $data['currency'],
                                'status' => Einvoice::STATUS_INVALID,
                                'uuid' => $uuid,
                                'submission_date' => now(),
                                'with_sg_gst' => $data['with_sg_gst'],
                            ]);
                            $errorMessage = $details['error'] ?? 'Failed to get document details';
                            $results['failed'][] = [
                                'sku' => $invoiceCodeNumber,
                                'error' => $errorMessage
                            ];
                            Log::warning('E-Invoice Submission - Document Accepted but longId Not Available', [
                                'sku' => $invoiceCodeNumber,
                                'uuid' => $uuid,
                                'error' => $errorMessage,
                                'details' => $details,
                            ]);
                        }
                    }
                } else {
                    Log::warning('E-Invoice Submission - No Accepted Documents', [
                        'response' => $response,
                    ]);
                }

                // Process rejected documents - DO NOT save to DB
                if (isset($response['rejectedDocuments']) && !empty($response['rejectedDocuments'])) {
                    Log::warning('E-Invoice Submission - Processing Rejected Documents', [
                        'count' => count($response['rejectedDocuments']),
                        'rejected_documents' => $response['rejectedDocuments'],
                    ]);

                    foreach ($response['rejectedDocuments'] as $index => $rejected) {
                        Log::error('E-Invoice Submission - Document Rejected', [
                            'index' => $index,
                            'invoiceCodeNumber' => $rejected['invoiceCodeNumber'] ?? null,
                            'error' => $rejected['error'] ?? null,
                            'full_rejected' => $rejected,
                        ]);

                        $results['failed'][] = [
                            'sku' => $rejected['invoiceCodeNumber'] ?? 'Unknown',
                            'error' => $rejected['error']['message'] ?? 'Rejected by MyInvois',
                            'error_details' => $rejected['error'] ?? [],
                        ];
                    }
                }

                DB::commit();

                Log::info('E-Invoice Submission - Final Results Summary', [
                    'successful_count' => count($results['successful']),
                    'failed_count' => count($results['failed']),
                    'successful' => $results['successful'],
                    'failed' => $results['failed'],
                    'total_submitted' => count($documents),
                ]);
            } catch (\Exception $dbException) {
                DB::rollBack();
                Log::error('E-Invoice Submission - Database Error', [
                    'error' => $dbException->getMessage(),
                    'trace' => $dbException->getTraceAsString(),
                ]);
                throw $dbException;
            }

            return response()->json([
                'success' => true,
                'message' => 'E-Invoices submitted successfully',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('E-Invoice Submission Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Submission failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit consolidated e-invoice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitConsolidated(Request $request)
    {
        $request->validate([
            'invoices' => 'required|array',
            'invoices.*' => 'required|integer',
            'currencyRate' => 'nullable|numeric',
        ]);

        $selectedInvoiceIds = $request->input('invoices');
        $currencyRate = $request->input('currencyRate');

        try {
            $invoices = Invoice::whereIn('id', $selectedInvoiceIds)->with('customer')->get();
            
            $validInvoiceIds = [];
            $invalidInvoiceIds = [];
            
            foreach ($invoices as $invoice) {
                $customer = $invoice->customer;
                if (!$customer) {
                    $invalidInvoiceIds[] = $invoice->id;
                    Log::warning('Consolidated E-Invoice - Invoice excluded (no customer)', [
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoiceno,
                    ]);
                    continue;
                }
                
                $customerPhone = $customer->phone ?? '';
                $phoneLength = strlen(trim($customerPhone));
                
                if ($phoneLength < 8) {
                    $invalidInvoiceIds[] = $invoice->id;
                    Log::warning('Consolidated E-Invoice - Invoice excluded (invalid phone)', [
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoiceno,
                        'customer_id' => $customer->id,
                        'phone' => $customerPhone,
                        'phone_length' => $phoneLength,
                        'required_length' => 8,
                    ]);
                    continue;
                }
                
                $validInvoiceIds[] = $invoice->id;
            }
            
            if (empty($validInvoiceIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid invoices found. All selected invoices have invalid customer data (phone number must be at least 8 characters).',
                    'invalid_count' => count($invalidInvoiceIds),
                    'total_count' => count($selectedInvoiceIds),
                ], 400);
            }
            
            if (count($invalidInvoiceIds) > 0) {
                Log::info('Consolidated E-Invoice - Filtered invalid invoices', [
                    'valid_count' => count($validInvoiceIds),
                    'invalid_count' => count($invalidInvoiceIds),
                    'invalid_invoice_ids' => $invalidInvoiceIds,
                ]);
            }
            
            // Generate SKU (without saving to DB)
            $consolidatedTemp = new \App\Models\ConsolidatedEinvoice();
            $sku = $consolidatedTemp->generateSku();

            // Create temporary consolidated model for XML generation (not saved)
            $consolidatedTemp = new \App\Models\ConsolidatedEinvoice([
                'sku' => $sku,
                'currency' => 'MYR',
            ]);

            // Generate consolidated XML with only valid invoices
            $xmlContent = $this->xmlGenerator->generateConsolidatedXml($validInvoiceIds, $consolidatedTemp, $currencyRate);

            // Submit to API
            $response = $this->myInvoisService->submitAndGetDetails($xmlContent, $sku, true);

            Log::info('Consolidated E-Invoice Submission Response', [
                'response_structure' => array_keys($response),
                'has_submission' => isset($response['submission']),
                'submission_response' => $response['submission'] ?? null,
            ]);

            // Check for rejected documents - DO NOT save to DB if rejected
            if (isset($response['submission']['rejectedDocuments']) && !empty($response['submission']['rejectedDocuments'])) {
                $rejected = $response['submission']['rejectedDocuments'][0];
                return response()->json([
                    'success' => false,
                    'message' => 'Document was rejected: ' . ($rejected['error']['message'] ?? 'Unknown error'),
                    'error_details' => $rejected['error'] ?? [],
                ], 400);
            }

            // Only save to DB if document was ACCEPTED
            if (isset($response['submission']['acceptedDocuments'][0])) {
                $acceptedDoc = $response['submission']['acceptedDocuments'][0];
                $uuid = $acceptedDoc['uuid'];
                
                Log::info('Consolidated E-Invoice Accepted', [
                    'uuid' => $uuid,
                    'codeNumber' => $acceptedDoc['invoiceCodeNumber'] ?? null,
                ]);

                // Get document details
                $details = $this->myInvoisService->getDocumentDetails($uuid);

                DB::beginTransaction();
                try {
                    // Default to Invalid - only set to Valid if explicitly validated as Valid
                    $status = ConsolidatedEinvoice::STATUS_INVALID;
                    
                    // Check for validation errors first
                    if (isset($details['error'])) {
                        $status = ConsolidatedEinvoice::STATUS_INVALID;
                    } elseif (isset($details['validationResults']['status'])) {
                        $validationStatus = $details['validationResults']['status'];
                        if ($validationStatus === 'Valid' && isset($details['longId'])) {
                            $status = ConsolidatedEinvoice::STATUS_VALID;
                        } else {
                            $status = ConsolidatedEinvoice::STATUS_INVALID;
                        }
                    } elseif (!isset($details['error']) && isset($details['longId'])) {
                        // If we have longId without errors, consider it Valid
                        $status = ConsolidatedEinvoice::STATUS_VALID;
                    }
                    
                    // Create consolidated einvoice record ONLY AFTER acceptance
                    $consolidatedEinvoice = \App\Models\ConsolidatedEinvoice::create([
                        'sku' => $sku,
                        'currency' => 'MYR',
                        'uuid' => $uuid,
                        'status' => $status,
                        'longId' => $details['longId'] ?? null,
                        'submission_date' => now(),
                        'validated_time' => $details['dateTimeValidated'] ?? null,
                    ]);

                    // Attach only valid invoices to consolidated einvoice
                    $consolidatedEinvoice->invoices()->attach($validInvoiceIds);

                    if (!isset($details['error']) && isset($details['longId'])) {
                        Log::info('Consolidated E-Invoice Created Successfully', [
                            'id' => $consolidatedEinvoice->id,
                            'uuid' => $uuid,
                            'longId' => $details['longId'],
                            'status' => ConsolidatedEinvoice::STATUS_VALID,
                        ]);
                    } else {
                        Log::warning('Consolidated E-Invoice Created but longId not available yet', [
                            'id' => $consolidatedEinvoice->id,
                            'uuid' => $uuid,
                            'error' => $details['error'] ?? 'longId not retrieved',
                        ]);
                    }

                    DB::commit();
                    
                    $message = 'Consolidated E-Invoice submitted successfully';
                    if (count($invalidInvoiceIds) > 0) {
                        $message .= '. ' . count($invalidInvoiceIds) . ' invoice(s) were excluded due to invalid customer data (phone number must be at least 8 characters).';
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'consolidatedEinvoice' => $consolidatedEinvoice,
                        'invalid_invoice_count' => count($invalidInvoiceIds),
                        'valid_invoice_count' => count($validInvoiceIds),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to save consolidated e-invoice after acceptance', [
                        'uuid' => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            } else {
                Log::error('Consolidated E-Invoice - No accepted documents in response', [
                    'response' => $response,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No accepted documents in response',
                    'response' => $response,
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Consolidated E-Invoice Submission Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Submission failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh e-invoice status from MyInvois API
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function refreshStatus($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $einvoice = Einvoice::findOrFail($id);

            if (!$einvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Invoice has not been submitted yet',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($einvoice->uuid);

            if (isset($details['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $details['error'] ?? 'Failed to refresh status',
                ], 400);
            }

            $status = $einvoice->status;
            if (isset($details['validationResults']['status'])) {
                $validationStatus = $details['validationResults']['status'];
                if ($validationStatus === 'Invalid') {
                    $status = Einvoice::STATUS_INVALID;
                } elseif ($validationStatus === 'Valid' && isset($details['longId'])) {
                    $status = Einvoice::STATUS_VALID;
                }
            } elseif (isset($details['longId'])) {
                $status = Einvoice::STATUS_VALID;
            }

                $einvoice->update([
                'longId' => $details['longId'] ?? $einvoice->longId,
                'status' => $status,
                'validated_time' => $details['dateTimeValidated'] ?? $einvoice->validated_time,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Status refreshed successfully',
                    'einvoice' => $einvoice->fresh(),
                ]);

        } catch (\Exception $e) {
            Log::error('E-Invoice Status Refresh Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified e-invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $einvoice = Einvoice::with('invoice.customer')->findOrFail($id);

            return view('einvoices.show')->with('einvoice', $einvoice);
        } catch (\Exception $e) {
            Flash::error('Invalid E-Invoice ID');
            return redirect(route('einvoices.index'));
        }
    }

    public function refreshConsolidatedStatus($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $consolidatedEinvoice = ConsolidatedEinvoice::findOrFail($id);

            if (!$consolidatedEinvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consolidated E-Invoice has not been submitted yet',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($consolidatedEinvoice->uuid);

            if (isset($details['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $details['error'] ?? 'Failed to refresh status',
                ], 400);
            }

            // Default to Invalid - only set to Valid if explicitly validated as Valid
            $status = ConsolidatedEinvoice::STATUS_INVALID;
            
            // Check for validation errors first
            if (isset($details['error'])) {
                $status = ConsolidatedEinvoice::STATUS_INVALID;
            } elseif (isset($details['validationResults']['status'])) {
                $validationStatus = $details['validationResults']['status'];
                if ($validationStatus === 'Valid' && isset($details['longId'])) {
                    $status = ConsolidatedEinvoice::STATUS_VALID;
                } else {
                    $status = ConsolidatedEinvoice::STATUS_INVALID;
                }
            } elseif (isset($details['longId'])) {
                // If we have longId without errors, consider it Valid
                $status = ConsolidatedEinvoice::STATUS_VALID;
            }

                $consolidatedEinvoice->update([
                'longId' => $details['longId'] ?? $consolidatedEinvoice->longId,
                'status' => $status,
                'validated_time' => $details['dateTimeValidated'] ?? $consolidatedEinvoice->validated_time,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Status refreshed successfully',
                    'consolidatedEinvoice' => $consolidatedEinvoice->fresh(),
                ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh consolidated e-invoice status', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function viewDocument($id, Request $request)
    {
        try {
            $id = Crypt::decrypt($id);
            $einvoice = Einvoice::findOrFail($id);

            if (!$einvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Invoice has not been submitted yet',
                ], 400);
            }

            $format = $request->get('format', 'PDF');

            if ($format === 'PDF' || $format === 'pdf') {
                try {
                    $einvoice->load('invoice.customer', 'invoice.driver', 'invoice.invoicedetail.product');
                    
                    if (!$einvoice->invoice) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invoice not found for this e-invoice',
                        ], 404);
                    }

                    $invoice = $einvoice->invoice;
                    
                    if ($invoice->customer) {
                        $customer = $invoice->customer;
                        if ($customer->group) {
                            $groupIds = explode(',', $customer->group);
                            $firstGroupId = $groupIds[0] ?? null;
                            if ($firstGroupId) {
                                $customer->groupcompany = DB::table('companies')
                                    ->where('companies.group_id', $firstGroupId)
                                    ->select('companies.*')
                                    ->first();
                            }
                        }
                        
                        try {
                            $creditResult = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                            if (!empty($creditResult) && isset($creditResult[0]->credit)) {
                                $invoice->newcredit = round($creditResult[0]->credit, 2);
                            } else {
                                $invoice->newcredit = 0;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get customer credit for e-invoice PDF', [
                                'invoice_id' => $invoice->id,
                                'customer_id' => $invoice->customer_id,
                                'error' => $e->getMessage(),
                            ]);
                            $invoice->newcredit = 0;
                        }
                    }

                    $qrCodeData = null;
                    if ($einvoice->uuid && $einvoice->longId) {
                        try {
                            $qrCodeData = $this->myInvoisService->getQRCode($einvoice->uuid, $einvoice->longId);
                        } catch (\Exception $e) {
                            Log::warning('Failed to get QR code for PDF', [
                                'einvoice_id' => $einvoice->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $apiDetails = null;
                    if ($einvoice->uuid) {
                        try {
                            $apiDetails = $this->myInvoisService->getDocumentDetails($einvoice->uuid, 1, 0);
                            if (isset($apiDetails['error'])) {
                                $apiDetails = null;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to fetch API details for PDF', [
                                'einvoice_id' => $einvoice->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $min = 550;
                    $each = 23;
                    $itemCount = $einvoice->invoice->invoicedetail ? $einvoice->invoice->invoicedetail->count() : 0;
                    $height = ($itemCount * $each) + $min;

                    $pdf = Pdf::loadView('einvoices.print', [
                        'einvoice' => $einvoice,
                        'invoice' => $invoice,
                        'qrCodeData' => $qrCodeData,
                        'apiDetails' => $apiDetails,
                    ]);

                    return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')
                        ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])
                        ->stream($einvoice->sku . '.pdf');
                } catch (\Exception $e) {
                    Log::error('E-Invoice PDF Generation Failed', [
                        'einvoice_id' => $einvoice->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate PDF: ' . $e->getMessage(),
                    ], 500);
                }
            } elseif ($format === 'XML' || $format === 'xml') {
                $document = $this->myInvoisService->getDocument($einvoice->uuid, $format, $einvoice->longId);
                if (isset($document['document'])) {
                    $xmlContent = base64_decode($document['document']);
                    return response($xmlContent)
                        ->header('Content-Type', 'application/xml')
                        ->header('Content-Disposition', 'inline; filename="' . $einvoice->sku . '.xml"');
                } elseif (is_string($document)) {
                    return response($document)
                        ->header('Content-Type', 'application/xml')
                        ->header('Content-Disposition', 'inline; filename="' . $einvoice->sku . '.xml"');
                }
            }

            return response()->json($document);
        } catch (\Exception $e) {
            Log::error('E-Invoice View Document Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelDocument($id, Request $request)
    {
        try {
            $id = Crypt::decrypt($id);
            $einvoice = Einvoice::findOrFail($id);

            if (!$einvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Invoice has not been submitted yet',
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:300',
            ]);

            Log::info('E-Invoice Cancel - Starting', [
                'id' => $id,
                'uuid' => $einvoice->uuid,
                'current_status' => $einvoice->status,
                'reason' => $request->reason,
            ]);

            if (!$einvoice->submission_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has no submission date.',
                ], 400);
            }

            $hoursSinceSubmission = $einvoice->submission_date->diffInHours(now());
            if ($hoursSinceSubmission > 72) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document cannot be cancelled. Only documents submitted within the last 72 hours can be cancelled.',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($einvoice->uuid, 1, 0);
            
            if (isset($details['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot retrieve document status: ' . $details['error'],
                ], 400);
            }

            $documentStatus = $details['status'] ?? null;
            
            if ($documentStatus !== 'Valid') {
                $statusMessage = $documentStatus ? "Document status is '{$documentStatus}'" : 'Document status is unknown';
                return response()->json([
                    'success' => false,
                    'message' => "Document cannot be cancelled. {$statusMessage}. Only documents with 'Valid' status can be cancelled.",
                ], 400);
            }

            if (isset($details['cancelDateTime']) && $details['cancelDateTime']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has already been cancelled.',
                ], 400);
            }

            $result = $this->myInvoisService->cancelDocument($einvoice->uuid, $request->reason);

            Log::info('E-Invoice Cancel - API Response', [
                'uuid' => $einvoice->uuid,
                'result' => $result,
                'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
            ]);

            $status = Einvoice::STATUS_INVALID;
            if (is_array($result)) {
                $status = $result['status'] ?? $result['documentStatus'] ?? Einvoice::STATUS_INVALID;
            }

            $einvoice->update([
                'status' => $status,
            ]);

            Log::info('E-Invoice Cancel - Success', [
                'id' => $einvoice->id,
                'uuid' => $einvoice->uuid,
                'new_status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document cancelled successfully',
                'einvoice' => $einvoice->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('E-Invoice Cancel - Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['reason'] ?? []),
            ], 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'IncorrectState') !== false || strpos($errorMessage, 'cannot be cancelled') !== false) {
                $errorMessage = 'Document cannot be cancelled. The document may already be cancelled, rejected, or in an invalid state. Only documents with "Valid" status can be cancelled.';
            }
            
            Log::error('E-Invoice Cancel Failed', [
                'id' => $id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    public function getFullDetails($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $einvoice = Einvoice::findOrFail($id);

            if (!$einvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Invoice has not been submitted yet',
                ], 400);
            }

            try {
            $details = $this->myInvoisService->getDocumentDetails($einvoice->uuid, 1, 0);
                
                if (isset($details['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $details['error'],
                    ], 400);
                }

            return response()->json([
                'success' => true,
                'details' => $details,
                'einvoice' => $einvoice,
            ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection timeout. Please try again later.',
                ], 408);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch API details in getFullDetails', [
                    'einvoice_id' => $einvoice->id,
                    'uuid' => $einvoice->uuid,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve details: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('E-Invoice Get Full Details Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get document details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function viewConsolidatedDocument($id, Request $request)
    {
        try {
            $id = Crypt::decrypt($id);
            $consolidatedEinvoice = ConsolidatedEinvoice::findOrFail($id);

            if (!$consolidatedEinvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consolidated E-Invoice has not been submitted yet',
                ], 400);
            }

            $format = $request->get('format', 'PDF');

            if ($format === 'PDF' || $format === 'pdf') {
                try {
                    $consolidatedEinvoice->load('invoices.customer', 'invoices.driver', 'invoices.invoicedetail.product');

                    $invoices = $consolidatedEinvoice->invoices;
                    if ($invoices->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No invoices found for this consolidated e-invoice',
                        ], 404);
                    }

                    foreach ($invoices as $invoice) {
                        if ($invoice->customer) {
                            $customer = $invoice->customer;
                            if ($customer->group) {
                                $groupIds = explode(',', $customer->group);
                                $firstGroupId = $groupIds[0] ?? null;
                                if ($firstGroupId) {
                                    $customer->groupcompany = DB::table('companies')
                                        ->where('companies.group_id', $firstGroupId)
                                        ->select('companies.*')
                                        ->first();
                                }
                            }

                            try {
                                $creditResult = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                                if (!empty($creditResult) && isset($creditResult[0]->credit)) {
                                    $invoice->newcredit = round($creditResult[0]->credit, 2);
                                } else {
                                    $invoice->newcredit = 0;
                                }
                            } catch (\Exception $e) {
                                Log::warning('Failed to get customer credit for consolidated e-invoice PDF', [
                                    'invoice_id' => $invoice->id,
                                    'customer_id' => $invoice->customer_id,
                                    'error' => $e->getMessage(),
                                ]);
                                $invoice->newcredit = 0;
                            }
                        }
                    }

                    $qrCodeData = null;
                    if ($consolidatedEinvoice->uuid && $consolidatedEinvoice->longId) {
                        try {
                            $qrCodeData = $this->myInvoisService->getQRCode($consolidatedEinvoice->uuid, $consolidatedEinvoice->longId);
                        } catch (\Exception $e) {
                            Log::warning('Failed to get QR code for consolidated e-invoice PDF', [
                                'consolidated_einvoice_id' => $consolidatedEinvoice->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $apiDetails = null;
                    if ($consolidatedEinvoice->uuid) {
                        try {
                            $apiDetails = $this->myInvoisService->getDocumentDetails($consolidatedEinvoice->uuid, 1, 0);
                            if (isset($apiDetails['error'])) {
                                $apiDetails = null;
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to fetch API details for consolidated e-invoice PDF', [
                                'consolidated_einvoice_id' => $consolidatedEinvoice->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $min = 550;
                    $each = 23;
                    $totalItems = 0;
                    foreach ($invoices as $invoice) {
                        $totalItems += $invoice->invoicedetail ? $invoice->invoicedetail->count() : 0;
                    }
                    $height = ($totalItems * $each) + $min;

                    $pdf = Pdf::loadView('consolidated_einvoices.print', [
                        'consolidatedEinvoice' => $consolidatedEinvoice,
                        'invoices' => $invoices,
                        'qrCodeData' => $qrCodeData,
                        'apiDetails' => $apiDetails,
                    ]);

                    return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')
                        ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])
                        ->stream($consolidatedEinvoice->sku . '.pdf');

                } catch (\Exception $e) {
                    Log::error('Consolidated E-Invoice PDF Generation Failed', [
                        'consolidated_einvoice_id' => $consolidatedEinvoice->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to generate PDF: ' . $e->getMessage(),
                    ], 500);
                }
            } elseif ($format === 'XML' || $format === 'xml') {
                $document = $this->myInvoisService->getDocument($consolidatedEinvoice->uuid, $format, $consolidatedEinvoice->longId);
                if (isset($document['document'])) {
                    $xmlContent = base64_decode($document['document']);
                    return response($xmlContent)
                        ->header('Content-Type', 'application/xml')
                        ->header('Content-Disposition', 'inline; filename="' . $consolidatedEinvoice->sku . '.xml"');
                } elseif (is_string($document)) {
                    return response($document)
                        ->header('Content-Type', 'application/xml')
                        ->header('Content-Disposition', 'inline; filename="' . $consolidatedEinvoice->sku . '.xml"');
                }
            }

            return response()->json($document);
        } catch (\Exception $e) {
            Log::error('Consolidated E-Invoice View Document Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve document: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelConsolidatedDocument($id, Request $request)
    {
        try {
            $id = Crypt::decrypt($id);
            $consolidatedEinvoice = ConsolidatedEinvoice::findOrFail($id);

            if (!$consolidatedEinvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consolidated E-Invoice has not been submitted yet',
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:300',
            ]);

            Log::info('Consolidated E-Invoice Cancel - Starting', [
                'id' => $id,
                'uuid' => $consolidatedEinvoice->uuid,
                'current_status' => $consolidatedEinvoice->status,
                'reason' => $request->reason,
            ]);

            if (!$consolidatedEinvoice->submission_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has no submission date.',
                ], 400);
            }

            $hoursSinceSubmission = $consolidatedEinvoice->submission_date->diffInHours(now());
            if ($hoursSinceSubmission > 72) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document cannot be cancelled. Only documents submitted within the last 72 hours can be cancelled.',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($consolidatedEinvoice->uuid, 1, 0);
            
            if (isset($details['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot retrieve document status: ' . $details['error'],
                ], 400);
            }

            $documentStatus = $details['status'] ?? null;
            
            if ($documentStatus !== 'Valid') {
                $statusMessage = $documentStatus ? "Document status is '{$documentStatus}'" : 'Document status is unknown';
                return response()->json([
                    'success' => false,
                    'message' => "Document cannot be cancelled. {$statusMessage}. Only documents with 'Valid' status can be cancelled.",
                ], 400);
            }

            if (isset($details['cancelDateTime']) && $details['cancelDateTime']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has already been cancelled.',
                ], 400);
            }

            $result = $this->myInvoisService->cancelDocument($consolidatedEinvoice->uuid, $request->reason);

            Log::info('Consolidated E-Invoice Cancel - API Response', [
                'uuid' => $consolidatedEinvoice->uuid,
                'result' => $result,
                'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
            ]);

            $status = ConsolidatedEinvoice::STATUS_CANCELLED;
            if (is_array($result)) {
                $status = $result['status'] ?? $result['documentStatus'] ?? ConsolidatedEinvoice::STATUS_CANCELLED;
            }

            $consolidatedEinvoice->update([
                'status' => $status,
            ]);

            Log::info('Consolidated E-Invoice Cancel - Success', [
                'id' => $consolidatedEinvoice->id,
                'uuid' => $consolidatedEinvoice->uuid,
                'new_status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document cancelled successfully',
                'consolidatedEinvoice' => $consolidatedEinvoice->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Consolidated E-Invoice Cancel - Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['reason'] ?? []),
            ], 422);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'IncorrectState') !== false || strpos($errorMessage, 'cannot be cancelled') !== false) {
                $errorMessage = 'Document cannot be cancelled. The document may already be cancelled, rejected, or in an invalid state. Only documents with "Valid" status can be cancelled.';
            }
            
            Log::error('Consolidated E-Invoice Cancel Failed', [
                'id' => $id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    public function showConsolidated($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $consolidatedEinvoice = ConsolidatedEinvoice::with('invoices.customer')->findOrFail($id);

            return view('consolidated_einvoices.show')->with('consolidatedEinvoice', $consolidatedEinvoice);
        } catch (\Exception $e) {
            Flash::error('Invalid Consolidated E-Invoice ID');
            return redirect(route('consolidated-einvoices.index'));
        }
    }

    public function getConsolidatedFullDetails($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $consolidatedEinvoice = ConsolidatedEinvoice::findOrFail($id);

            if (!$consolidatedEinvoice->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consolidated E-Invoice has not been submitted yet',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($consolidatedEinvoice->uuid, 1, 0);

            return response()->json([
                'success' => true,
                'details' => $details,
                'consolidatedEinvoice' => $consolidatedEinvoice,
            ]);
        } catch (\Exception $e) {
            Log::error('Consolidated E-Invoice Get Full Details Failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get document details: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function validateCustomerEinvoiceDetails($customer, $invoiceNo = null)
    {
        return null;
    }
}
