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