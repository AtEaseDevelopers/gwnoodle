<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class sender extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }

    public function send()
    {
        dd('1');
    }

    public function email($to_name, $to_email, $to_cc, $subject, $body)
    {
        try{
            // $to_name = 'Ogbonna Vitalis';
            // $to_email = 'chajian981124@gmail.com';
            // $subject = 'Multiline test Mail';
            // $body = date("Y-m-d H:i:s");
            if($to_cc == ''){
                $data = array('name'=>$to_name, 'body' => $body);
                Mail::send('senders.mail', $data, function($message) use ($to_name, $to_email, $subject) {
                    $message->to($to_email, $to_name)
                    ->subject($subject);
                    $message->from(env('MAIL_USERNAME'),env('APP_NAME').' Bot');
                });
            }else{
                $data = array('name'=>$to_name, 'body' => $body);
                Mail::send('senders.mail', $data, function($message) use ($to_name, $to_email, $subject, $to_cc) {
                    $message->to($to_email, $to_name)
                    ->subject($subject)
                    ->cc($to_cc);
                    $message->from(env('MAIL_USERNAME'),env('APP_NAME').' Bot');
                });
            }
            return false;
        }catch(Exception $e){
            Log::error($e);
            return true;
        }
    }
}
