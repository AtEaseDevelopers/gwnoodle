<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MyInvoisService
{
    protected $clientId;
    protected $clientSecret;
    protected $apiUrl;
    protected $portalUrl;
    protected $tokenCacheKey = 'myinvois_access_token';

    public function __construct()
    {
        // Support both config locations for backward compatibility
        $this->clientId = config('e-invoices.client_id') ?? env('MYINVOIS_CLIENT_ID');
        $this->clientSecret = config('e-invoices.client_secret') ?? env('MYINVOIS_CLIENT_SECRET');
        $this->apiUrl = config('e-invoices.url') ?? env('MYINVOIS_API_URL', 'https://api.myinvois.hasil.gov.my');
        $this->portalUrl = config('e-invoices.portal_url') ?? env('MYINVOIS_PORTAL_URL', 'https://myinvois.hasil.gov.my');
    }

    public function authenticate($scope = null)
    {
        $actualScope = 'InvoicingAPI';
        
        // Get supplier TIN to include in cache key (TIN is tied to the authentication token)
        $supplierTIN = config('e-invoices.supplier_tin') ?? env('E_INVOICE_SUPPLIER_TIN');
        $supplierTIN = trim($supplierTIN, '"\' ');
        // Remove curly quotes and other quote variants
        $supplierTIN = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{201F}\x{2033}\x{2036}"\'"\s]/u', '', $supplierTIN);
        
        // Include TIN in cache key to ensure token matches the TIN used in documents
        $cacheKey = $this->tokenCacheKey . '_' . $actualScope . '_' . md5($this->clientId . '_' . $supplierTIN);
        
        Log::info('MyInvois Authentication - Starting', [
            'client_id' => $this->clientId,
            'client_id_hash' => md5($this->clientId),
            'supplier_tin' => $supplierTIN,
            'scope' => $actualScope,
            'cache_key' => $cacheKey,
            'api_url' => $this->apiUrl,
        ]);
        
        if (Cache::has($cacheKey)) {
            $cachedToken = Cache::get($cacheKey);
            Log::info('MyInvois Authentication - Access Token from Cache', [
                'scope' => $actualScope,
                'cache_key' => $cacheKey,
                'client_id' => $this->clientId,
                'access_token' => substr($cachedToken, 0, 50) . '...' . substr($cachedToken, -10),
                'token_length' => strlen($cachedToken),
            ]);
            return $cachedToken;
        }

        try {
            Log::info('MyInvois Authentication - Requesting New Token', [
                'client_id' => $this->clientId,
                'scope' => $actualScope,
                'endpoint' => $this->apiUrl . '/connect/token',
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'en',
            ])->asForm()->post($this->apiUrl . '/connect/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => $actualScope,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['access_token'])) {
                    $expiresIn = $data['expires_in'] ?? 3600;
                    $accessToken = $data['access_token'];
                    Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn - 60));
                    
                    Log::info('MyInvois Authentication - Access Token Retrieved', [
                        'scope' => $actualScope,
                        'cache_key' => $cacheKey,
                        'client_id' => $this->clientId,
                        'access_token' => substr($accessToken, 0, 50) . '...' . substr($accessToken, -10),
                        'token_length' => strlen($accessToken),
                        'expires_in' => $expiresIn,
                    ]);
                    
                    return $accessToken;
                }
            }

            Log::error('MyInvois Authentication Failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            throw new \Exception('Failed to authenticate with MyInvois API: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('MyInvois Authentication Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getAccessToken($scope = null)
    {
        return $this->authenticate($scope);
    }

    public function makeRequest($method, $endpoint, $data = [], $scope = null)
    {
        $token = $this->getAccessToken($scope);
        
        $supplierTIN = config('e-invoices.supplier_tin') ?? env('E_INVOICE_SUPPLIER_TIN');
        $supplierTIN = trim($supplierTIN, '"\' ');
        
        Log::info('MyInvois makeRequest - Request Details', [
            'method' => $method,
            'endpoint' => $endpoint,
            'client_id' => $this->clientId,
            'supplier_tin' => $supplierTIN,
            'token_preview' => substr($token, 0, 50) . '...' . substr($token, -10),
        ]);
        
        $response = Http::timeout(15)->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'Authorization' => 'Bearer ' . $token,
        ])->{strtolower($method)}($this->apiUrl . $endpoint, $data);

        if ($response->successful()) {
            return $response->json();
        }

        $responseBody = $response->body();
        $responseJson = json_decode($responseBody, true);
        
        Log::error('MyInvois API Request Failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'client_id' => $this->clientId,
            'supplier_tin' => $supplierTIN,
            'response_body' => $responseBody,
            'parsed_error' => $responseJson['error'] ?? null,
        ]);

        throw new \Exception('MyInvois API request failed: ' . $responseBody);
    }

    /**
     * Validate TIN (Tax Identification Number)
     */
    public function validateTIN($tin, $idType, $idValue)
    {
        try {
            $endpoint = "/api/v1.0/taxpayer/validate/{$tin}?idType={$idType}&idValue={$idValue}";
            return $this->makeRequest('GET', $endpoint);
        } catch (\Exception $e) {
            Log::error('TIN Validation Failed', [
                'tin' => $tin,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Submit documents to MyInvois
     */
    public function submitDocuments(array $documents)
    {
        try {
            $token = $this->getAccessToken();
            
            $supplierTIN = config('e-invoices.supplier_tin') ?? env('E_INVOICE_SUPPLIER_TIN');
            $supplierTIN = trim($supplierTIN, '"\' ');
            // Remove curly quotes and other quote variants to match authentication cache key
            $supplierTIN = preg_replace('/[\x{201C}\x{201D}\x{201E}\x{201F}\x{2033}\x{2036}"\'"\s]/u', '', $supplierTIN);
            
            Log::info('MyInvois submitDocuments - Access Token and TIN Info', [
                'access_token' => substr($token, 0, 50) . '...' . substr($token, -10),
                'token_length' => strlen($token),
                'documents_count' => count($documents),
                'client_id' => $this->clientId,
                'supplier_tin_from_config' => $supplierTIN,
                'supplier_tin_length' => strlen($supplierTIN),
                'supplier_tin_hex' => bin2hex($supplierTIN),
            ]);
            
            $payload = [
                'documents' => $documents
            ];
            
            $endpoint = '/api/v1.0/documentsubmissions';
            $response = $this->makeRequest('POST', $endpoint, $payload);
            
            Log::info('MyInvois submitDocuments - API Response Received', [
                'response_structure' => array_keys($response),
                'has_accepted' => isset($response['acceptedDocuments']),
                'has_rejected' => isset($response['rejectedDocuments']),
                'accepted_count' => isset($response['acceptedDocuments']) ? count($response['acceptedDocuments']) : 0,
                'rejected_count' => isset($response['rejectedDocuments']) ? count($response['rejectedDocuments']) : 0,
                'full_response' => $response,
            ]);
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Document Submission Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get document details by UUID
     */
    public function getDocumentDetails($uuid, $maxRetries = 15, $delay = 3)
    {
        try {
            $endpoint = "/api/v1.0/documents/{$uuid}/details";
            $retryCount = 0;

            Log::info('MyInvois getDocumentDetails - Starting', [
                'uuid' => $uuid,
                'max_retries' => $maxRetries,
                'delay' => $delay,
            ]);

            while ($retryCount < $maxRetries) {
                if ($retryCount > 0) {
                    sleep($delay);
                }

                try {
                    Log::info('MyInvois getDocumentDetails - Attempt', [
                        'uuid' => $uuid,
                        'retry_count' => $retryCount + 1,
                    ]);

                    $response = $this->makeRequest('GET', $endpoint);
                    
                    Log::info('MyInvois getDocumentDetails - Response Received', [
                        'uuid' => $uuid,
                        'retry_count' => $retryCount + 1,
                        'response_keys' => array_keys($response),
                        'full_response' => $response,
                    ]);
                    
                    // Check validation results
                    if (isset($response['validationResults'])) {
                        $validationResults = $response['validationResults'];
                        Log::info('MyInvois getDocumentDetails - Validation Results', [
                            'uuid' => $uuid,
                            'validation_status' => $validationResults['status'] ?? null,
                            'validation_results' => $validationResults,
                        ]);

                        if ($validationResults['status'] === 'Invalid') {
                            $validationSteps = $validationResults['validationSteps'] ?? [];
                            Log::warning('MyInvois getDocumentDetails - Validation Failed', [
                                'uuid' => $uuid,
                                'validation_steps' => $validationSteps,
                            ]);

                            foreach ($validationSteps as $step) {
                                if ($step['status'] === 'Invalid') {
                                    $error = $step['error']['innerError'][0]['error'] ?? null;
                                    if ($error) {
                                        Log::error('MyInvois getDocumentDetails - Validation Error Found', [
                                            'uuid' => $uuid,
                                            'error' => $error,
                                            'step' => $step,
                                        ]);
                                        return ['error' => $error];
                                    }
                                }
                            }
                        }
                    }

                    // Check if longId is available
                    $longId = $response['longId'] ?? null;
                    Log::info('MyInvois getDocumentDetails - Checking longId', [
                        'uuid' => $uuid,
                        'has_longId' => !empty($longId),
                        'longId' => $longId,
                        'response_keys' => array_keys($response),
                    ]);

                    if (!empty($longId) || !empty($response['status'])) {
                        $result = [
                            'uuid' => $response['uuid'] ?? null,
                            'submissionUid' => $response['submissionUid'] ?? null,
                            'longId' => $longId,
                            'internalId' => $response['internalId'] ?? null,
                            'typeName' => $response['typeName'] ?? null,
                            'typeVersionName' => $response['typeVersionName'] ?? null,
                            'issuerTin' => $response['issuerTin'] ?? null,
                            'issuerName' => $response['issuerName'] ?? null,
                            'receiverId' => $response['receiverId'] ?? null,
                            'receiverName' => $response['receiverName'] ?? null,
                            'dateTimeIssued' => isset($response['dateTimeIssued']) 
                                ? Carbon::parse($response['dateTimeIssued'])
                                    ->setTimezone('Asia/Kuala_Lumpur')
                                    ->format('Y-m-d H:i:s')
                                : null,
                            'dateTimeReceived' => isset($response['dateTimeReceived']) 
                                ? Carbon::parse($response['dateTimeReceived'])
                                    ->setTimezone('Asia/Kuala_Lumpur')
                                    ->format('Y-m-d H:i:s')
                                : null,
                            'dateTimeValidated' => isset($response['dateTimeValidated']) 
                                ? Carbon::parse($response['dateTimeValidated'])
                                    ->setTimezone('Asia/Kuala_Lumpur')
                                    ->format('Y-m-d H:i:s')
                                : null,
                            'totalExcludingTax' => $response['totalExcludingTax'] ?? null,
                            'totalDiscount' => $response['totalDiscount'] ?? null,
                            'totalNetAmount' => $response['totalNetAmount'] ?? null,
                            'totalPayableAmount' => $response['totalPayableAmount'] ?? null,
                            'status' => $response['status'] ?? null,
                            'cancelDateTime' => isset($response['cancelDateTime']) 
                                ? Carbon::parse($response['cancelDateTime'])
                                    ->setTimezone('Asia/Kuala_Lumpur')
                                    ->format('Y-m-d H:i:s')
                                : null,
                            'rejectRequestDateTime' => isset($response['rejectRequestDateTime']) 
                                ? Carbon::parse($response['rejectRequestDateTime'])
                                    ->setTimezone('Asia/Kuala_Lumpur')
                                    ->format('Y-m-d H:i:s')
                                : null,
                            'documentStatusReason' => $response['documentStatusReason'] ?? null,
                            'createdByUserId' => $response['createdByUserId'] ?? null,
                            'validationResults' => $response['validationResults'] ?? null,
                        ];
                        Log::info('MyInvois getDocumentDetails - Success', [
                            'uuid' => $uuid,
                            'has_longId' => !empty($longId),
                            'status' => $result['status'],
                        ]);
                        return $result;
                    } else {
                        Log::warning('MyInvois getDocumentDetails - longId Not Available Yet', [
                            'uuid' => $uuid,
                            'retry_count' => $retryCount + 1,
                            'max_retries' => $maxRetries,
                            'response' => $response,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Continue retrying if it's a temporary error
                    Log::warning('Document details retry', [
                        'uuid' => $uuid,
                        'attempt' => $retryCount + 1,
                        'error' => $e->getMessage()
                    ]);
                }

                $retryCount++;
            }

            Log::error('MyInvois getDocumentDetails - Max Retries Reached', [
                'uuid' => $uuid,
                'max_retries' => $maxRetries,
            ]);

            return ['error' => 'Failed to retrieve longId after multiple attempts'];
        } catch (\Exception $e) {
            Log::error('Failed to get document details', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Update document state (e.g., cancel document)
     */
    public function updateDocumentState($uuid, $status, $reason = null)
    {
        try {
            $endpoint = "/api/v1.0/documents/state/{$uuid}/state";
            $payload = [
                'status' => $status,
            ];
            
            if ($reason) {
                $payload['reason'] = $reason;
            }

            return $this->makeRequest('PUT', $endpoint, $payload);
        } catch (\Exception $e) {
            Log::error('Document State Update Failed', [
                'uuid' => $uuid,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel document
     */
    public function cancelDocument($uuid, $reason)
    {
        return $this->updateDocumentState($uuid, 'cancelled', $reason);
    }

    /**
     * Get document (XML or JSON format)
     * 
     * @param string $uuid Document UUID
     * @param string $format Document format ('XML' or 'JSON')
     * @param string|null $longId Optional longId to use instead of uuid
     * @return array|string Document content
     */
    public function getDocument($uuid, $format = 'XML', $longId = null)
    {
        try {
            $formatLower = strtolower($format);
            $endpoints = [];
            
            if ($longId) {
                $endpoints[] = "/api/v1.0/documents/{$longId}?format={$formatLower}";
                $endpoints[] = "/api/v1.0/documents/{$longId}?format={$format}";
            }
            
            $endpoints[] = "/api/v1.0/documents/{$uuid}?format={$formatLower}";
            $endpoints[] = "/api/v1.0/documents/{$uuid}?format={$format}";
            $endpoints[] = "/api/v1.0/documents/{$uuid}/download?format={$formatLower}";
            $endpoints[] = "/api/v1.0/documents/{$uuid}/download?format={$format}";
            
            Log::info('MyInvois getDocument - Request', [
                'uuid' => $uuid,
                'longId' => $longId,
                'format' => $format,
                'endpoints_to_try' => $endpoints,
            ]);
            
            $lastError = null;
            foreach ($endpoints as $endpoint) {
                try {
                    $response = $this->makeRequest('GET', $endpoint);
                    
                    Log::info('MyInvois getDocument - Success', [
                        'uuid' => $uuid,
                        'longId' => $longId,
                        'format' => $format,
                        'endpoint' => $endpoint,
                    ]);
                    
                    return $response;
                } catch (\Exception $e) {
                    $lastError = $e;
                    Log::warning('MyInvois getDocument - Endpoint Failed', [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
            
            throw $lastError ?? new \Exception('All endpoints failed');
        } catch (\Exception $e) {
            Log::error('Failed to get document', [
                'uuid' => $uuid,
                'longId' => $longId,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate validation link for document
     */
    public function generateValidationLink($uuid, $longId)
    {
        return "{$this->portalUrl}/{$uuid}/share/{$longId}";
    }

    /**
     * Generate QR code image from validation link
     * Uses online QR code generator API to create QR code from validation URL
     */
    public function getQRCode($uuid, $longId = null)
    {
        try {
            if (!$uuid || !$longId) {
                return null;
            }

            $validationLink = $this->generateValidationLink($uuid, $longId);
            
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($validationLink);
            
            try {
                $response = Http::timeout(10)->get($qrCodeUrl);
                
                if ($response->successful()) {
                    $imageData = base64_encode($response->body());
                    return [
                        'image' => $imageData,
                        'content_type' => 'image/png',
                        'validation_link' => $validationLink,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to generate QR code from online service', [
                    'uuid' => $uuid,
                    'longId' => $longId,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to generate QR code', [
                'uuid' => $uuid,
                'longId' => $longId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Prepare document for submission
     */
    public function prepareDocument($xmlContent, $codeNumber)
    {
        return [
            'format' => 'XML',
            'document' => base64_encode($xmlContent),
            'documentHash' => hash('sha256', $xmlContent),
            'codeNumber' => $codeNumber,
        ];
    }

    /**
     * Submit e-invoice with XML generation helper
     * This method combines XML generation and submission for convenience
     * 
     * @param string $xmlContent The XML content to submit
     * @param string $codeNumber The invoice code number (SKU)
     * @return array Response from API with accepted/rejected documents
     */
    public function submitEInvoice($xmlContent, $codeNumber)
    {
        $document = $this->prepareDocument($xmlContent, $codeNumber);
        return $this->submitDocuments([$document]);
    }

    /**
     * Submit multiple e-invoices with XML generation helper
     * 
     * @param array $invoices Array of ['xml' => string, 'codeNumber' => string]
     * @return array Response from API with accepted/rejected documents
     */
    public function submitMultipleEInvoices(array $invoices)
    {
        $documents = [];
        foreach ($invoices as $invoice) {
            $documents[] = $this->prepareDocument($invoice['xml'], $invoice['codeNumber']);
        }
        return $this->submitDocuments($documents);
    }

    /**
     * Complete e-invoice workflow: submit and get details
     * Submits the invoice and automatically retrieves document details including UUID and longId
     * 
     * @param string $xmlContent The XML content to submit
     * @param string $codeNumber The invoice code number (SKU)
     * @param bool $waitForDetails Whether to wait for document details after submission
     * @return array Complete submission result with document details
     */
    public function submitAndGetDetails($xmlContent, $codeNumber, $waitForDetails = true)
    {
        $response = $this->submitEInvoice($xmlContent, $codeNumber);
        
        $result = [
            'submission' => $response,
            'documents' => [],
        ];

        if ($waitForDetails && isset($response['acceptedDocuments'])) {
            foreach ($response['acceptedDocuments'] as $document) {
                $uuid = $document['uuid'];
                $details = $this->getDocumentDetails($uuid);
                
                $result['documents'][] = [
                    'invoiceCodeNumber' => $document['invoiceCodeNumber'],
                    'uuid' => $uuid,
                    'details' => $details,
                    'validationLink' => isset($details['longId']) 
                        ? $this->generateValidationLink($uuid, $details['longId']) 
                        : null,
                ];
            }
        }

        return $result;
    }
}

