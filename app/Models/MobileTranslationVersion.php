<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileTranslationVersion extends Model
{
    use HasFactory;
    protected $fillable = [
        'language_id',
        'version'
    ];

    protected $attributes = [
        'version' => 1
    ];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}
