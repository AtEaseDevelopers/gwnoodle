<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Helper extends Model
{
    public static function member_url($route=""){
        return config('app.url')."/".$route;
    }

    public static function admin_url($route=""){
        return config('app.url')."/admin/".$route;
    }

    public static function agent_url($route=""){
        return config('app.url')."/sales/".$route;
    }

    public static function query_params($query=[]){
        return "?".http_build_query($query);
    }

    public static function generateRandomString($length = 30, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
    
        return $randomString;
    }
    
    public static function numberToWordsMy($amount)
    {
        // Round to two decimals
        $amount = number_format($amount, 2, '.', '');
        // Split into Ringgit (integer) and Sen (decimal) parts
        list($ringgitPart, $senPart) = explode('.', $amount);
    
        // Convert both parts to words
        $ringgitWords = Helper::convertToWords((int)$ringgitPart);
        $senWords     = Helper::convertToWords((int)$senPart);
    
        // Handle scenarios: if the amount has no Sen (00), or if it's purely integer
        if ((int)$senPart === 0) {
            // Example: 46 => “RINGGIT MALAYSIA FORTY SIX ONLY”
            return strtoupper("RINGGIT MALAYSIA {$ringgitWords} ONLY");
        } else {
            // Example: 46.50 => “RINGGIT MALAYSIA FORTY SIX AND FIFTY SEN ONLY”
            return strtoupper("RINGGIT MALAYSIA {$ringgitWords} AND {$senWords} SEN ONLY");
        }
    }


    public static function convertToWords($number)
    {
        $units = ['', 'one','two','three','four','five','six','seven','eight','nine',
                  'ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen',
                  'seventeen','eighteen','nineteen'];
        $tens  = ['', '', 'twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
    
        if ($number == 0) {
            return '';
        }
    
        $words = '';
    
        // Handle thousands
        if (($number / 1000) > 0) {
            
            $thousand = Helper::convertToWords((int)($number / 1000));
            
            $words .= ($thousand !== '') ? $thousand . ' thousand ' : '';
            $number %= 1000;
        }
    
        // Handle hundreds
        if (($number / 100) > 0) {
            $hundred = Helper::convertToWords((int)($number / 100));
            
            $words .= ($hundred !== '') ? $hundred . ' hundred ' : '';
            $number %= 100;
        }
    
        // Handle tens and units
        if ($number > 0) {
            if ($number < 20) {
                $words .= $units[$number];
            } else {
                $words .= $tens[(int)($number / 10)];
                if (($number % 10) > 0) {
                    $words .= ' ' . $units[$number % 10];
                }
            }
        }
        return trim($words);
    }

}