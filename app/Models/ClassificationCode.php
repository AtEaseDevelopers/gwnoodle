<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
    ];

      public static function getDescription(string $code): ?string
    {
        // Find the classification code
        $classification = self::where('code', $code)->first();
        
        // Return description if found, otherwise return null
        return $classification ? $classification->description : null;
    }
    
}
