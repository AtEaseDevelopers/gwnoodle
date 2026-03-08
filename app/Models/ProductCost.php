<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class ProductCost extends Model
{
    use HasFactory;
    use LogsActivity;

    public $table = 'product_costs';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'product_id',
        'cost',                    // The cost price at that time
        'old_cost',                 // Previous cost (optional)
        'changed_by',               // User who changed it
        'remarks'                   // Why it changed
    ];

    protected $casts = [
        'product_id' => 'integer',
        'cost' => 'float',
        'old_cost' => 'float',
        'changed_by' => 'integer'
    ];

    /**
     * Get the product that owns this cost history
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who changed the cost
     */
    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}