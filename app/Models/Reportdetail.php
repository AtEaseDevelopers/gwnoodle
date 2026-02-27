<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Reportdetail
 * @package App\Models
 * @version August 14, 2022, 2:19 pm UTC
 *
 * @property \App\Models\Report $report
 * @property integer $report_id
 * @property string $name
 * @property string $title
 * @property string $type
 * @property string $data
 * @property integer $sequence
 * @property integer $status
 * @property string $STR_UDF1
 * @property string $STR_UDF2
 * @property string $STR_UDF3
 * @property integer $INT_UDF1
 * @property integer $INT_UDF2
 * @property integer $INT_UDF3
 */
class Reportdetail extends Model
{
    // use SoftDeletes;

    use HasFactory;

    public $table = 'reportdetails';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'report_id',
        'name',
        'title',
        'type',
        'data',
        'sequence',
        'status',
        'STR_UDF1',
        'STR_UDF2',
        'STR_UDF3',
        'INT_UDF1',
        'INT_UDF2',
        'INT_UDF3'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'report_id' => 'integer',
        'name' => 'string',
        'title' => 'string',
        'type' => 'string',
        'data' => 'string',
        'sequence' => 'integer',
        'status' => 'integer',
        'STR_UDF1' => 'string',
        'STR_UDF2' => 'string',
        'STR_UDF3' => 'string',
        'INT_UDF1' => 'integer',
        'INT_UDF2' => 'integer',
        'INT_UDF3' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'report_id' => 'required',
        'name' => 'required|string|max:255',
        'title' => 'required|string|max:255',
        'type' => 'required|string|max:255',
        'sequence' => 'required|unique:reportdetails,sequence,NULL,id,report_id,',
        'status' => 'required',
        'STR_UDF1' => 'nullable|string',
        'STR_UDF2' => 'nullable|string',
        'STR_UDF3' => 'nullable|string',
        'INT_UDF1' => 'nullable|integer',
        'INT_UDF2' => 'nullable|integer',
        'INT_UDF3' => 'nullable|integer',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function report()
    {
        return $this->belongsTo(\App\Models\Report::class, 'report_id');
    }
}
