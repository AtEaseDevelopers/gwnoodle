<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    public function getTotalSales(Request $request)
    {
        $data = $request->all();
        $datefrom = $data['datefrom'];
        $dateto = $data['dateto'];
        $driver = $data['driver'];
        $customer = $data['customer'];
        $by = $data['by'];
        $rawResult = DB::select('call ice_spDSBD_TotalSales(\''.$datefrom.'\',\''.$dateto.'\',\''.$driver.'\',\''.$customer.'\',\''.$by.'\')')[0];
        $result = str_replace('"{','{',str_replace('}"','}',str_replace('\"','"',str_replace('\\\"','"',str_replace(']"',']',str_replace('"[','[',$rawResult->data))))));
        return $result;
    }

    public function getTotalSalesQty(Request $request)
    {
        $data = $request->all();
        $datefrom = $data['datefrom'];
        $dateto = $data['dateto'];
        $driver = $data['driver'];
        $customer = $data['customer'];
        $by = $data['by'];
        $rawResult = DB::select('call ice_spDSBD_TotalSalesQty(\''.$datefrom.'\',\''.$dateto.'\',\''.$driver.'\',\''.$customer.'\',\''.$by.'\')')[0];
        $result = str_replace('"{','{',str_replace('}"','}',str_replace('\"','"',str_replace('\\\"','"',str_replace(']"',']',str_replace('"[','[',$rawResult->data))))));
        return $result;
    }
}
