<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Einvoice;
use App\Models\Customer;
use App\Services\MyInvoisService;
use App\Services\EInvoiceXmlGenerateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSubmitSelfClaimInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:auto-submit-self-claim';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically submit invoices to e-invoice if self_claim_complete is true and invoice is 2+ days old';

    protected $myInvoisService;
    protected $xmlGenerator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->myInvoisService = new MyInvoisService();
        $this->xmlGenerator = new EInvoiceXmlGenerateService();
    }

    /**
     * Check if customer has all required fields for self claim
     *
     * @param Customer $customer
     * @return bool
     */
    protected function isSelfClaimComplete(Customer $customer): bool
    {
        if (!$customer) {
            return false;
        }

        $requiredFields = [
            'phone',
            'email',
            'address',
            'city',
            'postcode',
            'state',
            'country',
            'registration_no',
            'tin',
            'msic',
        ];

        foreach ($requiredFields as $field) {
            $value = $customer->{$field} ?? null;
            if ($value === null || trim((string) $value) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate customer e-invoice details for individual e-invoices
     * Note: TIN validation (EI0 format) is only required for consolidated e-invoices, not individual e-invoices
     *
     * @param Customer $customer
     * @param string $invoiceNo
     * @return string|null Error message or null if valid
     */
    protected function validateCustomerEinvoiceDetails(Customer $customer, string $invoiceNo): ?string
    {
        if (!$customer) {
            return "Invoice #{$invoiceNo} has no customer assigned";
        }

        // Validate phone number length (minimum 8 characters) - required for individual e-invoices
        if (empty($customer->phone) || strlen(trim($customer->phone)) < 8) {
            return "Invoice #{$invoiceNo}: Customer phone number must be at least 8 characters";
        }

        // Note: TIN format validation (must start with EI0) is NOT required for individual e-invoices
        // It's only required for consolidated e-invoices

        return null;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Finding invoices with self_claim_complete = true that are 2+ days old...");

        // Find invoices created 2+ days ago
        $twoDaysAgo = Carbon::now()->subDays(2)->startOfDay();
        
        // Get invoices that:
        // 1. Created 2+ days ago
        // 2. Status is not "New" (status != 0)
        // 3. Not already submitted to e-invoice (no einvoice with uuid)
        // 4. Not in consolidated e-invoice
        $invoices = Invoice::where('created_at', '<=', $twoDaysAgo)
            ->where('status', '!=', 0) // Exclude status "New"
            ->whereDoesntHave('einvoice', function ($query) {
                $query->whereNotNull('uuid');
            })
            ->whereDoesntHave('consolidatedEinvoices')
            ->with('customer')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info("No invoices found that meet the criteria.");
            return 0;
        }

        $this->info("Found {$invoices->count()} invoice(s) to check.");

        // Filter invoices where self_claim_complete is true
        $eligibleInvoices = [];
        foreach ($invoices as $invoice) {
            if ($invoice->customer && $this->isSelfClaimComplete($invoice->customer)) {
                $eligibleInvoices[] = $invoice;
            }
        }

        if (empty($eligibleInvoices)) {
            $this->info("No invoices found with self_claim_complete = true.");
            return 0;
        }

        $this->info("Found " . count($eligibleInvoices) . " invoice(s) with self_claim_complete = true.");

        $documents = [];
        $invoiceSkuMap = [];
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        try {
            foreach ($eligibleInvoices as $invoice) {
                $customer = $invoice->customer;
                
                // Validate customer e-invoice details
                $validationError = $this->validateCustomerEinvoiceDetails($customer, $invoice->invoiceno);
                if ($validationError) {
                    $this->warn("Skipping Invoice #{$invoice->invoiceno}: {$validationError}");
                    $results['failed'][] = [
                        'sku' => $invoice->invoiceno,
                        'error' => $validationError
                    ];
                    continue;
                }

                // Generate unique SKU (without saving to DB)
                $einvoiceTemp = new Einvoice();
                $sku = $einvoiceTemp->generateSku($invoice->id, true);
                
                Log::info('Auto Submit E-Invoice - SKU Generated', [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoiceno,
                    'sku' => $sku,
                ]);

                // Store mapping of SKU to invoice data for later DB creation
                $invoiceSkuMap[$sku] = [
                    'invoice_id' => $invoice->id,
                    'with_sg_gst' => false, // Default to false for auto-submit
                    'currency' => 'MYR',
                ];

                // Create temporary Einvoice model instance for XML generation (not saved)
                $einvoice = new Einvoice([
                    'sku' => $sku,
                    'invoice_batch_id' => $invoice->id,
                    'currency' => 'MYR',
                    'with_sg_gst' => false,
                ]);
                $einvoice->setRelation('invoice', $invoice);

                // Generate XML
                $xmlContent = $this->xmlGenerator->generateEInvoiceXml($einvoice, null);

                // Prepare document
                $documents[] = $this->myInvoisService->prepareDocument($xmlContent, $sku);
            }

            if (empty($documents)) {
                $this->warn("No valid invoices to submit after validation.");
                return 0;
            }

            $this->info("Submitting " . count($documents) . " invoice(s) to MyInvois API...");

            // Submit all documents to API
            $response = $this->myInvoisService->submitDocuments($documents);

            Log::info('Auto Submit E-Invoice - Full API Response', [
                'response_keys' => array_keys($response),
                'has_accepted' => isset($response['acceptedDocuments']),
                'has_rejected' => isset($response['rejectedDocuments']),
                'accepted_count' => isset($response['acceptedDocuments']) ? count($response['acceptedDocuments']) : 0,
                'rejected_count' => isset($response['rejectedDocuments']) ? count($response['rejectedDocuments']) : 0,
            ]);

            // Only save to DB if documents were accepted
            DB::beginTransaction();
            try {
                // Process accepted documents
                if (isset($response['acceptedDocuments']) && !empty($response['acceptedDocuments'])) {
                    foreach ($response['acceptedDocuments'] as $document) {
                        $uuid = $document['uuid'] ?? null;
                        $invoiceCodeNumber = $document['invoiceCodeNumber'] ?? null;

                        if (!isset($invoiceSkuMap[$invoiceCodeNumber])) {
                            Log::warning('Auto Submit E-Invoice - SKU not found in mapping', [
                                'invoiceCodeNumber' => $invoiceCodeNumber,
                            ]);
                            continue;
                        }

                        $data = $invoiceSkuMap[$invoiceCodeNumber];

                        // Get document details
                        $details = $this->myInvoisService->getDocumentDetails($uuid);

                        if (!isset($details['error']) && isset($details['longId'])) {
                            // Create DB record with VALID status
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
                            $this->info("✓ Invoice {$invoiceCodeNumber} submitted successfully (UUID: {$uuid})");
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
                            $this->warn("⚠ Invoice {$invoiceCodeNumber} submitted but status is Invalid: {$errorMessage}");
                        }
                    }
                }

                // Process rejected documents
                if (isset($response['rejectedDocuments']) && !empty($response['rejectedDocuments'])) {
                    foreach ($response['rejectedDocuments'] as $document) {
                        $invoiceCodeNumber = $document['invoiceCodeNumber'] ?? 'Unknown';
                        $error = $document['error'] ?? [];
                        $errorMessage = $error['message'] ?? 'Unknown error';
                        
                        $results['failed'][] = [
                            'sku' => $invoiceCodeNumber,
                            'error' => $errorMessage
                        ];
                        $this->error("✗ Invoice {$invoiceCodeNumber} was rejected: {$errorMessage}");
                    }
                }

                DB::commit();

                // Summary
                $this->info("");
                $this->info("=== SUMMARY ===");
                $this->info("Total invoices processed: " . count($eligibleInvoices));
                $this->info("Successful: " . count($results['successful']));
                $this->info("Failed: " . count($results['failed']));

                if (count($results['successful']) > 0) {
                    $this->info("");
                    $this->info("Successfully submitted invoices:");
                    foreach ($results['successful'] as $sku) {
                        $this->info("  - {$sku}");
                    }
                }

                if (count($results['failed']) > 0) {
                    $this->warn("");
                    $this->warn("Failed invoices:");
                    foreach ($results['failed'] as $failed) {
                        $this->warn("  - {$failed['sku']}: {$failed['error']}");
                    }
                }

                return count($results['failed']) > 0 ? 1 : 0;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error saving e-invoices: " . $e->getMessage());
                Log::error('Auto Submit E-Invoice - Failed to save', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Auto Submit E-Invoice Command Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
