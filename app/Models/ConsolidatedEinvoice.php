<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\MyInvoisService;
use Illuminate\Support\Facades\Log;

class ConsolidatedEinvoice extends Model
{
    use HasFactory;
    protected $guarded = [];

    const STATUS_VALID = 'Valid';
    const STATUS_INVALID = 'Invalid';
    const STATUS_PENDING = 'Pending';
    const STATUS_CANCELLED = 'Cancelled';

    protected $casts = [
        'submission_date' => 'datetime',
        'validated_time' => 'datetime',
    ];

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'consolidated_einvoice_invoices', 'consolidated_einvoice_id', 'invoice_id');
    }

    public function generateSku(): string 
    {
        $init_idx = 1;
        while (true) {
            $str_init_idx = (string) $init_idx;
            while (strlen($str_init_idx) < 4) { 
                $str_init_idx = '0'.$str_init_idx;
            }

            $sku = 'CONS - '.now()->format('ym').'/'.$str_init_idx;
            $exists = self::where('sku', $sku)->exists();

            if (! $exists) {
                break;
            }
            $init_idx++;
        }
        return $sku;
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    public function isInvalid(): bool
    {
        return $this->status === self::STATUS_INVALID;
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

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VALID);
    }

    public function scopeInvalid($query)
    {
        return $query->where('status', self::STATUS_INVALID);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('uuid')->whereNotNull('submission_date');
    }
}

