@extends('layouts.app')

@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item">Dashboard</li>
</ol>
  <div class="container-fluid">
        <div class="animated fadeIn">
             <div class="row">
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Total Sales (RM)</strong>
                            <div class="float-right px-2">
                                <button type="button" class="btn btn-sm btn-primary" onclick="getTotalSales();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                            </div>
                            <div class="float-right px-2">
                                <div class="btn-group">
                                    <button id="total-sales-by" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    By DAY
                                    </button>
                                    <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="setTotalSalesBy('DAY');">DAY</a>
                                    <a class="dropdown-item" href="#" onclick="setTotalSalesBy('WEEK');">WEEK</a>
                                    <a class="dropdown-item" href="#" onclick="setTotalSalesBy('MONTH');">MONTH</a>
                                    <a class="dropdown-item" href="#" onclick="setTotalSalesBy('YEAR');">YEAR</a>
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
                                  {!! Form::select('vendors', $vendorItems, $vendorIDItems, ['multiple' => 'true', 'id' => 'total-sales-item']) !!}
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="total-sales-container">
                                <canvas id="total-sales" width="800" height="500"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Product Delivered (TON)</strong>
                            <div class="float-right px-2">
                                <button type="button" class="btn btn-sm btn-primary" onclick="getProductDelivered();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                            </div>
                            <div class="float-right px-2">
                                <div class="btn-group">
                                    <button id="product-delivered-by" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    By DAY
                                    </button>
                                    <div class="dropdown-menu">
                                    <a class="dropdown-item" onclick="setProductDeliveredBy('DAY');">DAY</a>
                                    <a class="dropdown-item" onclick="setProductDeliveredBy('WEEK');">WEEK</a>
                                    <a class="dropdown-item" onclick="setProductDeliveredBy('MONTH');">MONTH</a>
                                    <a class="dropdown-item" onclick="setProductDeliveredBy('YEAR');">YEAR</a>
                                    </div>
                                </div>
                            </div>
                            <div class="float-right px-2">
                                <div id="product-delivered-reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down"></i>
                                </div>
                            </div>
                            <div class="float-right px-2">
                                  {!! Form::select('vendors', $vendorItems, $vendorIDItems, ['multiple' => 'true', 'id' => 'product-delivered-item']) !!}
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="product-delivered-container">
                                <canvas id="product-delivered" width="800" height="500"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Product Type</strong>
                            <div class="float-right px-2">
                                <button type="button" class="btn btn-sm btn-primary" onclick="getProductType();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                            </div>
                            <div class="float-right px-2">
                                <div id="product-type-reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down"></i>
                                </div>
                            </div>
                            <div class="float-right px-2">
                                  {!! Form::select('vendors', ['SALES'=>'Sales (RM)','WEIGHT'=>'Weight (TON)'], 'SALES', ['id' => 'product-type-item']) !!}
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="product-type-container">
                                <canvas id="product-type" width="800" height="500"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Driver Performance (TON)</strong>
                            <div class="float-right px-2">
                                <button type="button" class="btn btn-sm btn-primary" onclick="getDriverPerformance();"><i class="icon-reload icons d-block mt-1" style="font-size:21px;"></i></button>
                            </div>
                            <div class="float-right px-2">
                                <div class="btn-group">
                                    <button id="driver-performance-by" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    By DAY
                                    </button>
                                    <div class="dropdown-menu">
                                    <a class="dropdown-item" onclick="setDriverPerformanceBy('DAY');">DAY</a>
                                    <a class="dropdown-item" onclick="setDriverPerformanceBy('WEEK');">WEEK</a>
                                    <a class="dropdown-item" onclick="setDriverPerformanceBy('MONTH');">MONTH</a>
                                    <a class="dropdown-item" onclick="setDriverPerformanceBy('YEAR');">YEAR</a>
                                    </div>
                                </div>
                            </div>
                            <div class="float-right px-2">
                                <div id="driver-performance-reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down"></i>
                                </div>
                            </div>
                            <div class="float-right px-2">
                                  {!! Form::select('items', $itemItems, $itemIDItems, ['multiple' => 'true', 'id' => 'driver-performance-item']) !!}
                            </div>
                            <div class="float-right px-2">
                                  {!! Form::select('drivers', [], null, ['multiple' => 'true', 'id' => 'driver-performance-driver']) !!}
                            </div>

                            <div class="float-right px-2">
                                  {!! Form::select('group', $groupingItems, $groupingIDItems, ['multiple' => 'true', 'id' => 'driver-performance-group']) !!}
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="driver-performance-container">
                                <canvas id="driver-performance" width="800" height="500"></canvas>
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
        var totalsalesstart = moment().subtract(1, 'months');
        var totalsalesend = moment();
        var totalsalesitem = '%';
        var totalsaleby = 'DAY';

        var productdeliveredstart = moment().subtract(1, 'months');
        var productdeliveredend = moment();
        var productdelivereditem = '%';
        var productdeliveredby = 'DAY';

        var producttypestart = moment().subtract(1, 'months');
        var producttypeend = moment();
        var producttypetype = 'SALES';

        var driverperformancestart = moment().subtract(1, 'months');
        var driverperformanceend = moment();
        var driverperformanceitem = '%';
        var driverperformancecaptain = '%';
        var driverperformancegroup = '%';
        var driverperformancedriver = '%';
        var driverperformanceby = 'DAY';
        $(document).ready(function () {

            $('#total-sales-item').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Vendors'});
            $('#total-sales-item').on('change',function(e){ setTotalSalesItem($('#total-sales-item').val().toString()); });

            $('#product-delivered-item').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Vendors'});
            $('#product-delivered-item').on('change',function(e){ setProductDeliveredItem($('#product-delivered-item').val().toString()); });

            $('#driver-performance-item').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Products'});
            $('#driver-performance-item').on('change',function(e){ setDriverPerformanceItem($('#driver-performance-item').val().toString()); });

            $('#driver-performance-captain').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Captains'});
            $('#driver-performance-captain').on('change',function(e){ setDriverPerformanceCaptain($('#driver-performance-captain').val().toString()); });

            $('#driver-performance-group').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Groups'});
            $('#driver-performance-group').on('change',function(e){ setDriverPerformanceGroup($('#driver-performance-group').val().toString()); });

            $('#product-type-item').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Products'});
            $('#product-type-item').on('change',function(e){ setProductTypeType($('#product-type-item').val().toString()); });



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


            productdeliveredcb(productdeliveredstart,productdeliveredend);
            function productdeliveredcb(start, end) {
                $('#product-delivered-reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                productdeliveredstart = start;
                productdeliveredend = end;
                getProductDelivered();
            }
            $('#product-delivered-reportrange').daterangepicker({
                startDate: productdeliveredstart,
                endDate: productdeliveredend,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, productdeliveredcb);


            producttypecb(producttypestart,producttypeend);
            function producttypecb(start, end) {
                $('#product-type-reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                producttypestart = start;
                producttypeend = end;
                getProductType();
            }
            $('#product-type-reportrange').daterangepicker({
                startDate: producttypestart,
                endDate: producttypeend,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, producttypecb);


            driverperformancecb(driverperformancestart,driverperformanceend);
            function driverperformancecb(start, end) {
                $('#driver-performance-reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
                driverperformancestart = start;
                driverperformanceend = end;
                getDriverPerformance();
            }
            $('#driver-performance-reportrange').daterangepicker({
                startDate: driverperformancestart,
                endDate: driverperformanceend,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, driverperformancecb);

        });
        $(document).ready(function () {
            getDriverList(1);
            // getProductDelivered();
            // getTotalSales();
            // getProductType();
        });
    </script>

    <script>
        function HideLoadHome(){
            if(step > 4){
                $('#loading').removeClass('d-flex');
                $('#loading').hide();
            }
        }
        function ShowLoadHome(){
            $('#loading').addClass('d-flex');
            $('#loading').show();
        }
        function setTotalSalesItem(item){
            totalsalesitem = item;
            getTotalSales();
        }
        function setProductDeliveredItem(item){
            productdelivereditem = item;
            getProductDelivered();
        }
        function setDriverPerformanceItem(item){
            driverperformanceitem = item;
            getDriverPerformance();
        }
        function setDriverPerformanceDriver(driver){
            driverperformancedriver = driver;
            getDriverPerformance();
        }
        function setDriverPerformanceCaptain(captain){
            driverperformancecaptain = captain;
            getDriverList(2);
        }
        function setDriverPerformanceGroup(group){
            driverperformancegroup = group;
            getDriverList(2);
        }
        function setTotalSalesBy(by){
            $('#total-sales-by').html('By ' + by);
            totalsaleby = by;
            getTotalSales();
        }
        function setProductDeliveredBy(by){
            $('#product-delivered-by').html('By ' + by);
            productdeliveredby = by;
            getProductDelivered();
        }
        function setDriverPerformanceBy(by){
            $('#driver-performance-by').html('By ' + by);
            driverperformanceby = by;
            getDriverPerformance();
        }
        function setProductTypeType(type){
            producttypetype = type;
            getProductType();
        }

        function getTotalSales(){
            ShowLoad();
            var datefrom = totalsalesstart.format('YYYY-MM-DD');
            var dateto = totalsalesend.format('YYYY-MM-DD');
            var item = totalsalesitem;
            var by = totalsaleby;
            $.ajax({
                url: "{{config('app.url')}}/home/getTotalSales",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,item: item
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

        function getProductDelivered(){
            ShowLoad();
            var datefrom = productdeliveredstart.format('YYYY-MM-DD');
            var dateto = productdeliveredend.format('YYYY-MM-DD');
            var item = productdelivereditem;
            var by = productdeliveredby;
            $.ajax({
                url: "{{config('app.url')}}/home/getProductDelivered",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,item: item
                ,by: by
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    drawProductDelivered($.parseJSON(response));
                    HideLoadHome();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoadHome();
                }
            });
        }

        function getProductType(){
            ShowLoad();
            var datefrom = producttypestart.format('YYYY-MM-DD');
            var dateto = producttypeend.format('YYYY-MM-DD');
            var type = producttypetype;
            $.ajax({
                url: "{{config('app.url')}}/home/getProductType",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,type: type
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    drawProductType($.parseJSON(response));
                    HideLoadHome();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoadHome();
                }
            });
        }

        function getDriverPerformance(){
            ShowLoad();
            var datefrom = driverperformancestart.format('YYYY-MM-DD');
            var dateto = driverperformanceend.format('YYYY-MM-DD');
            var driver = driverperformancedriver;
            var item = driverperformanceitem;
            var by = driverperformanceby;
            $.ajax({
                url: "{{config('app.url')}}/home/getDriverPerformance",
                type:"POST",
                data:{
                datefrom: datefrom
                ,dateto: dateto
                ,driver: driver
                ,item: item
                ,by: by
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    drawDriverPerformance($.parseJSON(response));
                    HideLoadHome();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoadHome();
                }
            });
        }

        function getDriverList(step){
            ShowLoad();
            var captain = driverperformancecaptain;
            var group = driverperformancegroup;
            $.ajax({
                url: "{{config('app.url')}}/home/getDriverList",
                type:"POST",
                data:{
                captain: captain
                ,group: group
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    if(step == 1){
                        setDriverSelectInitial(response);
                    }else{
                        setDriverSelect(response);
                    }
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        function setDriverSelectInitial(data){
            $("#driver-performance-driver").html('');
            $.each( data, function( i, l ){
                var sOption = '<option value="'+l['id']+'" selected="selected">'+l['name']+'</option>'
                $("#driver-performance-driver").append(sOption);
            });

            $('#driver-performance-driver').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Drivers'});
            $('#driver-performance-driver').on('change',function(e){ setDriverPerformanceDriver($('#driver-performance-driver').val().toString()); });
            $("#driver-performance-driver").SumoSelect().sumo.reload();
        }

        function setDriverSelect(data){
            $("#driver-performance-driver").html('');
            $.each( data, function( i, l ){
                var sOption = '<option value="'+l['id']+'" selected="selected">'+l['name']+'</option>'
                $("#driver-performance-driver").append(sOption);
            });
            $('#driver-performance-driver').off('change');
            $('#driver-performance-driver').SumoSelect({okCancelInMulti: true, selectAll: true, placeholder: 'Select Drivers'});
            $('#driver-performance-driver').on('change',function(e){ setDriverPerformanceDriver($('#driver-performance-driver').val().toString()); });
            $("#driver-performance-driver").SumoSelect().sumo.reload();
            $("#driver-performance-driver").change();
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

        function drawProductDelivered(data){
            $('#product-delivered').remove();
            $('#product-delivered-container').append('<canvas id="product-delivered" width="800" height="500"></canvas>');
            if(data.datasets == null){
                var canvas = document.getElementById("product-delivered");
                var ctx = canvas.getContext("2d");
                ctx.font = "30px Comic Sans MS";
                ctx.textAlign = "center";
                ctx.fillStyle = "gray";
                ctx.fillText("No data available", canvas.width/2, canvas.height/2);
            }else{
                new Chart(document.getElementById("product-delivered"), {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        title: {
                        display: true,
                        text: 'Product Delivered'
                        }
                    }
                });
            }
            step++;
        }

        function drawProductType(data){
            $('#product-type').remove();
            $('#product-type-container').append('<canvas id="product-type" width="800" height="500"></canvas>');
            if(data.labels == null){
                var canvas = document.getElementById("product-type");
                var ctx = canvas.getContext("2d");
                ctx.font = "30px Comic Sans MS";
                ctx.textAlign = "center";
                ctx.fillStyle = "gray";
                ctx.fillText("No data available", canvas.width/2, canvas.height/2);
            }else{
                new Chart(document.getElementById("product-type"), {
                    type: 'doughnut',
                    data: data,
                    options: {
                        plugins: {
                            legend: {
                                position: 'right'
                            }

                        },
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        },
                        maintainAspectRatio: false,
                        title: {
                        display: true,
                        text: 'Product Type'
                        }
                    }
                });
            }
            step++;
        }

        function drawDriverPerformance(data){
            $('#driver-performance').remove();
            $('#driver-performance-container').append('<canvas id="driver-performance" width="800" height="500"></canvas>');
            if(data.labels == null){
                var canvas = document.getElementById("driver-performance");
                var ctx = canvas.getContext("2d");
                ctx.font = "30px Comic Sans MS";
                ctx.textAlign = "center";
                ctx.fillStyle = "gray";
                ctx.fillText("No data available", canvas.width/2, canvas.height/2);
            }else{
                new Chart(document.getElementById("driver-performance"), {
                    type: 'line',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        title: {
                        display: true,
                        text: 'Driver Performance'
                        }
                    }
                });
            }
            step++;
        }


        // new Chart(document.getElementById("product-type"), {
        //     type: 'doughnut',
        //     data: {
        //     labels: ["Product A", "Product B", "Product C", "Product D", "Product E"],
        //     datasets: [{
        //         label: "Product Type",
        //         backgroundColor: ["#3e95cd", "#8e5ea2","#3cba9f","#e8c3b9","#c45850"],
        //         data: [2478,5267,734,784,433]
        //     }]
        //     },
        //     options: {
        //     maintainAspectRatio: false,
        //     title: {
        //         display: true,
        //         text: 'Product Type'
        //     }
        //     }
        // });

    </SCript>
@endpush
