<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class TaskTransfer
 * @package App\Models
 * @version July 20, 2023, 6:54 pm +08
 *
 * @property integer $from_driver_id
 * @property integer $to_driver_id
 * @property integer $task_id
 */
class TaskTransfer extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'task_transfers';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';



    public $fillable = [
        'from_driver_id',
        'to_driver_id',
        'task_id'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'datetime',
        'from_driver_id' => 'integer',
        'to_driver_id' => 'integer',
        'task_id' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'date' => 'nullable|nullable',
        'from_driver_id' => 'required',
        'to_driver_id' => 'required',
        'task_id' => 'required',
        'created_at' => 'nullable|nullable',
        'updated_at' => 'nullable|nullable'
    ];

    public function task()
    {
        return $this->belongsTo(\App\Models\Task::class, 'task_id', 'id');
    }

    public function fromdriver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'from_driver_id', 'id');
    }

    public function todriver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'to_driver_id', 'id');
    }

    public function getDateAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y H:i:s');
    }

    
}
