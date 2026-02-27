<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CronJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronJob:updatedo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Delivery Order Billing Rate & Commission Rate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $result = app('App\Http\Controllers\scheduler')->updateDoRate();
        if($result){
            $result = 0;
        }else{
            $result = 1;
        }
        return $result;
    }
}
