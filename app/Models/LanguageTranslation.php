<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageTranslation extends Model
{
    protected $fillable = ['language_id', 'page', 'display_text','key','translated_text'];

    public function language()
    {
        return $this->belongsTo(Language::class);
    }
}