<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}} Archived</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/x-icon" href="{{config('app.url')}}/favicon.ico">
    <!-- Bootstrap 4.1.1 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.css" integrity="sha512-rBi1cGvEdd3NmSAQhPWId5Nd6QxE8To4ADjM2a6n0BrqQdisZ/RPUlm0YycDzvNL1HHAh1nKZqI0kSbif+5upQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/css/coreui.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@icon/coreui-icons-free@1.0.1-alpha.1/coreui-icons-free.css">

     <!-- PRO version // if you have PRO version licence than remove comment and use it. -->
    {{--<link rel="stylesheet" href="https://unpkg.com/@coreui/icons@1.0.0/css/brand.min.css">--}}
    {{--<link rel="stylesheet" href="https://unpkg.com/@coreui/icons@1.0.0/css/flag.min.css">--}}
     <!-- PRO version -->

    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.3.0/css/flag-icon.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/css/bootstrap-select.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.4.8/sumoselect.min.css" integrity="sha512-vU7JgiHMfDcQR9wyT/Ye0EAAPJDHchJrouBpS9gfnq3vs4UGGE++HNL3laUYQCoxGLboeFD+EwbZafw7tbsLvg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css">

    <style>
        .asterisk{
            color:red !important;
        }
        tfoot {
            display: table-row-group;
        }
        /* #dataTableBuilder tfoot th input{
            width:100%;
            border:none;
        }
        #dataTableBuilder td{
            padding: 0px !important; 
            vertical-align: middle !important;
        }
        #dataTableBuilder th{
            padding: 0px !important; 
        } */

        .table.table-striped.table-bordered tfoot th input{
            width:100%;
            border:none;
        }
        .table.table-striped.table-bordered td{
            padding: 0px !important; 
            vertical-align: middle !important;
        }
        .table.table-striped.table-bordered th{
            padding: 0px !important; 
        }

        #loading {
            position: fixed;
            display: block;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            text-align: center;
            opacity: 0.7;
            background-color: #fff;
            z-index: 99999;
        }

        #loading-image {
            z-index: 999999;
        }
        .dataTables_filter{
            display: none;
        }
        .dt-button.buttons-columnVisibility.active{
            background-color: lightgray;
        }
        .dt-button.button-page-length.active{
            background-color: lightgray;            
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.active, .dataTables_wrapper .dataTables_paginate .paginate_button.active:hover{
            color: #333 !important;
            border: 1px solid rgba(0, 0, 0, 0.3);
            background-color: rgba(230, 230, 230, 0.1);
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, rgba(230, 230, 230, 0.1)), color-stop(100%, rgba(0, 0, 0, 0.1)));
            background: -webkit-linear-gradient(top, rgba(230, 230, 230, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
            background: -moz-linear-gradient(top, rgba(230, 230, 230, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
            background: -ms-linear-gradient(top, rgba(230, 230, 230, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
            background: -o-linear-gradient(top, rgba(230, 230, 230, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
            background: linear-gradient(to bottom, rgba(230, 230, 230, 0.1) 0%, rgba(0, 0, 0, 0.1) 100%);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled, .dataTables_wrapper .dataTables_paginate .paginate_button.disabled a{
            cursor: no-drop;
        }
        .dataTables_paginate .pagination{
            margin-bottom: 0px;
        }
        .dataTables_paginate{
            padding-top: 12px !important;
        }

        /* multiline */
        /* .app-header.navbar{
            background-color: #ffd64d;
        }
        .app-header.navbar .nav-item a{
            color: black;
        }
        .app-header.navbar .navbar-toggler-icon{
            color: black;
        }
        .app-header{
            border-bottom: 1px solid #ffd64d;
        }
        .app-body .sidebar{
            background-color: #fffaea;
        }
        .app-body span{
            color: black;
        }
        .app-body .nav-dropdown-items .nav-link.active{
            background: #fff1c3;
        } */
        .app-body .nav-dropdown-items .nav-link.active span{
            /* color: #8a8576; */
        }
        /* .app-body .nav-dropdown-items .nav-link{
            background: #fff6d6;
        }
        .sidebar .nav-dropdown.open{
            background: #fff6d6;
        }
        .breadcrumb{
            background: #fffaea;
            border-bottom: none;
        }
        .main{
            background: #fffaea;
            border-left: 1px solid #c8ced3;
        }
        .app-footer{
            border-left: 1px solid #c8ced3;
            background: #fffaea;
        }
        .card-body{
            background: #fff6d6;
        }
        .card-header{
            background: #ffd64d;
        }
        .card-header a{
            color: black;
        }
        .card-header a:hover{
            background-color: #ffd64d;
        }
        #dataTableBuilder thead{
            background: #ffd64d;
        }
        #dataTableBuilder tfoot input{
            background: #fffaea;
        }
        #dataTableBuilder tbody .odd{
            background: white !important;
        }
        #dataTableBuilder tbody .even{
            background: #fffaea !important;
        }
        .dt-button-collection .dt-button{
            background: #fffaea;
        }
        .dt-button-collection .dt-button.active{
            background: #fff1c3;
        }
        .dropdown-header{
            background: #ffd64d;
            color: black;
        }
        .dropdown-item{
            background-color: #fffaea;
        } */
        body{
            font-family: sans-serif;
            /* font-size: 16px; */
        }
        /* width */
        ::-webkit-scrollbar {
            width: 5px;
        }

        /* Track */
        ::-webkit-scrollbar-track {
            background: #fffaea; 
            border-radius: 10px;
        }
        
        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #888; 
            border-radius: 10px;
        }

        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #555; 
        }


        .table th {
            position: relative;
        }
        .resizer {
            /* Displayed at the right side of column */
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            cursor: col-resize;
            user-select: none;
        }
        .resizer:hover,
        .resizing {
            border-right: 2px solid blue;
        }
        @media only screen and (max-width: 850px) {
            .dt-buttons {
                display: none;
            }
            .dt-buttons a {
                display: block;
            }
        }      
        @media only screen and (min-width: 851px) {
            .dt-buttons {
                display: block;
            }
        }
        #dataTableBuilder_wrapper, #dataTableBuilder_do_wrapper, #dataTableBuilder_claim_wrapper, #dataTableBuilder_compound_wrapper, #dataTableBuilder_advance_wrapper, #dataTableBuilder_loan_wrapper, #dataTableBuilder_bonus__wrapper, #dataTableBuilder_pdo_wrapper {
            /* overflow-y: scroll;  */
            display: block;
        }
        #dataTableBuilder_wrapper tr, #dataTableBuilder_do_wrapper tr, #dataTableBuilder_claim_wrapper tr, #dataTableBuilder_compound_wrapper tr, #dataTableBuilder_advance_wrapper tr, #dataTableBuilder_loan_wrapper tr, #dataTableBuilder_bonus__wrapper tr, #dataTableBuilder_pdo_wrapper tr{
            white-space: nowrap;
        }
        .SumoSelect .select-all{
            height: 40px;
        }

        .dataTableBuilderDiv{
            overflow-x: auto;
            overflow-y: auto;
            height: calc(100vh - 364px);
            width: 100%;
        }
        thead th{
            position: sticky !important;
            top: 0px !important;
            background-color: white;
            z-index: 100;
        }
        tfoot th{
            position: sticky !important;
            top: 20px !important;
            background-color: white;
            z-index: 100;
        }
    </style>
    @stack('styles')
</head>
<body class="app header-fixed sidebar-fixed aside-menu-fixed sidebar-lg-show">
    <div id="loading" class="d-flex align-items-center justify-content-center">
        <div id='loading-image' class="spinner-border" role="status">
            <span class="sr-only">Loading...</span>
          </div>
      </div>
<header class="app-header navbar">
    <button class="navbar-toggler sidebar-toggler d-lg-none mr-auto" type="button" data-toggle="sidebar-show">
        <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand" href="{{config('app.url').'/archived'}}">
        <img class="navbar-brand-full" src="{{config('app.url')}}/logo.png" height="30"
             alt="Multiline Logo">
        <img class="navbar-brand-minimized" src="{{config('app.url')}}/logo.png" width="30"
             height="30" alt="Multiline Logo">
    </a>
    <button class="navbar-toggler sidebar-toggler d-md-down-none" type="button" data-toggle="sidebar-lg-show">
        <span class="navbar-toggler-icon"></span>
    </button>

    <ul class="nav navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" style="margin-right: 10px" data-toggle="dropdown" href="#" role="button"
               aria-haspopup="true" aria-expanded="false">
                {{ Auth::user()->name }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-header text-center">
                    <strong>Account</strong>
                </div>
                <a href="{{config('app.url')}}" class="dropdown-item btn btn-default btn-flat">
                    <i class="fa fa-home"></i>Home
                </a>
                <a href="{{ url('/logout') }}" class="dropdown-item btn btn-default btn-flat"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-lock"></i>Logout
                </a>
                <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</header>

<div class="app-body">
    @include('archived.layouts.sidebar')
    <main class="main">
        @yield('content')
    </main>
</div>
<footer class="app-footer">
    <div>
        <a href="{{config('app.url').'/archived'}}">{{config('app.name')}} </a>
        <span>&copy; {{date("Y")}} {{config('app.name')}}</span>
    </div>
    <div class="ml-auto">
        <span>Powered by</span>
        <a href="{{config('app.url').'/archived'}}">{{config('app.name')}}</a>
    </div>
</footer>
</body>
<!-- jQuery 3.1.1 -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.37/js/bootstrap-datetimepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js" integrity="sha512-mh+AjlD3nxImTUGisMpHXW03gE6F4WdQyvuFRkjecwuWLwD2yCijw4tKA3NsEFpA1C3neiKhGXPSIGSfCYPMlQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@2.1.16/dist/js/coreui.min.js"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script> --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js" integrity="sha512-yDlE7vpGDP7o2eftkCiPZ+yuUyEcaBwoJoIhdXv71KZWugFqEphIS3PU60lEkFaz8RxaVsMpSvQxMBaKVwA5xg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/df-number-format/2.1.6/jquery.number.min.js" integrity="sha512-3z5bMAV+N1OaSH+65z+E0YCCEzU8fycphTBaOWkvunH9EtfahAlcJqAVN2evyg0m7ipaACKoVk6S9H2mEewJWA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.4.8/jquery.sumoselect.min.js" integrity="sha512-Ut8/+LO2wW6HfMEz1vxHpiwMMQfw7Yf/0PdpTERAbK2VJQt4eVDsmFL269zUCkeG/QcEcc/tcORSrGHlP89nBQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.1.0/chartjs-plugin-datalabels.min.js" integrity="sha512-Tfw6etYMUhL4RTki37niav99C6OHwMDB2iBT5S5piyHO+ltK2YX8Hjy9TXxhE1Gm/TmAV0uaykSpnHKFIAif/A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
<script src="{{config('app.url')}}/resizableColumns.min.js"></script>
<script>
    function HideLoad(){
        // $('#dataTableBuilder_wrapper').closest('.card').height($('.app-body').height()-100);
        $('#loading').removeClass('d-flex');
        $('#loading').hide();
    }
    function ShowLoad(){
        $('#loading').addClass('d-flex');
        $('#loading').show();
    }
    function noti(tp,tt,mg){
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-bottom-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
        if(tp == 'e'){
            toastr.error(mg, tt);
        }else if(tp=='i'){
            toastr.info(mg, tt);
        }else if(tp=='w'){
            toastr.warning(mg, tt);
        }else if(tp=='s'){
            toastr.success(mg, tt);
        }
    }
    var resize = 1;
    $(document).ready(function() {
        $('.fa.fa-align-justify').on('click', function(event) {
            $('.dt-buttons').toggle();
        });
        if($("#dataTableBuilder").length == 1){
            $.fn.dataTable.ext.errMode = 'none';
        }
    });
    
</script>
@stack('scripts')

</html>
