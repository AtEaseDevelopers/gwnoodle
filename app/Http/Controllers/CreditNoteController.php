<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Einvoice;
use App\Services\EInvoiceXmlGenerateService;
use App\Services\MyInvoisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Laracasts\Flash\Flash;

class CreditNoteController extends Controller
{
    protected $xmlGenerator;
    protected $myInvoisService;

    public function __construct()
    {
        $this->xmlGenerator = new EInvoiceXmlGenerateService();
        $this->myInvoisService = new MyInvoisService();
    }

    /**
     * Display a listing of credit notes
     */
    public function index()
    {
        $creditNotes = CreditNote::with('einvoices.invoice.customer')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('credit_notes.index', compact('creditNotes'));
    }

    /**
     * Step 1: Note Type Selection (Credit Note)
     * Redirects directly to customer selection since note type is already determined
     */
    public function create()
    {
        return redirect()->route('credit-notes.select-customer', ['note_type' => 'credit']);
    }

    /**
     * Step 2: Customer Selection
     */
    public function selectCustomer(Request $request)
    {
        // Get note_type from request (defaults to credit for credit note controller)
        $noteType = $request->input('note_type', 'credit');
        
        $request->validate([
            'note_type' => 'sometimes|in:credit,debit',
        ]);
        
        // Get all customers who have submitted e-invoices
        $customers = \App\Models\Customer::whereHas('invoices', function($query) {
            $query->whereHas('einvoice', function($q) {
                $q->whereNotNull('uuid')->where('status', 'Valid');
            });
        })->with(['invoices' => function($query) {
            $query->whereHas('einvoice', function($q) {
                $q->whereNotNull('uuid')->where('status', 'Valid');
            })->with('einvoice');
        }])->get();

        return view('credit_notes.select_customer', compact('customers', 'noteType'));
    }

    /**
     * Step 3: Currency Selection
     */
    public function selectCurrency(Request $request)
    {
        $request->validate([
            'note_type' => 'required|in:credit,debit',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $noteType = $request->note_type;
        $customerId = $request->customer_id;

        return view('credit_notes.select_currency', compact('noteType', 'customerId'));
    }

    /**
     * Step 4: E-Invoice Selection
     */
    public function selectEinvoice(Request $request)
    {
        $request->validate([
            'note_type' => 'required|in:credit,debit',
            'customer_id' => 'required|exists:customers,id',
            'currency' => 'required|string|max:10',
        ]);

        $noteType = $request->note_type;
        $customerId = $request->customer_id;
        $currency = $request->currency;

        // Get valid e-invoices for this customer
        $einvoices = Einvoice::whereHas('invoice', function($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })
        ->whereNotNull('uuid')
        ->where('status', 'Valid')
        ->where('currency', $currency)
        ->with('invoice.customer')
        ->get();

        return view('credit_notes.select_einvoice', compact('noteType', 'customerId', 'currency', 'einvoices'));
    }

    /**
     * Step 5: Update E-Invoice (Enter credit note details)
     */
    public function updateEinvoice(Request $request)
    {
        $request->validate([
            'note_type' => 'required|in:credit,debit',
            'customer_id' => 'required|exists:customers,id',
            'currency' => 'required|string|max:10',
            'einvoice_ids' => 'required|array|min:1',
            'einvoice_ids.*' => 'required|exists:einvoices,id',
        ]);

        $noteType = $request->note_type;
        $customerId = $request->customer_id;
        $currency = $request->currency;
        $einvoiceIds = $request->einvoice_ids;

        $einvoices = Einvoice::whereIn('id', $einvoiceIds)
            ->whereNotNull('uuid')
            ->where('status', 'Valid')
            ->with('invoice.customer')
            ->get();

        if ($einvoices->count() !== count($einvoiceIds)) {
            Flash::error('One or more selected e-invoices are invalid.');
            return redirect()->back();
        }

        return view('credit_notes.update_einvoice', compact('noteType', 'customerId', 'currency', 'einvoices'));
    }

    /**
     * Store and submit credit note (legacy method for web form)
     */
    public function store(Request $request)
    {
        $request->validate([
            'note_type' => 'required|in:credit,debit',
            'customer_id' => 'required|exists:customers,id',
            'currency' => 'required|string|max:10',
            'einvoice_ids' => 'required|array|min:1',
            'einvoice_ids.*' => 'required|exists:einvoices,id',
            'changes' => 'required|array|min:1',
            'changes.*.description' => 'required|string|max:255',
            'changes.*.changes' => 'required|numeric',
        ]);

        // Convert to new format
        $einvoices = [];
        $einvoiceModels = Einvoice::whereIn('id', $request->einvoice_ids)
            ->whereNotNull('uuid')
            ->where('status', 'Valid')
            ->with('invoice.invoicedetail')
            ->get();

        foreach ($einvoiceModels as $einvoice) {
            // Calculate total amount from invoice details
            $totalAmount = 0;
            if ($einvoice->invoice && $einvoice->invoice->invoicedetail) {
                foreach ($einvoice->invoice->invoicedetail as $detail) {
                    $totalAmount += floatval($detail->totalprice ?? 0);
                }
            }
            
            // Find matching change item (assuming one change per einvoice for now)
            $changeItem = $request->changes[0] ?? null;
            if ($changeItem) {
                $einvoices[$einvoice->id] = [
                    'sku' => $einvoice->sku,
                    'amount' => $totalAmount,
                    'changes' => $changeItem['changes'],
                    'description' => $changeItem['description'],
                ];
            }
        }

        return $this->submitNote($request->merge(['einvoices' => $einvoices]));
    }

    /**
     * Submit credit note (new method following the pattern)
     */
    public function submitNote(Request $request)
    {
        $einvoices = $request->input('einvoices');
        $currency = $request->input('currency') ?? Session::get('currency');
        $invoiceType = $request->input('invoice_type') ?? Session::get('invoice_type', 'individual');
        $noteType = $request->input('note_type') ?? Session::get('note_type', 'credit');

        $results = [];
        $affectedEinvoiceIds = [];

        // Validate each einvoice
        foreach ($einvoices as $einvoiceId => $invoice) {
            $amount = floatval($invoice['amount'] ?? 0);
            $changes = $invoice['changes'] ?? null;

            if ($changes === null || $changes === '' || floatval($changes) === 0.0) {
                return response()->json([
                    'success' => false,
                    'message' => "EInvoice {$invoice['sku']}: No changes provided",
                ], 400);
            }

            // Allow changes to equal the amount (full credit), with small tolerance for floating point
            $changesAbs = abs(floatval($changes));
            $tolerance = 0.01; // Allow 1 cent tolerance for floating point comparison
            
            if ($changesAbs > ($amount + $tolerance)) {
                return response()->json([
                    'success' => false,
                    'message' => "EInvoice {$invoice['sku']}: Changes (".number_format($changesAbs, 2).") cannot exceed amount (".number_format($amount, 2).")",
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            if ($noteType == 'credit') {
                $note = new CreditNote();
                $note->sku = (new CreditNote)->generateSku();
            } else {
                // DebitNote would go here if implemented
                throw new \Exception('Debit note not yet implemented');
            }

            $totalAmount = 0;
            foreach ($einvoices as $item) {
                $totalAmount += $item['changes'] ?? 0;
            }

            $affectedEinvoiceIds = array_keys($einvoices);

            $note->status = 'Pending';
            $note->from = $invoiceType;
            $note->changes = json_encode($einvoices);
            $note->amount = $totalAmount;
            $note->currency = $currency;
            // uuid and longId will be null by default (set after successful submission)
            $note->save();

            if ($invoiceType == 'consolidated') {
                $note->consolidatedEinvoice()->attach($affectedEinvoiceIds);
            } else {
                $note->einvoices()->attach($affectedEinvoiceIds);
            }

            // Refresh to ensure relationships are loaded
            $note->refresh();
            if ($invoiceType == 'consolidated') {
                $note->load('consolidatedEinvoice');
            } else {
                $note->load('einvoices.invoice.customer');
            }

            // Convert einvoices format for XML generation
            $changesForXml = [];
            foreach ($einvoices as $einvoiceId => $invoiceData) {
                $changesForXml[] = [
                    'sku' => $invoiceData['sku'] ?? '',
                    'description' => $invoiceData['description'] ?? 'Credit note adjustment',
                    'changes' => $invoiceData['changes'] ?? 0,
                ];
            }

            // Log TIN verification before XML generation
            $supplierTIN = config('e-invoices.supplier_tin') ?? env('E_INVOICE_SUPPLIER_TIN');
            $supplierTIN = trim($supplierTIN, '"\' ');
            $supplierTIN = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{201F}\x{2033}\x{2036}"\'"\s]/u', '', $supplierTIN);
            
            Log::info('Credit Note - TIN Verification Before XML Generation', [
                'credit_note_sku' => $note->sku,
                'supplier_tin' => $supplierTIN,
                'supplier_tin_length' => strlen($supplierTIN),
                'supplier_tin_hex' => bin2hex($supplierTIN),
                'invoice_type' => $invoiceType,
            ]);

            $document = $this->xmlGenerator->generateNoteXml($note, $changesForXml, $invoiceType, $currency);

            if (!$document) {
                throw new \Exception('Failed to generate XML');
            }

            $syncResponse = $this->syncNote($document, $note, $einvoices, $invoiceType);
            
            // Check if sync was successful
            $syncData = json_decode($syncResponse->getContent(), true);
            if (isset($syncData['success']) && $syncData['success'] === false) {
                throw new \Exception($syncData['message'] ?? 'Failed to sync note');
            }
            
            if (!empty($syncData['errorDetails'])) {
                throw new \Exception('Failed to sync note: ' . json_encode($syncData['errorDetails']));
            }

            DB::commit();

            return $syncResponse;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to submit note', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing changes: ' . $e->getMessage(),
                'details' => $results,
            ], 500);
        }
    }

    /**
     * Sync credit note to MyInvois
     */
    public function syncNote($document, $note, $changes, $invoiceType)
    {
        $token = $this->myInvoisService->getAccessToken();
        $apiUrl = config('e-invoices.url') ?? env('MYINVOIS_API_URL', 'https://api.myinvois.hasil.gov.my');
        $url = rtrim($apiUrl, '/') . '/api/v1.0/documentsubmissions';
        
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        // Format document for submission (matching MyInvoisService format)
        $documentPayload = [
            'format' => 'XML',
            'document' => base64_encode($document),
            'documentHash' => hash('sha256', $document),
            'codeNumber' => $note->sku,
        ];

        $payload = [
            'documents' => [$documentPayload],
        ];

        try {
            $response = Http::withHeaders($headers)->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $acceptedDocuments = $responseData['acceptedDocuments'] ?? [];
                $rejectedDocuments = $responseData['rejectedDocuments'] ?? [];

                $errorDetails = [];
                $successfulDocuments = [];

                foreach ($acceptedDocuments as $document) {
                    $uuid = $document['uuid'];
                    $invoiceCodeNumber = $document['invoiceCodeNumber'] ?? $note->sku;

                    $documentDetails = $this->myInvoisService->getDocumentDetails($uuid);

                    if (isset($documentDetails['error'])) {
                        $errorDetails[] = [
                            'invoiceCodeNumber' => $invoiceCodeNumber,
                            'error' => $documentDetails['error'],
                        ];
                        continue;
                    }

                    $note->update([
                        'uuid' => $uuid,
                        'longId' => $documentDetails['longId'] ?? null,
                        'submission_date' => Carbon::now(),
                        'status' => 'Valid',
                        'validated_time' => $documentDetails['dateTimeValidated'] ?? null,
                    ]);

                    $successfulDocuments[] = $invoiceCodeNumber;

                    if (isset($documentDetails['uuid']) && isset($documentDetails['longId'])) {
                        $this->generateAndSaveNotePdf($note, $changes, $invoiceType);
                    }
                }

                if (!empty($rejectedDocuments)) {
                    $errorDetails = [];
                    foreach ($rejectedDocuments as $rejectedDoc) {
                        $errorDetails[] = [
                            'invoiceCodeNumber' => $rejectedDoc['invoiceCodeNumber'] ?? $note->sku,
                            'error_code' => $rejectedDoc['error']['code'] ?? null,
                            'error_message' => $rejectedDoc['error']['message'] ?? 'Unknown error',
                            'error_target' => $rejectedDoc['error']['target'] ?? null,
                            'property_path' => $rejectedDoc['error']['propertyPath'] ?? null,
                            'details' => array_map(function ($detail) {
                                return [
                                    'code' => $detail['code'] ?? null,
                                    'message' => $detail['message'] ?? null,
                                    'target' => $detail['target'] ?? null,
                                    'propertyPath' => $detail['propertyPath'] ?? null,
                                ];
                            }, $rejectedDoc['error']['details'] ?? []),
                        ];
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document submission completed',
                    'successfulDocuments' => $successfulDocuments,
                    'errorDetails' => $errorDetails,
                    'redirect_url' => route('credit-notes.index'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Document submission failed',
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Throwable $th) {
            Log::error('Failed to sync note', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync note: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and save credit note PDF
     */
    protected function generateAndSaveNotePdf($note, $changes, $invoiceType)
    {
        try {
            // Load relationships
            if ($invoiceType == 'consolidated') {
                $note->load('consolidatedEinvoice');
            } else {
                $note->load('einvoices.invoice.customer');
            }

            // Generate PDF view (you'll need to create this view)
            $pdf = Pdf::loadView('credit_notes.print', [
                'creditNote' => $note,
                'changes' => $changes,
                'invoiceType' => $invoiceType,
            ]);

            // Save PDF
            $filename = str_replace('/', '-', $note->sku) . '.pdf';
            Storage::put('public/lhdn/pdf/credit-note/' . $filename, $pdf->output());

            Log::info('Credit Note PDF Generated', [
                'credit_note_id' => $note->id,
                'sku' => $note->sku,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to generate credit note PDF', [
                'credit_note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified credit note
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $creditNote = CreditNote::with('einvoices.invoice.customer')->findOrFail($id);

        return view('credit_notes.show', compact('creditNote'));
    }

    /**
     * Cancel a credit note (similar to e-invoice cancellation)
     */
    public function cancel($id, Request $request)
    {
        try {
            $id = Crypt::decrypt($id);
            $creditNote = CreditNote::findOrFail($id);

            if (!$creditNote->uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit note has not been submitted yet',
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|max:300',
            ]);

            Log::info('Credit Note Cancel - Starting', [
                'id' => $id,
                'uuid' => $creditNote->uuid,
                'current_status' => $creditNote->status,
                'reason' => $request->reason,
            ]);

            if (!$creditNote->submission_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document has no submission date.',
                ], 400);
            }

            $hoursSinceSubmission = $creditNote->submission_date->diffInHours(now());
            if ($hoursSinceSubmission > 72) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document cannot be cancelled. Only documents submitted within the last 72 hours can be cancelled.',
                ], 400);
            }

            $details = $this->myInvoisService->getDocumentDetails($creditNote->uuid, 1, 0);
            
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

            $result = $this->myInvoisService->cancelDocument($creditNote->uuid, $request->reason);

            Log::info('Credit Note Cancel - API Response', [
                'uuid' => $creditNote->uuid,
                'result' => $result,
                'result_keys' => is_array($result) ? array_keys($result) : 'not_array',
            ]);

            $status = CreditNote::STATUS_INVALID;
            if (is_array($result)) {
                $status = $result['status'] ?? $result['documentStatus'] ?? CreditNote::STATUS_INVALID;
            }

            $creditNote->update([
                'status' => $status,
            ]);

            Log::info('Credit Note Cancel - Success', [
                'id' => $creditNote->id,
                'uuid' => $creditNote->uuid,
                'new_status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credit note cancelled successfully',
                'creditNote' => $creditNote->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Credit Note Cancel - Validation Error', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['reason'] ?? []),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Credit Note Cancel - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel credit note: ' . $e->getMessage(),
            ], 500);
        }
    }
}
