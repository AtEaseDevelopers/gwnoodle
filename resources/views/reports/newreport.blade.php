@extends('layouts.app')

@section('css')
    @include('layouts.datatables_css')
@endsection

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">{{ __('report.reports') }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="#"></a>
            </li>
            <li class="breadcrumb-item active">{{ __('report.run') }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('flash::message')
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong></strong>
                                  <a href="#" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                <iframe id="newreportframe" src="https://report.tytcloud.com/frameset?__report=test_driver.rptdesign&__format=pdf" title="description"></iframe>
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection

@push('scripts')
    @include('layouts.datatables_js')
    <script>
        $(document).keyup(function(e) {
            if (e.key === "Escape") {
                $('.card .card-header a')[0].click();
            }
        });
        $(document).ready(function () {
            HideLoad();
        });
    </script>
@endpush