<?php

namespace App\Http\Controllers;

use App\DataTables\ReportDataTable;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Repositories\ReportRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Report;
use Response;
use App\Models\Reportdetail;
use Illuminate\Support\Facades\DB;
use \Exception;
use Mockery\Expectation;
use App\Models\paymentdetail;
use App\Models\Driver;
use App\Models\Lorry;
use App\Models\Code;
use App\Models\DailyInventoryBalance;
use App\Models\Vendor;
use Illuminate\Support\Facades\Crypt;
use Barryvdh\DomPDF\Facade\Pdf;
use File;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Product;
use App\Exports\SellerInformationExport;
use App\Exports\MonthlySaleReport;
use App\Exports\DailySaleReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportController extends AppBaseController
{
    /** @var ReportRepository $reportRepository*/
    private $reportRepository;

    public function __construct(ReportRepository $reportRepo)
    {
        $this->reportRepository = $reportRepo;
    }

    /**
     * Display a listing of the Report.
     *
     * @param ReportDataTable $reportDataTable
     *
     * @return Response
     */
    public function index(ReportDataTable $reportDataTable)
    {
        return $reportDataTable->render('reports.index');
    }

    /**
     * Show the form for creating a new Report.
     *
     * @return Response
     */
    public function create()
    {
        return view('reports.create');
    }

    /**
     * Store a newly created Report in storage.
     *
     * @param CreateReportRequest $request
     *
     * @return Response
     */
    public function store(CreateReportRequest $request)
    {
        $input = $request->all();

        $report = $this->reportRepository->create($input);

        Flash::success(__('report.report_saved_successfully'));

        return redirect(route('reports.index'));
    }

    /**
     * Display the specified Report.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {

        $report = $this->reportRepository->find($id);

        if (empty($report)) {
            Flash::error(__('report.report_not_found'));

            return redirect(route('reports.index'));
        }
        
        if($report->sqlvalue == 'Monthly_Total_Sales_Quantity_Reports'){
            $products = Product::select('id','name')->get();
            $agents = Agent::select('id','name')->get();
            $customerGroup = Code::where('code', 'customer_group')->select('value','description')->get();
            return view('reports.monthly-total-sale-show',compact('report','products','agents','customerGroup'));
            
        }
        
        if($report->sqlvalue == 'Daily_Total_Sales_Quantity_Reports'){
            $products = Product::select('id','name')->get();
            $derviers = Driver::select('id','name')->get();
            $lorrys = Lorry::select('id','lorryno')->get();
            $customerGroup = Code::where('code', 'customer_group')->select('value','description')->get();
            return view('reports.daily-total-sale-show',compact('report','products','derviers','lorrys','customerGroup'));
            
        }

        $reportdetails = Reportdetail::where('report_id',$id)->where('status','1')->orderBy('sequence','asc')->get()->toarray();
        $c = 0;
        foreach($reportdetails as $reportdetail){
            try{
                $t = $reportdetail['data'];
                if($reportdetail['data']==null){
                    $reportdetail['data'] = '{}';
                }
                // $data = json_decode(json_encode(DB::select($reportdetail['data'])), true);
                $data = DB::select(DB::raw($reportdetail['data']));
                $array = array();
                foreach($data as $value){
                    $value=array_values((array)$value);
                    $array[$value[1]] = $value[0];
                  }
                $reportdetails[$c]['data'] = $array;
            }
            catch(Exception $e) {
                if($reportdetails[$c]['type'] == 'multiselect' or $reportdetails[$c]['type'] == 'dropdown'){
                    $data = json_decode($reportdetails[$c]['data'], true);
                }else{
                    $data = $reportdetails[$c]['data'];
                }
                $reportdetails[$c]['data'] = $data;
            }
            $c = $c + 1;
        }   
        return view('reports.show')->with('reportdetails', $reportdetails)->with('report', $report);
    }

    /**
     * Show the form for editing the specified Report.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $report = $this->reportRepository->find($id);

        if (empty($report)) {
            Flash::error(__('report.report_not_found'));

            return redirect(route('reports.index'));
        }

        return view('reports.edit')->with('report', $report);
    }

    /**
     * Update the specified Report in storage.
     *
     * @param int $id
     * @param UpdateReportRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateReportRequest $request)
    {
        $report = $this->reportRepository->find($id);

        if (empty($report)) {
            Flash::error(__('report.report_not_found'));

            return redirect(route('reports.index'));
        }

        $report = $this->reportRepository->update($request->all(), $id);

        Flash::error(__('report.report_updated_successfully'));

        return redirect(route('reports.index'));
    }

    /**
     * Remove the specified Report from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $report = $this->reportRepository->find($id);

        if (empty($report)) {
            Flash::error(__('report.report_not_found'));

            return redirect(route('reports.index'));
        }

        $this->reportRepository->delete($id);

        Flash::error(__('report.report_deleted_successfully'));

        return redirect(route('reports.index'));
    }

    public function getCustomers($id)
    {
        if($id == "All"){
            $customers = Customer::select('id', 'company')->get(); 
        }
        else{
            $customers = Customer::whereRaw("FIND_IN_SET(?, `group`)", [$id])->select('id', 'company')->get();
        }

        if($customers->isNotEmpty()){
            $options = '<option value="%">ALL</option>';
            foreach ($customers as $customer) {
                $options .= '<option value="' . $customer->id . '">' . $customer->company . '</option>';
            }
        }
        else{
          $options = '<option value="">Select</option>';  
        }
        
        return response()->json(['options' => $options]);
    }
    
    public function run(Request $request)
    {
        $data = $request->all();
        $report_id = $data['_report_id'];
        $sp = Report::where('id',$report_id)->pluck('sqlvalue')->first();
        if($sp == 'SELLER_INFORMATION_RECORD'){
            $param = '';
            foreach ($data as $key => $value) {
                if($key != '_token' && $key != '_report_id'){
                    if(is_array($value)){
                        $array = '';
                        foreach($value as $arr){
                            $array = $array.$arr.',';
                        }
                        $array = rtrim($array, ",");
                        $param = $param . $key . '=' . $array . '&';
                    }else{
                        $param = $param . $key . '=' . $value . '&';
                    }
                }
            }
            return redirect(route('seller_information_record').'?'.$param); 

            /*if($request->report_type == 'Run Report PDF'){
                return redirect(route('seller_information_record').'?'.$param); 
            }
            else{
                return redirect(route('seller_information_record_excel').'?'.$param); 
            }*/
        }
        if($sp == 'CUSTOMER_STATEMENT_OF_ACCOUNT'){
            $param = '';
            foreach ($data as $key => $value) {
                if($key != '_token' && $key != '_report_id'){
                    if(is_array($value)){
                        $array = '';
                        foreach($value as $arr){
                            $array = $array.$arr.',';
                        }
                        $array = rtrim($array, ",");
                        $param = $param . $key . '=' . $array . '&';
                    }else{
                        $param = $param . $key . '=' . $value . '&';
                    }
                }
            }
            return redirect(route('customer_statement_of_account').'?'.$param);   
        }
        
         if($sp == 'DAILY_SALES_REPORT'){
            $param = '';
            foreach ($data as $key => $value) {
                if($key != '_token' && $key != '_report_id'){
                    if(is_array($value)){
                        $array = '';
                        foreach($value as $arr){
                            $array = $array.$arr.',';
                        }
                        $array = rtrim($array, ",");
                        $param = $param . $key . '=' . $array . '&';
                    }else{
                        $param = $param . $key . '=' . $value . '&';
                    }
                }
            }
             return redirect(route('daily_sales_report_excel').'?'.$param); 
        }
        
        $param = '';
        foreach ($data as $key => $value) {
            if($key != '_token'){
                if(is_array($value)){
                    $array = '';
                    foreach($value as $arr){
                        $array = $array.$arr.',';
                    }
                    $array = rtrim($array, ",");
                    $param = $param . "'" . $array . "',";
                }else{
                    
                    
                    if($key== 'datefrom')
                        $value .=  ' 00:00:00';
                    else
                    if($key== 'dateto')
                    {
                        $value .=  ' 23:59:59';
                    }
                    
                    $param = $param . "'" . $value . "',";
                }
            }
        }
        $param = rtrim($param, ",");
        $query = 'call '.$sp."(".$param.");";
        $result = DB::select($query)[0];
        $result = $result->ID;
        return redirect(route('showreport', $result));
    }
    
    public function monthlysalereport(Request $request)
    {
        // return $request->all();
        $monthYear = $request->month_year;
        $startDate = Carbon::parse($monthYear)->startOfMonth();
        $endDate = Carbon::parse($monthYear)->endOfMonth();
        
        if($request->products == '%'){
            $products = Product::pluck('id')->toArray();
        } else {
            $products = $request->products;
        }
        
        if($request->agent == '%'){
            $agents = Agent::pluck('id')->toArray();
        } else {
            $agents = [$request->agent];
        }
        
        if($request->customergroup == '%'){
            $p_customer = Customer::pluck('id')->toArray();
        } else {
            $p_customer = Customer::whereRaw("FIND_IN_SET(?, `group`)", [$request->customergroup])->pluck('id')->toArray();
        }
            
        $invoices = Invoice::whereBetween('date', [$startDate, $endDate])
                ->whereHas('invoicedetail', function ($query) use ($products) {
                    $query->whereIn('product_id', $products);
                })
                ->where(function($q) use ($agents, $p_customer) {
                    $q->whereIn('agent_id', $agents)->orWhereIn('customer_id', $p_customer); // Filter by customer IDs
                })
                ->with('customer', 'agent', 'invoicedetail.product')
                ->get();

        $reportData = [];
    
        foreach ($invoices as $invoice) {
            $customerName = $invoice->customer->company ?? '';
            $agentName = $invoice->agent->name ?? '';
            $day = Carbon::parse($invoice->date)->day; // Day of the month
    
            if (!isset($reportData[$customerName][$agentName])) {
                // Initialize empty array for each customer-agent combination
                $reportData[$customerName][$agentName] = array_fill(1, 31, ['quantity' => 0, 'price' => 0]);
                $reportData[$customerName][$agentName]['Total Quantity'] = 0;
                $reportData[$customerName][$agentName]['Total Price'] = 0;
                $reportData[$customerName][$agentName]['Average Price'] = 0;
            }
    
            foreach ($invoice->invoicedetail as $detail) {
                $reportData[$customerName][$agentName][$day]['quantity'] += $detail->quantity;
                $reportData[$customerName][$agentName][$day]['price'] += $detail->totalprice;
    
                // Add to totals
                $reportData[$customerName][$agentName]['Total Quantity'] += $detail->quantity;
                $reportData[$customerName][$agentName]['Total Price'] += $detail->totalprice;
            }
    
            // Calculate average price for this invoice
            if ($reportData[$customerName][$agentName]['Total Quantity'] > 0) {
                $reportData[$customerName][$agentName]['Average Price'] =
                    $reportData[$customerName][$agentName]['Total Price'] /
                    $reportData[$customerName][$agentName]['Total Quantity'];
            }
        }
    
        // Export the data to Excel
        return Excel::download(new MonthlySaleReport($reportData, $monthYear), 'monthly_sale_report.xlsx');
    }

    public function daily_sales_report_excel(Request $request){
        // return $request->all();
        
        $day = $request->date;
        
        
        $date = Carbon::parse($day)->format('Y-m-d');

        // Get product IDs based on the request
        //$products = $request->products != '%' ? Product::pluck('id')->toArray() : $request->products;

        // Get driver IDs based on the request
        $p_driver = explode(',',$request['driver']) ?? [];
        
        if($p_driver[0] == '%'){
            $p_drivers = 'All';
            $p_driver = Driver::pluck('id')->toArray();
        }else{
            $p_drivers = implode(', ',Driver::whereIn('id',$p_driver)->pluck('name')->toArray());
        }
        
        //$lorrys = $request->lorrys != '%' ? Lorry::pluck('id')->toArray() : $request->lorrys;

        // Get customer IDs based on the customer group
       /* $p_customer = $request->customergroup == '%'
            ? Customer::pluck('id')->toArray() 
            : Customer::whereRaw("FIND_IN_SET(?, `group`)", [$request->customergroup])->pluck('id')->toArray();
            
        $payment_term = $request->payment_term == null ? null : ($request->payment_term == '%' ? [1, 2] : [$request->payment_term]);*/

        // Filter invoices based on the selected criteria
        $invoices = Invoice::whereIn('driver_id', $p_driver)
                ->whereDate('date', $date)
                /*->whereHas('invoicedetail')
                //->whereIn('customer_id', $p_customer)*/
                ->with('customer', 'driver', 'invoicedetail.product');
                
        $invoices = $invoices->pluck('id');
        $invoiceDetails = InvoiceDetail::whereIn('invoice_id', $invoices)->orderBy('invoice_id', 'desc')->get();
        
        return Excel::download(new DailySaleReportExport($invoiceDetails,$p_driver,$date), 'DailySaleReport.xlsx');    
    }

    public function report($id)
    {
        if($id == 0){
            abort(404);
        }
        try{
            $result = DB::select('select * from reportlists where id='.$id)[0];
            $report = Report::where('id',$result->report_id)->select('name','id')->first();
            return view('reports.report')->with('result', $result)->with('report', $report);
        }
        catch(Exception $e){
            abort(404);
        }
    }

    public function newreport()
    {
        return view('reports.newreport');
    }

    public function customer_statement_of_account(Request $request)
    {
        $p_customer = $request['p_customer'] ?? '';
        $p_datefrom = ($request['p_datefrom'] . ' 00:00:00') ?? '';
        $p_dateto = ($request['p_dateto'] . ' 23:59:59') ?? '';

        $p_customers = Customer::where('id',$p_customer)->first();

        $params = array(
            'p_customer' => $request['p_customer'],
            'p_customers' => $p_customers,
            'p_datefrom' => $request['p_datefrom'],
            'p_dateto' => $request['p_dateto']
        );

        $invoices = Invoice::leftJoin('invoice_details', function($join) {
            $join->on('invoices.id', '=', 'invoice_details.invoice_id');
          })
        ->where('invoices.customer_id',$p_customer)
        ->where('invoices.date', '>=', $p_datefrom)
        ->where('invoices.date', '<=', $p_dateto)
        ->select('invoices.date',DB::raw('invoices.invoiceno as reference'),DB::raw('"Sales" as descr'),DB::raw('sum(invoice_details.totalprice) as debit'),DB::raw('null as credit'))
        ->groupBy('invoices.id','invoices.date','invoices.invoiceno');
        $payments = InvoicePayment::where('customer_id',$p_customer)
        ->where('approve_at', '>=', $p_datefrom)
        ->where('approve_at', '<=', $p_dateto)
        ->select(DB::raw('approve_at as date'),DB::raw('DATE_FORMAT(approve_at,"%Y%m%d") as reference'),DB::raw('"Payment For Account" as descr'),DB::raw('null as debit'),DB::raw('amount as credit'));
        $data = $invoices->union($payments)->orderBy('date','asc')->get()->toArray();
        $params['total_debit'] = $invoices->count();
        $params['sum_debit'] = $invoices->sum('debit');
        $params['total_credit'] = $payments->count();
        $params['sum_credit'] = $payments->sum('amount');
        $pdf = Pdf::loadView('reports.customer_statement_of_account', array('data'=>$data,'params'=>$params));
        return $pdf->setPaper('a4', 'portrait')->setOptions(['isPhpEnabled' => true])->set_option('isRemoteEnabled', true)->stream('SELLER_INFORMATION_RECORD.pdf');
        return view('reports.customer_statement_of_account')->with('invoices', $invoices)->with('params',$params);
    }

    public function seller_information_record(Request $request)
    {
        $p_driver = explode(',',$request['p_driver']) ?? [];
        $p_agent = explode(',',$request['p_agent']) ?? [];
        $p_customer_group = explode(',',$request['p_customer_group']) ?? [];
        $p_customer = explode(',',$request['p_customer']) ?? [];
        $p_datefrom = ($request['p_datefrom'] . ' 00:00:00') ?? '';
        $p_dateto = ($request['p_dateto'] . ' 23:59:59') ?? '';

        if($p_driver[0] == '%'){
            $p_drivers = 'All';
            $p_driver = Driver::pluck('id')->toArray();
        }else{
            $p_drivers = implode(', ',Driver::whereIn('id',$p_driver)->pluck('name')->toArray());
        }
        
        if($p_agent[0] == '%'){
            $p_agents = 'All';
            $p_agent = Agent::pluck('id')->toArray();
        }else{
            $p_agents = implode(', ',Agent::whereIn('id',$p_agent)->pluck('name')->toArray());
        }
        
        if($p_customer_group[0] == '%'){
            $p_customer_groups = 'All';
        }else{
            $p_customer_groups = implode(', ',Code::whereIn('value',$p_customer_group)->where('code', 'customer_group')->pluck('description')->toArray());
        }

        if($p_customer[0] == '%'){
            $p_customers = 'All';
            if($p_customer_group[0] == '%'){
                $p_customer = Customer::pluck('id')->toArray();
            }
            else{
                $p_customer = Customer::whereRaw("FIND_IN_SET(?, `group`)", [$p_customer_group[0]])->pluck('id')->toArray();
            }
        }else{
            $p_customers = implode(', ',Customer::whereIn('id',$p_customer)->pluck('company')->toArray());
        }

        $params = array(
            'p_driver' => $request['p_driver'],
            'p_drivers' => $p_drivers,
            'p_agent' => $request['p_agent'],
            'p_agents' => $p_agents,
            'p_customer_group' => $request['p_customer_group'],
            'p_customer_groups' => $p_customer_groups,
            'p_customer' => $request['p_customer'],
            'p_customers' => $p_customers,
            'p_datefrom' => $request['p_datefrom'],
            'p_dateto' => $request['p_dateto']
        );

        $invoices = Invoice::whereIn('driver_id',$p_driver)
        ->whereIn('agent_id',$p_agent)
        ->when(!empty($p_customer), function ($query) use ($p_customer) {
            return $query->whereIn('customer_id', $p_customer);
        }) 
        ->where('date', '>=', $p_datefrom)
        ->where('date', '<=', $p_dateto)
        ->with('customer')
        ->withSum('invoicedetail','totalprice')
        ->with('invoicedetail.product')
        ->orderby('date','asc')
        ->get()->toArray();
        $pdf = Pdf::loadView('reports.seller_information_record', array('invoices'=>$invoices,'params'=>$params));
        return $pdf->setPaper('a4', 'portrait')->setOptions(['isPhpEnabled' => true])->stream('SELLER_INFORMATION_RECORD.pdf');
    }
    
    public function seller_information_record_excel(Request $request)
    {
        $p_driver = explode(',',$request['p_driver']) ?? [];
        $p_agent = explode(',',$request['p_agent']) ?? [];
        $p_customer_group = explode(',',$request['p_customer_group']) ?? [];
        $p_customer = explode(',',$request['p_customer']) ?? [];
        $p_datefrom = ($request['p_datefrom'] . ' 00:00:00') ?? '';
        $p_dateto = ($request['p_dateto'] . ' 23:59:59') ?? '';

        if($p_driver[0] == '%'){
            $p_drivers = 'All';
            $p_driver = Driver::pluck('id')->toArray();
        }else{
            $p_drivers = implode(', ',Driver::whereIn('id',$p_driver)->pluck('name')->toArray());
        }
        
        if($p_agent[0] == '%'){
            $p_agents = 'All';
            $p_agent = Agent::pluck('id')->toArray();
        }else{
            $p_agents = implode(', ',Agent::whereIn('id',$p_agent)->pluck('name')->toArray());
        }
        
        if($p_customer_group[0] == '%'){
            $p_customer_groups = 'All';
        }else{
            $p_customer_groups = implode(', ',Code::whereIn('value',$p_customer_group)->where('code', 'customer_group')->pluck('description')->toArray());
        }

        if($p_customer[0] == '%'){
            $p_customers = 'All';
            if($p_customer_group[0] == '%'){
                $p_customer = Customer::pluck('id')->toArray();
            }
            else{
                $p_customer = Customer::whereRaw("FIND_IN_SET(?, `group`)", [$p_customer_group[0]])->pluck('id')->toArray();
            }
        }else{
            $p_customers = implode(', ',Customer::whereIn('id',$p_customer)->pluck('company')->toArray());
        }

        $params = array(
            'p_driver' => $request['p_driver'],
            'p_drivers' => $p_drivers,
            'p_agent' => $request['p_agent'],
            'p_agents' => $p_agents,
            'p_customer_group' => $request['p_customer_group'],
            'p_customer_groups' => $p_customer_groups,
            'p_customer' => $request['p_customer'],
            'p_customers' => $p_customers,
            'p_datefrom' => $request['p_datefrom'],
            'p_dateto' => $request['p_dateto']
        );

        $invoices = Invoice::whereIn('driver_id',$p_driver)
        ->whereIn('agent_id',$p_agent)
        ->when(!empty($p_customer), function ($query) use ($p_customer) {
            return $query->whereIn('customer_id', $p_customer);
        }) 
        ->where('date', '>=', $p_datefrom)
        ->where('date', '<=', $p_dateto)
        ->with('customer')
        ->withSum('invoicedetail','totalprice')
        ->with('invoicedetail.product')
        ->orderby('date','asc')
        ->get()
        ->toArray();
        
        return Excel::download(new SellerInformationExport($invoices, $params), 'SELLER_INFORMATION_RECORD.xlsx');
    }

    public function dailysalereport(Request $request){
        // return $request->all();
        
        $day = $request->day;
        $date = Carbon::parse($day)->format('Y-m-d');

        // Get product IDs based on the request
        //$products = $request->products != '%' ? Product::pluck('id')->toArray() : $request->products;

        // Get driver IDs based on the request
        $p_driver = explode(',',$request['p_driver']) ?? [];
        if($p_driver[0] == '%'){
            $p_drivers = 'All';
            $p_driver = Driver::pluck('id')->toArray();
        }else{
            $p_drivers = implode(', ',Driver::whereIn('id',$p_driver)->pluck('name')->toArray());
        }
        
        //$lorrys = $request->lorrys != '%' ? Lorry::pluck('id')->toArray() : $request->lorrys;

        // Get customer IDs based on the customer group
       /* $p_customer = $request->customergroup == '%'
            ? Customer::pluck('id')->toArray() 
            : Customer::whereRaw("FIND_IN_SET(?, `group`)", [$request->customergroup])->pluck('id')->toArray();
            
        $payment_term = $request->payment_term == null ? null : ($request->payment_term == '%' ? [1, 2] : [$request->payment_term]);*/

        // Filter invoices based on the selected criteria
        $invoices = Invoice::whereIn('driver_id', $p_driver)
                ->whereDate('date', $date)
                ->whereHas('invoicedetail')
                ->whereIn('customer_id', $p_customer)
                ->with('customer', 'driver', 'invoicedetail.product');
            
        
        $invoices = $invoices->pluck('id');
        
        $invoiceDetails = InvoiceDetail::whereIn('invoice_id', $invoices)->orderBy('invoice_id', 'desc')->get();
        
        return Excel::download(new DailySaleReportExport($invoiceDetails,$p_driver,$date), 'DailySaleReport.xlsx');    
    }
    public function paymentoneview($id)
    {
        $id = Crypt::decrypt($id);
        if($id == 0){
            abort(404);
        }

        $paymentdetail = paymentdetail::where('id',$id)->with('driver:id,name,employeeid,grouping,ic,phone,bankdetails1,bankdetails2')->get();
        // dd($paymentdetail[0]->id);
        if (empty($paymentdetail)) {
            abort(404);
        }

        try{
            $result_do = DB::select('select * from reportlists where id='.$paymentdetail[0]->do_report)[0];
            $result_claim = DB::select('select * from reportlists where id='.$paymentdetail[0]->claim_report)[0];
            $result_compound = DB::select('select * from reportlists where id='.$paymentdetail[0]->comp_report)[0];
            $result_advance = DB::select('select * from reportlists where id='.$paymentdetail[0]->adv_report)[0];
            $result_loan = DB::select('select * from reportlists where id='.$paymentdetail[0]->loanpay_report)[0];
            $result_bonus = DB::select('select * from reportlists where id='.$paymentdetail[0]->bonus_report)[0];
            $paymentdetail_datefrom = $paymentdetail[0]->datefrom;
            $pdo_id = DB::select('select * from paymentdetails where datefrom <= \''.date_format(date_create($paymentdetail_datefrom),'Y-m-d').'\' and driver_id='.$paymentdetail[0]->driver_id.' and month=\''.$paymentdetail[0]->month.'\' and id<>'.$paymentdetail[0]->id.' order by dateto desc');
            // $result_pdo = DB::select('select * from reportlists where id='.$paymentdetail->do_report)[0];
            if (!empty($pdo_id)) {
                $result_pdo = DB::select('select * from reportlists where id='.$pdo_id[0]->do_report)[0];
            }else{
                $result_pdo = DB::select('select * from reportlists where id='.$paymentdetail[0]->do_report)[0];
                $result_pdo->data = '[{"COLUMNS": [{"title": "Date"}, {"title": "D/O No"}, {"title": "Destination"}, {"title": "Source"}, {"title": "Vendor"}, {"title": "Product"}, {"title": "Tonnage Delivered"}, {"title": "Billing Rate"}, {"title": "Delivered Product Price"}, {"title": "Loading/Unloading"}, {"title": "Tol"}, {"title": "Earn Before Comm."}, {"title": "Employee ID"}, {"title": "Driver Name"}, {"title": "Lorry Number"}, {"title": "Commission Weightage"}, {"title": "Commission Rate"}, {"title": "Amt for Commission"}, {"title": "Commission Percentage"}, {"title": "Driver\"s Comm."}, {"title": "Earn After Comm."}], "DATA": []}]';
            }
            return view('reports.paymentoneview')
            ->with('paymentdetail', $paymentdetail[0])
            ->with('result_do', $result_do)
            ->with('result_claim', $result_claim)
            ->with('result_compound', $result_compound)
            ->with('result_advance', $result_advance)
            ->with('result_loan', $result_loan)
            ->with('result_bonus', $result_bonus)
            ->with('result_pdo', $result_pdo);
        }
        catch(Exception $e){
            abort(404);
        }
    }

    // public function massDownloadPaymentoneviewPDF(Request $request)
    public function massDownloadPaymentoneviewPDF($ids)
    {
        $key = $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'];
        $zip = new ZipArchive;
        $fileName = $key.'.zip';
        if(file_exists($fileName)){
            unlink($fileName);
        }

        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE)
        {

            // $data = $request->all();
            // $ids = explode(",",$data['ids']);
            // $ids = $data['ids'];
            // $ids = [894];
            $ids = explode(",",$ids);
            foreach($ids as $id){
                $value = $this->getPaymentoneviewPDF(Crypt::encrypt($id),$key);
                // dd($value);
                $relativeNameInZipFile = basename($value);
                $zip->addFile($value, $relativeNameInZipFile);
            }

            $zip->close();
        }
        if(Storage::disk('public')->has($key)){
            Storage::disk('public')->deleteDirectory($key);
        }
    	// Download the generated zip
        return response()->download(public_path($fileName));
    }

    public function getPaymentoneviewPDF($id,$function){
        $id = Crypt::decrypt($id);
        if($id == 0){
            dd('0');
        }

        $paymentdetail = paymentdetail::where('id',$id)->with('driver:id,name,employeeid,grouping,ic,phone,bankdetails1,bankdetails2')->get();
        if (empty($paymentdetail)) {
            dd('Empty');
        }

        $result_do = DB::select('select * from reportlists where id='.$paymentdetail[0]->do_report)[0];
        $result_claim = DB::select('select * from reportlists where id='.$paymentdetail[0]->claim_report)[0];
        $result_compound = DB::select('select * from reportlists where id='.$paymentdetail[0]->comp_report)[0];
        $result_advance = DB::select('select * from reportlists where id='.$paymentdetail[0]->adv_report)[0];
        $result_loan = DB::select('select * from reportlists where id='.$paymentdetail[0]->loanpay_report)[0];
        $result_bonus = DB::select('select * from reportlists where id='.$paymentdetail[0]->bonus_report)[0];
        $paymentdetail_datefrom = $paymentdetail[0]->datefrom;
        $pdo_id = DB::select('select * from paymentdetails where datefrom <= \''.date_format(date_create($paymentdetail_datefrom),'Y-m-d').'\' and driver_id='.$paymentdetail[0]->driver_id.' and month=\''.$paymentdetail[0]->month.'\' and id<>'.$paymentdetail[0]->id.' order by dateto desc');
        if (!empty($pdo_id)) {
            $result_pdo = DB::select('select * from reportlists where id='.$pdo_id[0]->do_report)[0];
        }else{
            $result_pdo = DB::select('select * from reportlists where id='.$paymentdetail[0]->do_report)[0];
            $result_pdo->data = '[{"COLUMNS": [{"title": "Date"}, {"title": "D/O No"}, {"title": "Destination"}, {"title": "Source"}, {"title": "Vendor"}, {"title": "Product"}, {"title": "Tonnage Delivered"}, {"title": "Billing Rate"}, {"title": "Delivered Product Price"}, {"title": "Loading/Unloading"}, {"title": "Tol"}, {"title": "Earn Before Comm."}, {"title": "Employee ID"}, {"title": "Driver Name"}, {"title": "Lorry Number"}, {"title": "Commission Weightage"}, {"title": "Commission Rate"}, {"title": "Amt for Commission"}, {"title": "Commission Percentage"}, {"title": "Driver\"s Comm."}, {"title": "Earn After Comm."}], "DATA": []}]';
        }
        $pdf = Pdf::loadView('reports.paymentoneviewtemplate', array(
            'paymentdetail'=> $paymentdetail[0],
            'result_do'=> $result_do,
            'result_claim'=> $result_claim,
            'result_compound'=> $result_compound,
            'result_advance'=> $result_advance,
            'result_loan'=> $result_loan,
            'result_bonus'=> $result_bonus,
            'result_pdo'=> $result_pdo
        ));

        if($function == 'download'){
            return $pdf->setPaper('a4', 'landscape')->setOptions(['isPhpEnabled' => true])->download($paymentdetail[0]->driver->name.'_'.$paymentdetail[0]->datefrom.'_'.$paymentdetail[0]->dateto.'.pdf');
        }elseif($function == 'view'){
            return $pdf->setPaper('a4', 'landscape')->setOptions(['isPhpEnabled' => true])->stream($paymentdetail[0]->driver->name.'_'.$paymentdetail[0]->datefrom.'_'.$paymentdetail[0]->dateto.'.pdf');
        }else{
            if(!Storage::disk('public')->has($function)){
                Storage::disk('public')->makeDirectory($function);
            }
            $path = storage_path('app/public').'/'.$function.'/'.$paymentdetail[0]->driver->name.'_'.$paymentdetail[0]->datefrom.'_'.$paymentdetail[0]->dateto.'.pdf';
            $filename = $paymentdetail[0]->driver->name.'_'.$paymentdetail[0]->datefrom.'_'.$paymentdetail[0]->dateto.'.pdf';
            Storage::disk('public')->put($function.'/'.$filename,$pdf->setPaper('a4', 'landscape')->output());
            return $path;
        }

    }

    public function vendoroneview($id,$datefrom,$dateto)
    {
        $id = Crypt::decrypt($id);
        if($id == 0){
            abort(404);
        }

        $vendor = Vendor::find($id);
        $vendorbillings = DB::select('call spGetVendorBilling(\''.$id.'\',\''.$datefrom.'\',\''.$dateto.'\');');
        // dd($vendorbillings);

        try{
            return view('reports.vendoroneview')
            ->with('id', $id)
            ->with('datefrom', $datefrom)
            ->with('dateto', $dateto)
            ->with('vendor', $vendor)
            ->with('vendorbillings', $vendorbillings);
        }
        catch(Exception $e){
            abort(404);
        }
    }

    public function getVendoroneviewPDF($id,$datefrom,$dateto,$function)
    {
        $id = Crypt::decrypt($id);
        if($id == 0){
            abort(404);
        }

        try{
            $vendor = Vendor::find($id);
            $vendorbillings = DB::select('call spGetVendorBilling(\''.$id.'\',\''.$datefrom.'\',\''.$dateto.'\');');

            $pdf = Pdf::loadView('reports.vendoroneviewtemplate', array(
                'datefrom'=> $datefrom,
                'dateto'=> $dateto,
                'vendor'=> $vendor,
                'vendorbillings'=> $vendorbillings
            ));

            if($function == 'download'){
                return $pdf->setPaper('a4', 'landscape')->setOptions(['isPhpEnabled' => true])->download($vendor->name.'_'.$datefrom.'_'.$dateto.'.pdf');
            }elseif($function == 'view'){
                return $pdf->setPaper('a4', 'landscape')->setOptions(['isPhpEnabled' => true])->stream($vendor->name.'_'.$datefrom.'_'.$dateto.'.pdf');
            }
        }
        catch(Exception $e){
            abort(404);
        }

    }


}
