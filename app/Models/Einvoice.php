<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MyInvoisService;
use App\Services\EInvoiceXmlGenerateService;
use Illuminate\Support\Facades\Log;

class Einvoice extends Model
{
    use HasFactory;
    protected $guarded = [];

    const STATUS_VALID = 'Valid';
    const STATUS_INVALID = 'Invalid';

    protected $casts = [
        'submission_date' => 'datetime',
        'validated_time' => 'datetime',
        'with_sg_gst' => 'boolean',
    ];

    public function debitNotes()
    {
        return $this->belongsToMany(DebitNote::class, 'debit_note_einvoice');
    }

    public function creditNotes()
    {
        return $this->belongsToMany(CreditNote::class, 'credit_note_einvoice');
    }

    public function consolidatedEinvoice()
    {
        return $this->belongsToMany(ConsolidatedEinvoice::class, 'credit_note_consolidated_einvoice');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_batch_id', 'id');
    }

    public function generateSku(?int $invoiceBatchId = null, bool $forceUnique = false): string 
    {
        if ($invoiceBatchId !== null) {
            $existingEinvoices = self::where('invoice_batch_id', $invoiceBatchId)
                ->orderBy('created_at', 'asc')
                ->get();
            
            if ($existingEinvoices->count() > 0) {
                $firstSku = $existingEinvoices->first()->sku;
                
                if (preg_match('/^(.+?)-(\d+)$/', $firstSku, $matches)) {
                    $baseSku = $matches[1];
                } else {
                    $baseSku = $firstSku;
                }
                
                $maxSuffix = 0;
                $hasBaseSku = false;
                foreach ($existingEinvoices as $einvoice) {
                    if ($einvoice->sku === $baseSku) {
                        $hasBaseSku = true;
                    } elseif (preg_match('/^' . preg_quote($baseSku, '/') . '-(\d+)$/', $einvoice->sku, $suffixMatches)) {
                        $suffixValue = $suffixMatches[1];
                        if (strlen($suffixValue) <= 6) {
                            $suffix = (int)$suffixValue;
                            if ($suffix > 0 && $suffix <= 9999 && $suffix > $maxSuffix) {
                                $maxSuffix = $suffix;
                            }
                        }
                    }
                }
                
                if ($hasBaseSku || $maxSuffix > 0) {
                    return $baseSku . '-' . ($maxSuffix + 1);
                }
                
                return $baseSku . '-2';
            } else {
                // No existing e-invoices for this invoice, generate base SKU
                $init_idx = 1;
                while (true) {
                    $str_init_idx = (string) $init_idx;
                    while (strlen($str_init_idx) < 4) { 
                        $str_init_idx = '0'.$str_init_idx;
                    }

                    $baseSku = 'EINV - '.now()->format('ym').'/'.$str_init_idx;
                    $existsBase = self::where('sku', $baseSku)->exists();

                    if (! $existsBase) {
                        $suffixEinvoices = self::where('invoice_batch_id', $invoiceBatchId)
                            ->where('sku', 'like', $baseSku . '-%')
                            ->get();
                        
                        if ($suffixEinvoices->count() > 0) {
                            $maxSuffix = 0;
                            foreach ($suffixEinvoices as $einvoice) {
                                if (preg_match('/^' . preg_quote($baseSku, '/') . '-(\d+)$/', $einvoice->sku, $suffixMatches)) {
                                    $suffixValue = $suffixMatches[1];
                                    if (strlen($suffixValue) <= 6) {
                                        $suffix = (int)$suffixValue;
                                        if ($suffix > 0 && $suffix <= 9999 && $suffix > $maxSuffix) {
                                            $maxSuffix = $suffix;
                                        }
                                    }
                                }
                            }
                            if ($maxSuffix > 0) {
                                return $baseSku . '-' . ($maxSuffix + 1);
                            }
                            return $baseSku . '-2';
                        }
                        
                        return $baseSku;
                    }
                    $init_idx++;
                }
            }
        }
        
        $init_idx = 1;
        while (true) {
            $str_init_idx = (string) $init_idx;
            while (strlen($str_init_idx) < 4) { 
                $str_init_idx = '0'.$str_init_idx;
            }

            $sku = 'EINV - '.now()->format('ym').'/'.$str_init_idx;
            $exists = self::where('sku', $sku)->exists();

            if (! $exists) {
                break;
            }
            $init_idx++;
        }
        return $sku;
    }

    /**
     * Check if invoice is valid
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    /**
     * Check if invoice is invalid
     */
    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
    }

    /**
     * Check if invoice has been submitted
     */
    public function isSubmitted(): bool
    {
        return !empty($this->uuid) && !empty($this->submission_date);
    }

    /**
     * Get validation link for this invoice
     */
    public function getValidationLink(): ?string
    {
        if (!$this->uuid || !$this->longId) {
            return null;
        }

        $service = new MyInvoisService();
        return $service->generateValidationLink($this->uuid, $this->longId);
    }

    /**
     * Submit this e-invoice to MyInvois
     * 
     * @param float|null $currencyRate Currency exchange rate if needed
     * @param bool $waitForDetails Whether to wait for document details after submission
     * @return array Submission result
     */
    public function submitToMyInvois(?float $currencyRate = null, bool $waitForDetails = true): array
    {
        try {
            $xmlGenerator = new EInvoiceXmlGenerateService();
            $service = new MyInvoisService();

            // Generate XML
            $xmlContent = $xmlGenerator->generateEInvoiceXml($this, $currencyRate);

            // Submit and get details
            $result = $service->submitAndGetDetails($xmlContent, $this->sku, $waitForDetails);

            // Update invoice with submission details
            if (isset($result['documents'][0])) {
                $document = $result['documents'][0];
                $this->update([
                    'uuid' => $document['uuid'],
                    'longId' => $document['details']['longId'] ?? null,
                    'status' => isset($document['details']['longId']) ? self::STATUS_VALID : self::STATUS_INVALID,
                    'submission_date' => now(),
                    'validated_time' => isset($document['details']['dateTimeValidated']) 
                        ? $document['details']['dateTimeValidated'] 
                        : null,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to submit e-invoice', [
                'einvoice_id' => $this->id,
                'sku' => $this->sku,
                'error' => $e->getMessage()
            ]);
            
            // Update status to invalid on failure
            $this->update(['status' => self::STATUS_INVALID]);
            
            throw $e;
        }
    }

    /**
     * Cancel this e-invoice in MyInvois
     * 
     * @param string $reason Cancellation reason
     * @return array Cancellation result
     */
    public function cancel(string $reason): array
    {
        if (!$this->uuid) {
            throw new \Exception('Cannot cancel e-invoice: UUID not found');
        }

        try {
            $service = new MyInvoisService();
            $result = $service->cancelDocument($this->uuid, $reason);

            // Update status
            if (isset($result['status'])) {
                $this->update(['status' => $result['status']]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to cancel e-invoice', [
                'einvoice_id' => $this->id,
                'uuid' => $this->uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Refresh document details from MyInvois
     * 
     * @return array Document details
     */
    public function refreshDetails(): array
    {
        if (!$this->uuid) {
            throw new \Exception('Cannot refresh details: UUID not found');
        }

        try {
            $service = new MyInvoisService();
            $details = $service->getDocumentDetails($this->uuid);

            if (isset($details['error'])) {
                return $details;
            }

            $status = $this->status;
            if (isset($details['validationResults']['status'])) {
                $validationStatus = $details['validationResults']['status'];
                if ($validationStatus === 'Invalid') {
                    $status = self::STATUS_INVALID;
                } elseif ($validationStatus === 'Valid' && isset($details['longId'])) {
                    $status = self::STATUS_VALID;
                }
            } elseif (isset($details['longId'])) {
                $status = self::STATUS_VALID;
            }

            $this->update([
                'longId' => $details['longId'] ?? $this->longId,
                'status' => $status,
                'validated_time' => $details['dateTimeValidated'] ?? $this->validated_time,
            ]);

            return $details;
        } catch (\Exception $e) {
            Log::error('Failed to refresh e-invoice details', [
                'einvoice_id' => $this->id,
                'uuid' => $this->uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Scope to filter valid invoices
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VALID);
    }

    /**
     * Scope to filter invalid invoices
     */
    public function scopeInvalid($query)
    {
        return $query->where('status', self::STATUS_INVALID);
    }

    /**
     * Scope to filter submitted invoices
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('uuid')->whereNotNull('submission_date');
    }

    /**
     * Scope to filter unsubmitted invoices
     */
    public function scopeUnsubmitted($query)
    {
        return $query->whereNull('uuid')->orWhereNull('submission_date');
    }
}

