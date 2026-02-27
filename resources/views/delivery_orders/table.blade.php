@section('css')
    @include('layouts.datatables_css')
@endsection

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-bordered'], true) !!}

@push('scripts')
    @include('layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    
    <script>
        $(document).ready(function () {

            $(".buttons-reset").click(function(e){
                $('#dataTableBuilder tfoot th input').val('');
                $('#dataTableBuilder tfoot th select').val(1);
            });
            var table = $('#dataTableBuilder').DataTable();
            table.on( 'draw', function () {
                setcheckbox(window.checkboxid);
                checkcheckbox();
                HideLoad();
            });
            table.on( 'preDraw', function () {
                ShowLoad();
            });
            if(resize == 1){
                $('#dataTableBuilder').resizableColumns();
            }
        });
    </script>
@endpush


@push('scripts')
    <script>
        window.checkboxid = [];
        $(document).on("change", ".checkboxselect", function(e){
            if(this.checked){
                addcheckboxid($(this).attr('checkboxid'));
            }
            else{
                removecheckboxid($(this).attr('checkboxid'));
            }
            checkcheckbox();
        });
        $(document).on("change", "#selectallcheckbox", function(e){
            var checkall = this.checked;
            $('.checkboxselect').each(function(i, obj) {
                if(checkall){
                    if(!obj.checked){
                        addcheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop( "checked", checkall );
                    }
                }else{
                    if(obj.checked){
                        removecheckboxid($(obj).attr('checkboxid'));
                        $(obj).prop( "checked", checkall );
                    }
                }
            });
        });

        $(document).on("click", ".CountClaim", function(e){
            if($(this).text() == '0'){
                noti('i','Info','No claim can be show...')
                return;
            }
            getClaimInfo($(this).attr("dokey"));
        });
        $(document).on("click", ".BillingRate", function(e){
            getBillingRateInfo($(this).attr("dokey"));
        });
        $(document).on("click", ".CommissionRate", function(e){
            getCommissionRateInfo($(this).attr("dokey"));
        });
        function getClaimInfo(dokey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/deliveryOrders/getClaimInfo",
                type:"POST",
                data:{
                dokey: dokey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Claims')
                        modal.find('.modal-body').html(body)
                    })
                    $('#infoModel').modal('show')
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        function getBillingRateInfo(dokey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/deliveryOrders/getBillingRateInfo",
                type:"POST",
                data:{
                dokey: dokey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Billing Rate Details')
                        modal.find('.modal-body').html(body)
                    })
                    $('#infoModel').modal('show')
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        function getCommissionRateInfo(dokey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/deliveryOrders/getCommissionRateInfo",
                type:"POST",
                data:{
                dokey: dokey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Commission Rate Details')
                        modal.find('.modal-body').html(body)
                    })
                    $('#infoModel').modal('show')
                    HideLoad();
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        function getTableCode(data)
        {
            var tbl  = document.createElement("table");
            tbl.className = "table table-sm table-striped";
            var tr = tbl.insertRow(-1);
            $.each( data[0], function( key, value ) {
                var td = tr.insertCell();
                td.appendChild(document.createTextNode(key.charAt(0).toUpperCase() + key.slice(1)));
            });
            for (var i = 0; i < data.length; ++i)
            {
                var tr = tbl.insertRow();
                $.each( data[i], function( key, value ) {
                    var td = tr.insertCell();
                    td.appendChild(document.createTextNode(value.toString()));
                });
            }
            return tbl;
        }

        function searchDateColumn(i){
            $('#columnid').val(i.id);
            $('#dateModel').modal('show')
        }

        function dateRange(steps = 1) {
            if($('#datefrommodel').val() == ''){
                noti('i','Date From cannot be empty','Please select the Date From');
                return;
            }
            if($('#datetomodel').val() == ''){
                noti('i','Date To cannot be empty','Please select the Date To');
                return;
            }
            if($('#datetomodel').val() < $('#datefrommodel').val()){
                noti('i','Date From cannot greater than Date To','Please select the Date again');
                return;
            }
            var dateArray = '';
            var currentDateParts = $('#datefrommodel').val().split("-");
            var endDateParts = $('#datetomodel').val().split("-");
            var currentDate = new Date(+currentDateParts[2], currentDateParts[1] - 1, +currentDateParts[0]);
            var endDate = new Date(+endDateParts[2], endDateParts[1] - 1, +endDateParts[0]);
                
            while (currentDate <= endDate) {
                dateArray=dateArray+moment(currentDate).format("YYYY-MM-DD")+'|';
                currentDate.setUTCDate(currentDate.getUTCDate() + steps);
            }

            $('#'+$('#columnid').val()).val(dateArray.substring(0, dateArray.length-1)).change();
            $('#dateModel').modal('hide')
        }
        var start = moment();
        var end = moment();

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY'));
            $('#datefrommodel').val(start.format('DD-MM-YYYY'));
            $('#datetomodel').val(end.format('DD-MM-YYYY'));
        }
        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        function addcheckboxid(checkboxid){
            window.checkboxid.push(checkboxid);
            // console.log(window.checkboxid);
        }
        function removecheckboxid(checkboxid){
            window.checkboxid = jQuery.grep(window.checkboxid, function(value) {
                return value != checkboxid;
            });
            // console.log(window.checkboxid);
        }
        function setcheckbox(checkboxids){
            // console.log(checkboxid);
            for (i = 0; i < checkboxids.length; ++i) {
                $('input[class="checkboxselect"][checkboxid="'+checkboxids[i]+'"]').prop( "checked", true );
            }
        }
        function checkcheckbox(){
            var checked = 0;
            var checkbox = $('.checkboxselect');
            checkbox.each(function(i, obj) {
                if(obj.checked){
                    checked ++;
                }
            });
            if(checked == checkbox.length){
                $('#selectallcheckbox').prop( "checked", true );
            }else{
                $('#selectallcheckbox').prop( "checked", false );
            }
        }
    </script>
@endpush

<div class="modal fade" id="dateModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="infoModelLabel">Select a date range</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <input id="columnid" type="hidden" value="">
            <input id="datefrommodel" type="hidden" value="">
            <input id="datetomodel" type="hidden" value="">
            <div class="form-group col-sm-12">
                <label for="datefrommodel">Date:</label>
                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                    <i class="fa fa-calendar"></i>&nbsp;
                    <span></span> <i class="fa fa-caret-down"></i>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="dateRange();">Search</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
</div>

<div class="modal fade" id="infoModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="infoModelLabel">Modal title</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <div class="form-group col-sm-12">
                <label for="dono">DO Number:</label>
                <input class="form-control" type="text" disabled value="value">
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
</div>