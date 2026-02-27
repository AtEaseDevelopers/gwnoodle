@extends('layouts.app')

@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item">{{ __('dashboard.dashboard') }}</li>
</ol>
  <div class="container-fluid">
       <div class="animated fadeIn">
            <div class="row">
               <div class="col">
                   <div class="card mb-4">
                       <div class="card-header">
                           <strong>{{ __('dashboard.total_sales_rm') }}</strong>
                           <div class="float-right px-2">
                               <button type="button" class="btn btn-sm btn-primary" onclick="getTotalSales();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                           </div>
                           <div class="float-right px-2">
                               <div class="btn-group">
                                   <button id="total-sales-by" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                   {{ __('dashboard.by_day') }}
                                   </button>
                                   <div class="dropdown-menu">
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesBy('DAY');">{{ __('dashboard.day') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesBy('WEEK');">{{ __('dashboard.week') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesBy('MONTH');">{{ __('dashboard.month') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesBy('YEAR');">{{ __('dashboard.year') }}</a>
                                   </div>
                               </div>
                           </div>
                           <div class="float-right px-2">
                               <div id="total-sales-reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                   <i class="fa fa-calendar"></i>&nbsp;
                                   <span></span> <i class="fa fa-caret-down"></i>
                               </div>
                           </div>
                           <div class="float-right px-2">
                                 {!! Form::select('customer', $customerItems, $customerIDItems, ['multiple' => 'true', 'id' => 'total-sales-customer']) !!}
                           </div>
                           <div class="float-right px-2">
                                 {!! Form::select('drivers', $driverItems, $driverIDItems, ['multiple' => 'true', 'id' => 'total-sales-driver']) !!}
                           </div>
                       </div>
                       <div class="card-body">
                           <div id="total-sales-container">
                               <canvas id="total-sales" width="800" height="500"></canvas>
                           </div>
                       </div>
                   </div>
               </div>
           </div>

            <div class="row">
               <div class="col">
                   <div class="card mb-4">
                       <div class="card-header">
                           <strong>{{ __('dashboard.total_sales_quantity') }}</strong>
                           <div class="float-right px-2">
                               <button type="button" class="btn btn-sm btn-primary" <!--onclick="getTotalSalesQty();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                           </div>
                           <div class="float-right px-2">
                               <div class="btn-group">
                                   <button id="total-sales-by" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                   {{ __('dashboard.by_day') }}
                                   </button>
                                   <div class="dropdown-menu">
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesQtyBy('DAY');">{{ __('dashboard.day') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesQtyBy('WEEK');">{{ __('dashboard.week') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesQtyBy('MONTH');">{{ __('dashboard.month') }}</a>
                                   <a class="dropdown-item" href="#" onclick="setTotalSalesQtyBy('YEAR');">{{ __('dashboard.year') }}</a>
                                   </div>
                               </div>
                           </div>
                           <div class="float-right px-2">
                               <div id="total-sales-qty-reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                   <i class="fa fa-calendar"></i>&nbsp;
                                   <span></span> <i class="fa fa-caret-down"></i>
                               </div>
                           </div>
                           <div class="float-right px-2">
                                 {!! Form::select('customer', $customerItems, $customerIDItems, ['multiple' => 'true', 'id' => 'total-sales-qty-customer']) !!}
                           </div>
                           <div class="float-right px-2">
                                 {!! Form::select('drivers', $driverItems, $driverIDItems, ['multiple' => 'true', 'id' => 'total-sales-qty-driver']) !!}
                           </div>
                       </div>
                       <div class="card-body">
                           <div id="total-sales-qty-container">
                               <canvas id="total-sales-qty" width="800" height="500"></canvas>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        var step = 1;
        var maxstep = 2;
        function HideLoadHome(){
            if(step > maxstep){
                $('#loading').removeClass('d-flex');
                $('#loading').hide();
            }
        }

        var totalsalesstart = moment().subtract(1, 'months');
        var totalsalesend = moment();
        var totalsalesdriver = '%';
        var totalsalescustomer = '%';
        var totalsaleby = 'DAY';

        $(document).ready(function () {

            $('#total-sales-driver').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Drivers'});
            $('#total-sales-driver').on('change',function(e){ setTotalSalesDriver($('#total-sales-driver').val().toString()); });

            $('#total-sales-customer').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Customers'});
            $('#total-sales-customer').on('change',function(e){ setTotalSalesCustomer($('#total-sales-customer').val().toString()); });

            totalsalescb(totalsalesstart,totalsalesend);
            function totalsalescb(start, end) {
                $('#total-sales-reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                totalsalesstart = start;
                totalsalesend = end;
                getTotalSales();
            }
            $('#total-sales-reportrange').daterangepicker({
                startDate: totalsalesstart,
                endDate: totalsalesend,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, totalsalescb);

            ////////////////////////////////////////////////////////////////////////

            $('#total-sales-qty-driver').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Drivers'});
            $('#total-sales-qty-driver').on('change',function(e){ setTotalSalesDriver($('#total-sales-qty-driver').val().toString()); });

            $('#total-sales-qty-customer').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Customers'});
            $('#total-sales-qty-customer').on('change',function(e){ setTotalSalesCustomer($('#total-sales-qty-customer').val().toString()); });

            totalsalesqtycb(TotalSalesQtystart,TotalSalesQtyend);
            function totalsalesqtycb(start, end) {
                $('#total-sales-qty-reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                TotalSalesQtystart = start;
                TotalSalesQtyend = end;
                getTotalSalesQty();
            }
            $('#total-sales-qty-reportrange').daterangepicker({
                startDate: TotalSalesQtystart,
                endDate: TotalSalesQtyend,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, totalsalesqtycb);

        });

        function setTotalSalesDriver(driver){
            totalsalesdriver = driver;
            getTotalSales();
        }

        function setTotalSalesCustomer(customer){
            totalsalescustomer = customer;
            getTotalSales();
        }

        function setTotalSalesBy(by){
            $('#total-sales-by').html('By ' + by);
            totalsaleby = by;
            getTotalSales();
        }

        function getTotalSales(){
            ShowLoad();
            var datefrom = totalsalesstart.format('YYYY-MM-DD');
            var dateto = totalsalesend.format('YYYY-MM-DD');
            var driver = totalsalesdriver;
            var customer = totalsalescustomer;
            var by = totalsaleby;
            $.ajax({
                url: "{{config('app.url')}}/home/getTotalSales",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,driver: driver
                ,customer: customer
                ,by: by
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    drawTotalSales($.parseJSON(response));
                    HideLoadHome();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoadHome();
                }
            });
            
        }

        function drawTotalSales(data){
            $('#total-sales').remove();
            $('#total-sales-container').append('<canvas id="total-sales" width="800" height="500"></canvas>');
            if(data.datasets == null){
                var canvas = document.getElementById("total-sales");
                var ctx = canvas.getContext("2d");
                ctx.font = "30px Comic Sans MS";
                ctx.textAlign = "center";
                ctx.fillStyle = "gray";
                ctx.fillText("No data available", canvas.width/2, canvas.height/2);
            }else{
                new Chart(document.getElementById("total-sales"), {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        title: {
                        display: true,
                        text: 'Total Sales'
                        }
                    }
                });
            }
            step++;
        }
        ////////////////////////////////////////////////////////////////


        var TotalSalesQtystart = moment().subtract(1, 'months');
        var TotalSalesQtyend = moment();
        var TotalSalesQtydriver = '%';
        var TotalSalesQtycustomer = '%';
        var totalsaleqtyby = 'DAY';

        function setTotalSalesQtyDriver(driver){
            TotalSalesQtydriver = driver;
            getTotalSalesQty();
        }

        function setTotalSalesQtyCustomer(customer){
            TotalSalesQtycustomer = customer;
            getTotalSalesQty();
        }

        function setTotalSalesQtyBy(by){
            $('#total-sales-qty-by').html('By ' + by);
            totalsaleqtyby = by;
            getTotalSalesQty();
        }

        function getTotalSalesQty(){
            ShowLoad();
            var datefrom = TotalSalesQtystart.format('YYYY-MM-DD');
            var dateto = TotalSalesQtyend.format('YYYY-MM-DD');
            var driver = TotalSalesQtydriver;
            var customer = TotalSalesQtycustomer;
            var by = totalsaleqtyby;
            $.ajax({
                url: "{{config('app.url')}}/home/getTotalSalesQty",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,driver: driver
                ,customer: customer
                ,by: by
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    drawTotalSalesQty($.parseJSON(response));
                    HideLoadHome();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoadHome();
                }
            });
           
        }

        function drawTotalSalesQty(data){
            $('#total-sales-qty').remove();
            $('#total-sales-qty-container').append('<canvas id="total-sales-qty" width="800" height="500"></canvas>');
            if(data.datasets == null){
                var canvas = document.getElementById("total-sales-qty");
                var ctx = canvas.getContext("2d");
                ctx.font = "30px Comic Sans MS";
                ctx.textAlign = "center";
                ctx.fillStyle = "gray";
                ctx.fillText("No data available", canvas.width/2, canvas.height/2);
            }else{
                new Chart(document.getElementById("total-sales-qty"), {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        title: {
                        display: true,
                        text: 'Total Sales'
                        }
                    }
                });
            }
            step++;
        }
    </script>
@endpush
