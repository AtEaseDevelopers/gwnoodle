<!-- Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:') !!}<span class="asterisk"> *</span>
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date','autofocus']) !!}
</div>

<!-- Dono Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dono', 'DO Number:') !!}<span class="asterisk"> *</span>
    {!! Form::text('dono', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
               format: 'DD-MM-YYYY',
               useCurrent: true,
               icons: {
                    time: "fa fa-clock-o",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-chevron-left",
                    next: "fa fa-chevron-right",
                    today: "fa fa-clock-o",
                    clear: "fa fa-trash-o"
               },
               sideBySide: true
           })
       </script>
@endpush

<!-- Driver Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('driver_id', 'Driver:') !!}&nbsp;<a href="#" id="info_driver_id" class="pe-auto"><i class="nav-icon icon-info"></i></a>&nbsp;<span class="asterisk"> *</span>
    {!! Form::select('driver_id', $driverItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Driver...','data-live-search'=>'true']) !!}
</div>

<!-- Lorry Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lorry_id', 'Lorry:') !!}&nbsp;<a href="#" id="info_lorry_id" class="pe-auto"><i class="nav-icon icon-info"></i></a>&nbsp;<span class="asterisk"> *</span>
    {!! Form::select('lorry_id', $lorryItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Lorry...','data-live-search'=>'true']) !!}
</div>

<!-- Vendor Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vendor_id', 'Vendor:') !!}<span class="asterisk"> *</span>
    {!! Form::select('vendor_id', $vendorItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Vendor...','data-live-search'=>'true']) !!}
</div>

<!-- Source Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('source_id', 'Source:') !!}<span class="asterisk"> *</span>
    {!! Form::select('source_id', $sourceItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Source...','data-live-search'=>'true']) !!}
</div>

<!-- Destinate Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('destinate_id', 'Destination:') !!}<span class="asterisk"> *</span>
    {!! Form::select('destinate_id', $destinateItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Destinate...','data-live-search'=>'true']) !!}
</div>

<!-- Item Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('item_id', 'Product:') !!}<span class="asterisk"> *</span>
    {!! Form::select('item_id', $itemItems, null, ['class' => 'form-control selectpicker', 'placeholder' => 'Pick a Item...','data-live-search'=>'true']) !!}
</div>

<!-- Weight Field -->
<div class="form-group col-sm-6">
    {!! Form::label('weight', 'Source Weight:') !!}<span class="asterisk"> *</span>
    {!! Form::number('weight', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Shipweight Field -->
<div class="form-group col-sm-6">
    {!! Form::label('shipweight', 'Destination Weight:') !!}
    {!! Form::number('shipweight', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Fees Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fees', 'Loading/Unloading Fees:') !!}
    {!! Form::number('fees', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Tol Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tol', 'Tol:') !!}
    {!! Form::number('tol', null, ['class' => 'form-control','step'=>'0.01','min'=>'0']) !!}
</div>

<!-- Billingrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('billingrate', 'Billing Rate:') !!}&nbsp;<a href="#" id="info_billingrate" class="pe-auto"><i class="nav-icon icon-info"></i></a>&nbsp;<span class="asterisk"> *</span>
    {!! Form::number('billingrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0','disabled']) !!}
</div>

<!-- Commissionrate Field -->
<div class="form-group col-sm-6">
    {!! Form::label('commissionrate', 'Commission Rate:') !!}&nbsp;<a href="#" id="info_commissionrate" class="pe-auto"><i class="nav-icon icon-info"></i></a>&nbsp;<span class="asterisk"> *</span>
    {!! Form::number('commissionrate', null, ['class' => 'form-control','step'=>'0.01','min'=>'0','disabled']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}<span class="asterisk"> *</span>
    {{ Form::select('status', array(1 => 'Active', 0 => 'Unactive'), null, ['class' => 'form-control']) }}
</div>

<!-- Remark Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remark', 'Remark:') !!}
    {!! Form::text('remark', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

{{-- <!-- Str Udf1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF1', 'String UDF1:') !!}
    {!! Form::textarea('STR_UDF1', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Str Udf2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF2', 'String UDF2:') !!}
    {!! Form::textarea('STR_UDF2', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Str Udf3 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('STR_UDF3', 'String UDF3:') !!}
    {!! Form::textarea('STR_UDF3', null, ['class' => 'form-control','rows'=>'1']) !!}
</div>

<!-- Int Udf1 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF1', 'Integer UDF1:') !!}
    {!! Form::number('INT_UDF1', null, ['class' => 'form-control']) !!}
</div>

<!-- Int Udf2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF2', 'Integer UDF2:') !!}
    {!! Form::number('INT_UDF2', null, ['class' => 'form-control']) !!}
</div>

<!-- Int Udf3 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('INT_UDF3', 'Integer UDF3:') !!}
    {!! Form::number('INT_UDF3', null, ['class' => 'form-control']) !!}
</div> --}}

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('deliveryOrders.index') }}" class="btn btn-secondary">Cancel</a>
</div>

@push('scripts')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('form a.btn-secondary')[0].click();
            }
        });
        $(document).ready(function () {
            $("#info_lorry_id").click(function() {
                getLorryInfo();
            });
            $("#info_driver_id").click(function() {
                getDriverInfo();
            });
            HideLoad();
            $("#info_billingrate").click(function() {
                getBillingRateInfo();
            });
            $("#info_commissionrate").click(function() {
                getCommissionRateInfo();
            });
            $("#item_id").bind("change", function() {
                getBillingRate();
                getCommissionRate();
            });
            $("#driver_id").bind("change", function() {
                getDriverLorry();
            });
            getBillingRate();
            getCommissionRate();
        });
        function getDriverLorry(){
            if($('#driver_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/deliveryOrders/getDriverLorry",
                    type:"POST",
                    data:{
                    driver_id: parseInt($('#driver_id').val())
                    ,_token: "{{ csrf_token() }}"
                    },
                    success:function(response){
                        $('#lorry_id').val(response['lorry_id']);
                        $('#lorry_id').trigger('change');
                        HideLoad();
                    },
                    error: function(error) {
                        noti('e','Please contact your administrator',error.responseJSON.message);
                        HideLoad();
                    }
                });
            }else{
                noti('i','Driver not select','Please select the driver and click again...');
            }
        }
        function getBillingRate(){
            if($('#item_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/items/getBillingRate",
                    type:"POST",
                    data:{
                    item_id: parseInt($('#item_id').val())
                    ,_token: "{{ csrf_token() }}"
                    },
                    success:function(response){
                        $('#billingrate').val(response);
                        HideLoad();
                    },
                    error: function(error) {
                        noti('e','Please contact your administrator',error.responseJSON.message)
                        HideLoad();
                    }
                });
            }
        }
        function getCommissionRate(){
            if($('#item_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/items/getCommissionRate",
                    type:"POST",
                    data:{
                    item_id: parseInt($('#item_id').val())
                    ,_token: "{{ csrf_token() }}"
                    },
                    success:function(response){
                        console.log(response);
                        $('#commissionrate').val(response);
                        HideLoad();
                    },
                    error: function(error) {
                        noti('e','Please contact your administrator',error.responseJSON.message)
                        HideLoad();
                    }
                });
            }
        }
        function getDriverInfo(){
            if($('#driver_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/deliveryOrders/getDriverInfo",
                    type:"POST",
                    data:{
                    driver_id: parseInt($('#driver_id').val())
                    ,_token: "{{ csrf_token() }}"
                    },
                    success:function(response){
                        var body = '<div class="form-group col-sm-12">';
                        body = body + getInputCode('Driver Name',response['name']);
                        body = body + getInputCode('IC',response['ic']);
                        body = body + getInputCode('Group',response['grouping']);
                        body = body + getInputCode('Captain',response['caption']);
                        body = body + '</div>';

                        $('#infoModel').on('show.bs.modal', function (event) {
                            var modal = $(this);
                            modal.find('.modal-title').text('Driver Information');
                            modal.find('.modal-body').html(body);
                        })
                        $('#infoModel').modal('show');
                        HideLoad();
                    },
                    error: function(error) {
                        noti('e','Please contact your administrator',error.responseJSON.message);
                        HideLoad();
                    }
                });
            }else{
                noti('i','Driver not select','Please select the driver and click again...');
            }
        }
        function getLorryInfo(){
            if($('#lorry_id').val() == ''){
                noti('i','Lorry not select','Please select the lorry and click again...');
                return;
            }
            if($('#vendor_id').val() == ''){
                noti('i','Vendor not select','Please select the vendor and click again...');
                return;
            }
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/deliveryOrders/getLorryInfo",
                type:"POST",
                data:{
                lorry_id: parseInt($('#lorry_id').val())
                ,vendor_id: parseInt($('#vendor_id').val())
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = '<div class="form-group col-sm-12">';
                    body = body + getInputCode('Lorry No',response['lorryno']);
                    body = body + getInputCode('Lorry Type',response['type']);
                    body = body + getInputCode('Weightage Limit',response['weightagelimit']);
                    body = body + getInputCode('Commission Limit',response['commissionlimit']);
                    body = body + getInputCode('Commission Percentage',response['commissionpercentage']);
                    body = body + '</div>';

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Lorry Information')
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
        function getInputCode(t,v){
            var label = '<label for="dono">'+t+'</label>';
            var input = '<input class="form-control" type="text" disabled value="'+v+'">';
            return label + input;
        }


        function getBillingRateInfo(dokey){
            if($('#vendor_id').val() == ''){
                noti('i','Vendor not select','Please select the Vendor and click again...');
                return;
            }
            if($('#source_id').val() == ''){
                noti('i','Source not select','Please select the Source and click again...');
                return;
            }
            if($('#destinate_id').val() == ''){
                noti('i','Destination not select','Please select the Destination and click again...');
                return;
            }
            if($('#item_id').val() == ''){
                noti('i','Product not select','Please select the Product and click again...');
                return;
            }
            if($('#vendor_id').val() != '' && $('#item_id').val() != '' && $('#source_id').val() != '' && $('#destinate_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/deliveryOrders/getBillingRate",
                    type:"POST",
                    data:{
                    vendor_id: parseInt($('#vendor_id').val())
                    ,item_id: parseInt($('#item_id').val())
                    ,source_id: parseInt($('#source_id').val())
                    ,destinate_id: parseInt($('#destinate_id').val())
                    ,weight: parseInt($('#weight').val())
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
        }

        function getCommissionRateInfo(dokey){
            if($('#vendor_id').val() == ''){
                noti('i','Vendor not select','Please select the Vendor and click again...');
                return;
            }
            if($('#source_id').val() == ''){
                noti('i','Source not select','Please select the Source and click again...');
                return;
            }
            if($('#destinate_id').val() == ''){
                noti('i','Destination not select','Please select the Destination and click again...');
                return;
            }
            if($('#item_id').val() == ''){
                noti('i','Product not select','Please select the Product and click again...');
                return;
            }
            if($('#vendor_id').val() != '' && $('#item_id').val() != '' && $('#source_id').val() != '' && $('#destinate_id').val() != ''){
                ShowLoad();
                $.ajax({
                    url: "{{config('app.url')}}/deliveryOrders/getCommissionRate",
                    type:"POST",
                    data:{
                    vendor_id: parseInt($('#vendor_id').val())
                    ,item_id: parseInt($('#item_id').val())
                    ,source_id: parseInt($('#source_id').val())
                    ,destinate_id: parseInt($('#destinate_id').val())
                    ,weight: parseInt($('#weight').val())
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
    </script>
@endpush

<div class="modal fade" id="infoModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
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