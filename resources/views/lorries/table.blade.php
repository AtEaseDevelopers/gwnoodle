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
        
        $(document).on("click", ".TyreService", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Tyre Service record...')
                return;
            }
            getTyreServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".InsuranceList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Insurance Service record...')
                return;
            }
            getInsuranceServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".PermitList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Permit Service record...')
                return;
            }
            getPermitServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".RoadTaxList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Road Tax Service record...')
                return;
            }
            getRoadtaxServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".InspectionList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Inspection Service record...')
                return;
            }
            getInspectionServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".OtherList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Other Service record...')
                return;
            }
            getOtherServiceInfo($(this).attr("lorrykey"));
        });
        $(document).on("click", ".FireExtinguisherList", function(e){
            if($(this).text() == 'NAN'){
                noti('i','Info','There is no Fire Extinguisher record...')
                return;
            }
            getFireExtinguisherServiceInfo($(this).attr("lorrykey"));
        });

        function getTyreServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getTyreServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Tyre Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')                        
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

        function getInsuranceServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getInsuranceServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Insurance Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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

        function getPermitServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getPermitServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Permit Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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

        function getRoadtaxServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getRoadtaxServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Road Tax Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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

        function getInspectionServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getInspectionServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Inspection Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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

        function getOtherServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getOtherServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    console.log(response);
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Other Service Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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

        function getFireExtinguisherServiceInfo(lorrykey){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/servicedetails/getFireExtinguisherServiceInfo",
                type:"POST",
                data:{
                lorrykey: lorrykey
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    console.log(response);
                    var body = getTableCode(response);

                    $('#infoModel').on('show.bs.modal', function (event) {
                        var modal = $(this)
                        modal.find('.modal-title').text('Fire Extinguisher Records')
                        modal.find('.modal-body').html(body)
                        modal.find('.modal-body').append('<br><br><p><i>Only show up to 3 records, for the older records please find in Lorry Service Module.</i></p>')    
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