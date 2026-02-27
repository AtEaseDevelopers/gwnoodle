<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    public $table = 'products';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'unit_code',
        'name',
        'price',           // Selling price
        'cost',           
        'status',
        'type',
        'classification_code',
        'carton_enabled',
        'units_per_carton'
    ];

    protected $casts = [
        'id' => 'integer',
        'code' => 'string',
        'name' => 'string',
        'price' => 'float',
        'cost' => 'float',      
        'status' => 'integer',
        'type' => 'integer',
        'classification_code' => 'string',
        'carton_enabled' => 'boolean',
        'units_per_carton' => 'integer'
    ];

    public static $rules = [
        'unit_code' => 'required|string|max:255',
        'name' => 'required|string|max:255',
        'price' => 'required|numeric',
        'cost' => 'nullable|numeric|min:0',  
        'status' => 'required',
        'type' => 'required',
        'carton_enabled' => 'boolean',
        'units_per_carton' => 'nullable|integer|min:1|required_if:carton_enabled,1',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];
    
    // Relationships
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
    
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    
    // NEW: Cost history relationship
    public function costHistory()
    {
        return $this->hasMany(ProductCost::class)->orderBy('created_at', 'desc');
    }
    
    // Helper to convert units to cartons (if enabled)
    public function convertToUnits($cartons)
    {
        if (!$this->carton_enabled) {
            return $cartons;
        }
        return $cartons * $this->units_per_carton;
    }
    
    // Helper to get profit margin
    public function getProfitMarginAttribute()
    {
        if (!$this->cost || $this->cost == 0) {
            return null;
        }
        return (($this->price - $this->cost) / $this->price) * 100;
    }
    
    // Helper to get profit amount
    public function getProfitAmountAttribute()
    {
        if (!$this->cost) {
            return null;
        }
        return $this->price - $this->cost;
    }

    public function getCurrentCostAttribute()
    {
        return $this->cost;
    }

    public function getCostAtDate($date)
    {
        $costAtDate = ProductCost::where('product_id', $this->id)
            ->where('created_at', '<=', $date)
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $costAtDate ? $costAtDate->cost : $this->cost;
    }

}