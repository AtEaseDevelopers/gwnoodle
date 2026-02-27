@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('driver_locations.driver_locations')}}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-align-justify"></i>
                            {{ __('driver_locations.summary')}}
                        </div>
                        <div class="card-body">
                            <div id="map"></div>
                        </div>
                    </div>
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             {{ __('driver_locations.transactions')}}
                             {{-- <a class="pull-right" href="{{ route('driverLocations.create') }}"><i class="fa fa-plus-square fa-lg"></i></a> --}}
                         </div>
                         <div class="card-body">
                             <div class="pull-right mr-3">
                                <button id="findDriversOnMap" class="btn btn-primary">{{ __('driver_locations.find_drivers_on_map')}}</button>
                            </div>
                             @include('driver_locations.table')
                              <div class="pull-right mr-3">

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
    $(document).ready(function() {
        let map;
        let markers = [];
        
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: { lat: 3.1949674484886432, lng: 101.73139214267331 }, // Default center
            });
            loadDriverLocations(); // Load all drivers initially
        }
        
        function clearMarkers() {
            markers.forEach(marker => marker.setMap(null));
            markers = [];
        }
        
        // Function to add markers based on backend data
        function addMarkers(data) {
            const infoWindow = new google.maps.InfoWindow();
            const bounds = new google.maps.LatLngBounds(); // Create a bounds object
        
            data.forEach(([position, driver_name, driver_employeeid, kelindan_employeeid, kelindan_name, lorryno, date]) => {
                const marker = new google.maps.Marker({
                    position,
                    map,
                    icon: 'http://maps.gstatic.com/mapfiles/ms2/micons/bus.png',
                    title: `<b>${lorryno}</b><hr>Driver Employee ID: ${driver_employeeid}<br>Driver Name: ${driver_name}<hr>Kelindan Employee ID: ${kelindan_employeeid}<br>Kelindan Name: ${kelindan_name}<hr>Last Active: ${date}`,
                    label: {
                        text: `${driver_name}`,
                        color: 'black',
                        fontSize: '19px',
                        className: 'marker-position',
                    },
                    optimized: false,
                });
        
                marker.addListener("click", () => {
                    infoWindow.close();
                    infoWindow.setContent(marker.getTitle());
                    infoWindow.open(marker.getMap(), marker);
                });
        
                // Extend the bounds object with the marker's position
                bounds.extend(marker.getPosition());
        
                markers.push(marker);
            });
        
            // Adjust the map to fit all markers
            map.fitBounds(bounds);
        }
        
        // Function to add markers based on checkbox data
        function addMarkersFromCheckboxes() {
            const selectedDrivers = [];
        
            $('.checkbox-select:checked').each(function() {
                const lat = $(this).data('latitude');
                const lng = $(this).data('longitude');
                const driverName = $(this).data('driver-name'); // Corrected attribute name
                const driverEmployeeId = $(this).data('driver-employeeid');
                const kelindanEmployeeId = $(this).data('kelindan-employeeid');
                const kelindanName = $(this).data('kelindan-name'); // Corrected attribute name
                const lorryno = $(this).data('lorryno');
                const date = $(this).data('date');
        
                if (lat && lng) {
                    selectedDrivers.push([
                        { lat: parseFloat(lat), lng: parseFloat(lng) }, // position
                        driverName,
                        driverEmployeeId,
                        kelindanEmployeeId,
                        kelindanName,
                        lorryno,
                        date
                    ]);
                }
            });
        
            if (selectedDrivers.length > 0) {
                clearMarkers();
                addMarkers(selectedDrivers);
            } else {
                // If no checkboxes are selected, load all drivers
                loadDriverLocations();
            }
        }
        
        function loadDriverLocations(selectedDrivers = []) {
            $.ajax({
                type: "GET",
                url: "{{ route('driverLocations.getDriverSummary') }}",
                cache: false,
                data: { drivers: selectedDrivers },
                success: function(data) {
                    clearMarkers();
                    addMarkers(data);
                    if (data.length > 0 && selectedDrivers.length === 0) {
                        map.setCenter({ lat: data[0][0]['lat'], lng: data[0][0]['lng'] });
                    }
                },
                error: function(jqXHR, status, error) {
                    console.error('AJAX error:', status, error);
                }
            });
        }
        
        function getSelectedDriverIds() {
            let selectedDrivers = [];
            $('.checkbox-select:checked').each(function() {
                let id = $(this).val();
                if (id) selectedDrivers.push(id);
            });
            return selectedDrivers;
        }
        
        // Event handler for the "Find Drivers on Map" button
        $('#findDriversOnMap').on('click', function() {
            addMarkersFromCheckboxes();
        });
        
        $('#selectAll').change(function() {
            let isChecked = $(this).is(':checked');
            $('.checkbox-select').prop('checked', isChecked);
        });
        
        // Initialize the map on page load
        initMap();
    });
    </script>


    <script>
        $(document).keyup(function(e) {
            if(e.altKey && e.keyCode == 78){
                $('.card .card-header a')[0].click();
            }
        });

        $(document).on("click", "#masssave", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to save 1 row"
            }else{
                m = "Confirm to save " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Save View',
                content: m,
                buttons: {
                    Yes: function() {
                        masssave(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });

        });

        function masssave(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/drivers/masssave",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    toastr.success('Please find Save View ID: '+response, 'Save Successfully', {showEasing: "swing", hideEasing: "linear", showMethod: "fadeIn", hideMethod: "fadeOut", positionClass: "toast-bottom-right", timeOut: 0, allowHtml: true });
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }

        $(document).on("click", "#massdelete", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to delete 1 row!"
            }else{
                m = "Confirm to delete " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Delete',
                content: m,
                buttons: {
                    Yes: function() {
                        massdelete(window.checkboxid);
                    },
                    No: function() {
                        return;
                    }
                }
            });
        });

        $(document).on("click", "#massactive", function(e){
            var m = "";
            if(window.checkboxid.length == 0){
                noti('i','Info','Please select at least one row');
                return;
            }else if(window.checkboxid.length == 1){
                m = "Confirm to update 1 row"
            }else{
                m = "Confirm to update " + window.checkboxid.length + " rows!"
            }
            $.confirm({
                title: 'Mass Update',
                content: m,
                buttons: {
                    Active: function() {
                        massupdatestatus(window.checkboxid,1);
                    },
                    Unactive: function() {
                        massupdatestatus(window.checkboxid,0);
                    },
                    somethingElse: {
                        text: 'Cancel',
                        btnClass: 'btn-gray',
                        keys: ['enter', 'shift']
                    }
                }
            });

        });
        function massdelete(ids){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/drivers/massdestroy",
                type:"POST",
                data:{
                ids: ids
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Delete Successfully',response+' row(s) had been deleted.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
        function massupdatestatus(ids,status){
            ShowLoad();
            $.ajax({
                url: "{{config('app.url')}}/drivers/massupdatestatus",
                type:"POST",
                data:{
                ids: ids,
                status: status
                ,_token: "{{ csrf_token() }}"
                },
                success:function(response){
                    window.checkboxid = [];
                    $('.buttons-reload').click();
                    noti('s','Update Successfully',response+' row(s) had been updated.')
                },
                error: function(error) {
                    noti('e','Please contact your administrator',error.responseJSON.message)
                    HideLoad();
                }
            });
        }
    </script>
@endpush
