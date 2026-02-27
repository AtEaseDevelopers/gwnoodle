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
            });
            var table = $('#dataTableBuilder').DataTable({
                pageLength: 10,
            });
            table.on( 'draw', function () {
                HideLoad();
            });
            table.on( 'preDraw', function () {
                ShowLoad();
            });
        });
    </script>
@endpush

