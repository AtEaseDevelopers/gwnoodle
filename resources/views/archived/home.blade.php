@extends('archived.layouts.app')

@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item">Archived Home</li>
</ol>
  <div class="container-fluid">
        <div class="animated fadeIn">
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#loading').removeClass('d-flex');
            $('#loading').hide();
        });
    </SCript>
@endpush