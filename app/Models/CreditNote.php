<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MyInvoisService;
use App\Services\EInvoiceXmlGenerateService;
use Illuminate\Support\Facades\Log;

class CreditNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    const STATUS_VALID = 'Valid';
    const STATUS_INVALID = 'Invalid';

    protected $casts = [
        'submission_date' => 'datetime',
        'validated_time' => 'datetime',
    ];

    public function einvoices()
    {
        return $this->belongsToMany(Einvoice::class, 'credit_note_einvoice');
    }

    public function consolidatedEinvoice()
    {
        return $this->belongsToMany(ConsolidatedEinvoice::class, 'credit_note_consolidated_einvoice');
    }

    public function generateSku(): string
    {
        $init_idx = 1;
        while (true) {
            $str_init_idx = (string) $init_idx;
            while (strlen($str_init_idx) < 4) {
                $str_init_idx = '0' . $str_init_idx;
            }

            $sku = 'ECN-' . now()->format('ym') . '/' . $str_init_idx;
            $exists = self::where('sku', $sku)->exists();

            if (!$exists) {
                return $sku;
            }
            $init_idx++;
        }
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isSubmitted(): bool
    {
        return !empty($this->uuid) && !empty($this->submission_date);
    }

    public function getValidationLink(): ?string
    {
        if (!$this->uuid || !$this->longId) {
            return null;
        }

        $service = new MyInvoisService();
        return $service->generateValidationLink($this->uuid, $this->longId);
    }
}
