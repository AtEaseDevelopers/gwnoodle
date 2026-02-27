<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'is_default', 'is_active'];

    public function translations()
    {
        return $this->hasMany(LanguageTranslation::class);
    }

    public static function defaultLanguage()
    {
        return static::where('is_default', true)->first();
    }

    public function getTranslationsByGroup()
    {
        return $this->translations->groupBy('group');
    }

    public function mobileTranslations()
    {
        return $this->hasMany(MobileTranslation::class);
    }

    public function mobileTranslationVersion()
    {
        return $this->hasOne(MobileTranslationVersion::class);
    }

}