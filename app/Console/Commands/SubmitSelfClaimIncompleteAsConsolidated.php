<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\ConsolidatedEinvoice;
use App\Services\MyInvoisService;
use App\Services\EInvoiceXmlGenerateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubmitSelfClaimIncompleteAsConsolidated extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:submit-self-claim-incomplete-consolidated';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Submit invoices with self_claim_complete = false from the previous month (excluding status New and already submitted) as consolidated e-invoices';

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
     * Check if customer has all required fields for self-claim (same as AutoSubmitSelfClaimInvoices)
     *
     * @param \App\Models\Customer $customer
     * @return bool
     */
    protected function isSelfClaimComplete($customer): bool
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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Increase memory limit for large batch processing
        ini_set('memory_limit', '512M');
        
        // Get previous month date range
        $now = Carbon::now();
        $previousMonth = $now->copy()->subMonth();
        $startDate = $previousMonth->copy()->startOfMonth()->format('Y-m-d H:i:s');
        $endDate = $previousMonth->copy()->endOfMonth()->format('Y-m-d 23:59:59');
        
        $monthName = $previousMonth->format('F Y');
        $this->info("Finding invoices with self_claim_complete = false from {$monthName} that are not submitted and not status 'New'...");

        // Get invoices from previous month only, excluding status = 0 (New)
        // and excluding those already submitted (has einvoice OR in consolidatedEinvoice)
        $invoices = Invoice::whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 0) // Exclude status "New" (0)
            ->whereDoesntHave('einvoice') // Not already submitted as individual e-invoice
            ->whereDoesntHave('consolidatedEinvoices') // Not already in a consolidated e-invoice
            ->with('customer')
            ->get();

        if ($invoices->isEmpty()) {
            $this->warn("No invoices found for {$monthName} that meet the criteria.");
            $this->info("Criteria: date in {$monthName}, status != 0 (New), not already submitted as e-invoice, not in consolidated e-invoice");
            return 0;
        }

        $this->info("Found {$invoices->count()} invoice(s) to check.");

        // Filter invoices where self_claim_complete is false (computed from customer fields)
        $validInvoiceIds = [];
        $invalidInvoiceIds = [];

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            if (!$customer) {
                $invalidInvoiceIds[] = $invoice->id;
                $this->warn("Invoice #{$invoice->invoiceno} (ID: {$invoice->id}) excluded: no customer");
                continue;
            }

            // Check if self_claim_complete is false (customer missing required fields)
            if (!$this->isSelfClaimComplete($customer)) {
                // This invoice has self_claim_complete = false, include it
                $validInvoiceIds[] = $invoice->id;
            }
            // If self_claim_complete is true, skip it (those are handled by auto-submit command)
        }

        if (empty($validInvoiceIds)) {
            $this->info("No invoices found with self_claim_complete = false.");
            $this->info("All invoices either have complete customer data (self_claim_complete = true) or are missing customers.");
            return 0;
        }

        if (count($invalidInvoiceIds) > 0) {
            $this->warn(count($invalidInvoiceIds) . " invoice(s) were excluded due to missing customer data.");
        }

        $this->info("Found " . count($validInvoiceIds) . " invoice(s) with self_claim_complete = false.");
        $this->info("Proceeding with " . count($validInvoiceIds) . " valid invoice(s)...");
        $this->warn("Note: MyInvois has a 300 KB limit per document. Will automatically batch if needed.");

        // Start with a reasonable batch size to avoid memory issues
        // Test with a small sample first to estimate optimal batch size
        $allInvoiceIds = $validInvoiceIds;
        $batches = [];
        
        // Use a small test batch (50 invoices) to estimate XML size per invoice
        $testBatchSize = min(50, count($allInvoiceIds));
        $testInvoiceIds = array_slice($allInvoiceIds, 0, $testBatchSize);
        
        $this->info("Testing with {$testBatchSize} invoice(s) to estimate batch size...");
        
        try {
            $consolidatedTemp = new ConsolidatedEinvoice();
            $testSku = $consolidatedTemp->generateSku();
            $testConsolidated = new ConsolidatedEinvoice([
                'sku' => $testSku,
                'currency' => 'MYR',
            ]);
            
            $testXmlContent = $this->xmlGenerator->generateConsolidatedXml($testInvoiceIds, $testConsolidated, null);
            $testXmlSizeKB = strlen($testXmlContent) / 1024;
            $this->info("Test XML size: " . number_format($testXmlSizeKB, 2) . " KB for {$testBatchSize} invoice(s)");
            
            // Calculate average size per invoice
            $avgSizePerInvoice = $testXmlSizeKB / $testBatchSize;
            $this->info("Average size per invoice: " . number_format($avgSizePerInvoice, 2) . " KB");
            
            // Calculate optimal batch size (target 250 KB to be safe, leaving 50 KB margin)
            $targetSizeKB = 250;
            $suggestedBatchSize = max(1, floor($targetSizeKB / $avgSizePerInvoice * 0.9)); // 90% to be safe
            
            // Cap at reasonable limits
            $suggestedBatchSize = min($suggestedBatchSize, 200); // Max 200 invoices per batch
            $suggestedBatchSize = max($suggestedBatchSize, 10); // Min 10 invoices per batch
            
            $this->info("Calculated optimal batch size: {$suggestedBatchSize} invoices per batch");
            
            // Split into batches
            $batches = array_chunk($allInvoiceIds, $suggestedBatchSize);
            $this->info("Will create " . count($batches) . " consolidated e-invoice(s)");
            
        } catch (\Exception $e) {
            // If test fails (memory issue), use a conservative batch size
            $this->warn("Test batch failed: " . $e->getMessage());
            $this->warn("Using conservative batch size of 50 invoices per batch");
            $suggestedBatchSize = 50;
            $batches = array_chunk($allInvoiceIds, $suggestedBatchSize);
            $this->info("Will create " . count($batches) . " consolidated e-invoice(s)");
        }

        $successCount = 0;
        $failureCount = 0;
        $createdConsolidatedEinvoices = [];

        foreach ($batches as $batchIndex => $batchInvoiceIds) {
            $batchNumber = $batchIndex + 1;
            $totalBatches = count($batches);
            
            if ($totalBatches > 1) {
                $this->info("");
                $this->info("=== Processing Batch {$batchNumber}/{$totalBatches} (" . count($batchInvoiceIds) . " invoices) ===");
            }

            try {
                // Generate SKU
                $consolidatedTemp = new ConsolidatedEinvoice();
                $sku = $consolidatedTemp->generateSku();

                if ($totalBatches > 1) {
                    $this->info("Generated SKU: {$sku}");
                }

                // Create temporary consolidated model for XML generation (not saved)
                $consolidatedTemp = new ConsolidatedEinvoice([
                    'sku' => $sku,
                    'currency' => 'MYR',
                ]);

                // Generate consolidated XML
                if ($totalBatches > 1) {
                    $this->info("Generating XML for batch...");
                } else {
                    $this->info("Generating XML...");
                }
                $xmlContent = $this->xmlGenerator->generateConsolidatedXml($batchInvoiceIds, $consolidatedTemp, null);
                
                // Check XML size (MyInvois limit is 300 KB)
                $xmlSizeKB = strlen($xmlContent) / 1024;
                if ($totalBatches > 1) {
                    $this->info("XML size: " . number_format($xmlSizeKB, 2) . " KB");
                }
                
                if ($xmlSizeKB > 300) {
                    $this->error("XML size ({$xmlSizeKB} KB) exceeds MyInvois 300 KB limit!");
                    $this->warn("This batch is still too large. Further reducing batch size...");
                    $failureCount++;
                    continue;
                }

                // Submit to API
                $this->info("Submitting to MyInvois API...");
                $response = $this->myInvoisService->submitAndGetDetails($xmlContent, $sku, true);

                Log::info('Consolidated E-Invoice Submission (Self-Claim Incomplete Command)', [
                    'batch' => $totalBatches > 1 ? $batchNumber : null,
                    'total_batches' => $totalBatches > 1 ? $totalBatches : null,
                    'invoice_count' => count($batchInvoiceIds),
                    'xml_size_kb' => $xmlSizeKB,
                    'response_structure' => array_keys($response),
                    'has_submission' => isset($response['submission']),
                    'submission_response' => $response['submission'] ?? null,
                ]);

                // Check for rejected documents
                if (isset($response['submission']['rejectedDocuments']) && !empty($response['submission']['rejectedDocuments'])) {
                    $rejected = $response['submission']['rejectedDocuments'][0];
                    $errorMessage = $rejected['error']['message'] ?? 'Unknown error';
                    if ($totalBatches > 1) {
                        $this->error("Batch {$batchNumber} was rejected: {$errorMessage}");
                    } else {
                        $this->error("Document was rejected: {$errorMessage}");
                    }
                    
                    // If rejected due to size limit, suggest further batching
                    if (stripos($errorMessage, '300') !== false || stripos($errorMessage, 'size') !== false || stripos($errorMessage, 'KB') !== false) {
                        $this->warn("  → This is a size limit error. The calculated batch size may still be too large.");
                        $this->warn("  → Try reducing the number of invoices per batch further.");
                    }
                    
                    $failureCount++;
                    continue;
                }

                // Only save to DB if document was ACCEPTED
                if (isset($response['submission']['acceptedDocuments'][0])) {
                    $acceptedDoc = $response['submission']['acceptedDocuments'][0];
                    $uuid = $acceptedDoc['uuid'];

                    $this->info("Document accepted. UUID: {$uuid}");

                    // Get document details (with retry for validation status)
                    $details = $this->myInvoisService->getDocumentDetails($uuid);
                    
                    // If status is still pending, wait and retry (validation happens asynchronously)
                    $maxRetries = 3;
                    $retryDelay = 3; // seconds
                    $retryCount = 0;
                    
                    while ($retryCount < $maxRetries && 
                           (!isset($details['longId']) || empty($details['longId'])) && 
                           (!isset($details['validationResults']['status']) || $details['validationResults']['status'] === 'Pending')) {
                        $retryCount++;
                        $this->info("Waiting {$retryDelay} seconds for validation to complete (attempt {$retryCount}/{$maxRetries})...");
                        sleep($retryDelay);
                        $details = $this->myInvoisService->getDocumentDetails($uuid);
                    }

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

                        // Create consolidated einvoice record
                        $consolidatedEinvoice = ConsolidatedEinvoice::create([
                            'sku' => $sku,
                            'currency' => 'MYR',
                            'uuid' => $uuid,
                            'status' => $status,
                            'longId' => $details['longId'] ?? null,
                            'submission_date' => now(),
                            'validated_time' => $details['dateTimeValidated'] ?? null,
                        ]);

                        // Attach invoices to consolidated einvoice
                        $consolidatedEinvoice->invoices()->attach($batchInvoiceIds);

                        DB::commit();

                        if ($totalBatches > 1) {
                            $this->info("✓ Batch {$batchNumber} - Consolidated E-Invoice created successfully!");
                        } else {
                            $this->info("✓ Consolidated E-Invoice created successfully!");
                        }
                        $this->info("  ID: {$consolidatedEinvoice->id}");
                        $this->info("  SKU: {$sku}");
                        $this->info("  UUID: {$uuid}");
                        $this->info("  Status: {$status}");
                        $this->info("  Invoices attached: " . count($batchInvoiceIds));
                        
                        if ($status === ConsolidatedEinvoice::STATUS_INVALID) {
                            $this->warn("  Note: Status is Invalid. Check the MyInvois portal for validation errors.");
                            if (isset($details['validationResults']['errors'])) {
                                $this->warn("  Validation errors: " . json_encode($details['validationResults']['errors']));
                            }
                        }

                        $successCount++;
                        $createdConsolidatedEinvoices[] = [
                            'id' => $consolidatedEinvoice->id,
                            'sku' => $sku,
                            'uuid' => $uuid,
                            'status' => $status,
                            'invoice_count' => count($batchInvoiceIds),
                        ];
                    } catch (\Exception $e) {
                        DB::rollBack();
                        if ($totalBatches > 1) {
                            $this->error("Batch {$batchNumber} - Failed to save consolidated e-invoice after acceptance: " . $e->getMessage());
                        } else {
                            $this->error("Failed to save consolidated e-invoice after acceptance: " . $e->getMessage());
                        }
                        Log::error('Failed to save consolidated e-invoice after acceptance (Self-Claim Incomplete Command)', [
                            'batch' => $totalBatches > 1 ? $batchNumber : null,
                            'uuid' => $uuid,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $failureCount++;
                    }
                } else {
                    if ($totalBatches > 1) {
                        $this->error("Batch {$batchNumber} - No accepted documents in response.");
                    } else {
                        $this->error("No accepted documents in response.");
                    }
                    Log::error('Consolidated E-Invoice - No accepted documents (Self-Claim Incomplete Command)', [
                        'batch' => $totalBatches > 1 ? $batchNumber : null,
                        'response' => $response,
                    ]);
                    $failureCount++;
                }
            } catch (\Exception $e) {
                if ($totalBatches > 1) {
                    $this->error("Batch {$batchNumber} - Error: " . $e->getMessage());
                } else {
                    $this->error("Error: " . $e->getMessage());
                }
                Log::error('Submit Self-Claim Incomplete Invoices Command Failed', [
                    'batch' => $totalBatches > 1 ? $batchNumber : null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failureCount++;
            }
        }

        // Summary
        $this->info("");
        $this->info("=== SUMMARY ===");
        if (count($batches) > 1) {
            $this->info("Total batches processed: " . count($batches));
        }
        $this->info("Successful: {$successCount}");
        $this->info("Failed: {$failureCount}");
        
        if (count($invalidInvoiceIds) > 0) {
            $this->warn("Excluded due to missing customer data: " . count($invalidInvoiceIds));
        }

        if ($successCount > 0) {
            $this->info("");
            $this->info("Created Consolidated E-Invoices:");
            foreach ($createdConsolidatedEinvoices as $ce) {
                $this->info("  - {$ce['sku']} (ID: {$ce['id']}, Status: {$ce['status']}, Invoices: {$ce['invoice_count']})");
            }
        }

        return $failureCount > 0 ? 1 : 0;
    }
}
