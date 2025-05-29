<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch border-radius-10">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-radius-10">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 p-2">
                                {{ Form::select('driver_id', [], request('driver_id'), [
                                    'data-ajax--url' => route('ajax-list', ['type' => 'driver']),
                                    'data-ajax--cache' => true,
                                    'data-ajax--delay' => 250,
                                    'class' => 'form-control select2js',
                                    'data-placeholder' => __('message.select_field', ['name' => __('message.driver')]),
                                    'data-allow-clear' => 'true',
                                    'id' => 'driverSearch',
                                ]) }}
                            </div>
                            <div class="col-md-3 p-2">
                                {{ Form::select('ongoing_driver_id', [], request('ongoing_driver_id'), [
                                    'data-ajax--url' => route('ajax-list', ['type' => 'ongoing_driver']),
                                    'data-ajax--cache' => true,
                                    'data-ajax--delay' => 250,
                                    'class' => 'form-control select2js',
                                    'data-placeholder' => __('message.ongoing_driver'),
                                    'data-allow-clear' => 'true',
                                    'id' => 'ongoing_driver_id',
                                ]) }}
                            </div>
                        </div>
                        <div class="border-radius-10" id="map" style="height: 600px;"></div>
                        <div id="maplegend" class="">

                            <div>
                                <img src="{{ asset('images/online.png') }}" /> {{ __('message.online') }}
                            </div>
                            <div>
                                <img src="{{ asset('images/ontrip.png') }}" /> {{ __('message.in_service') }}
                            </div>
                            <div>
                                <img src="{{ asset('images/offline.png') }}" /> {{ __('message.offline') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    @section('bottom_script')
        <script>
            $(document).ready(function() {
                let map;
                let markers = {};

                initializeMap();
                initOngoingDriverSearch();
                initDriverSearch();
                loadDriverList();

                function initializeMap() {
                    const defaultLocation = new google.maps.LatLng(20.947940, 72.955786);
                    const mapOptions = {
                        zoom: 1.5,
                        center: defaultLocation,
                        mapTypeId: google.maps.MapTypeId.ROADMAP
                    };
                    map = new google.maps.Map(document.getElementById('map'), mapOptions);

                    const legend = document.getElementById("maplegend");
                    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legend);
                    $('#maplegend').removeClass('d-none');
                }

                function loadDriverList() {
                    const url = "{{ route('driver_list.map') }}";
                    $.ajax({
                        type: 'GET',
                        url: url,
                        success: function(res) {
                            console.log("res",res.data);
                            if (res.data && res.data.length > 0) {
                                updateDriverMarkers(res.data);
                            }
                        },
                        error: function() {
                            console.error("Failed to load driver list.");
                        }
                    });
                }

                function updateDriverMarkers(locations) {
                    clearAllMarkers();

                    locations.forEach(location => {
                        const driverId = location.id;

                        const icon = location.is_online ?
                            (location.is_available ? "{{ asset('images/online.png') }}" :
                                "{{ asset('images/ontrip.png') }}") :
                            "{{ asset('images/offline.png') }}";

                        const position = new google.maps.LatLng(location.latitude, location.longitude);

                        const marker = new google.maps.Marker({
                            position: position,
                            map: map,
                            icon: icon,
                            title: location.display_name
                        });

                        markers[driverId] = marker;

                        google.maps.event.addListener(marker, 'click', function() {
                            showDriverInfo(driverId, marker);
                        });
                    });
                }

                function clearAllMarkers() {
                    for (const markerId in markers) {
                        if (markers[markerId]) {
                            markers[markerId].setMap(null);
                        }
                    }
                    markers = {};
                }

                function showDriverInfo(driverId, marker) {
                    const url = "{{ route('driverDetail', ['id' => '__id__']) }}".replace('__id__', driverId);
                    $.ajax({
                        type: 'GET',
                        url: url,
                        success: function(res) {
                            if (res.data) {
                                const driver = res.data;
                                const contentString = `
                                    <div class="map_driver_detail">
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fa fa-address-card"></i>: ${driver.display_name}</li>
                                            <li><i class="fa fa-phone"></i>: ${driver.contact_number}</li>
                                            <li><i class="fa fa-taxi"></i>: ${driver.driver_service?.name || '-'}</li>
                                            <li><i class="fa fa-clock"></i>: ${driver.last_location_update_at || '-'}</li>
                                            <li><a href="{{ route('driver.show', '') }}/${driverId}">
                                                <i class="fa fa-eye"></i> {{ __('message.view_form_title', ['form' => __('message.driver')]) }}</a></li>
                                        </ul>
                                    </div>`;
                                const infowindow = new google.maps.InfoWindow({
                                    content: contentString
                                });
                                infowindow.open(map, marker);
                            }
                        }
                    });
                }

                function initDriverSearch() {
                    $('#driverSearch').select2({
                        ajax: {
                            url: "{{ route('ajax-list', ['type' => 'driver']) }}",
                            dataType: 'json',
                            delay: 250,
                            processResults: function(data) {
                                return {
                                    results: data.results
                                };
                            }
                        },
                        placeholder: "{{ __('message.select_field', ['name' => __('message.driver')]) }}",
                        allowClear: true
                    });

                    $('#driverSearch').on('select2:select', function(e) {
                        const driverId = e.params.data.id;
                        zoomToDriver(driverId);
                    });

                    $('#driverSearch').on('select2:clear', function() {
                        resetMap();
                    });
                }

                function initOngoingDriverSearch() {
                    $('#ongoing_driver_id').select2({
                        ajax: {
                            url: "{{ route('ajax-list', ['type' => 'ongoing_driver']) }}",
                            dataType: 'json',
                            delay: 250,
                            processResults: function(data) {
                                return {
                                    results: data.results
                                };
                            }
                        },
                        placeholder: "{{ __('message.select_field', ['name' => __('message.ongoing_driver')]) }}",
                        allowClear: true
                    });
                
                    $('#ongoing_driver_id').on('select2:select', function(e) {
                        const driverId = e.params.data.id;
                        zoomToDriver(driverId);
                    });
                
                    $('#ongoing_driver_id').on('select2:clear', function() {
                        resetMap();
                    });
                }
                

                let selectedMarkerCircle = null;

                function zoomToDriver(driverId) {
                    const url = "{{ route('driver.search', ['id' => '__id__']) }}".replace('__id__', driverId);
                    $.ajax({
                        type: 'GET',
                        url: url,
                        success: function(res) {
                            if (res.data) {
                                const driver = res.data;
                                const position = new google.maps.LatLng(driver.latitude, driver.longitude);

                                if (markers[driverId]) {
                                    // Save original icon to reset later if needed
                                    const originalIcon = markers[driverId].getIcon();

                                    // Change to highlighted icon
                                    markers[driverId].setIcon("{{ asset('images/ontrip.png') }}");

                                    // Bounce animation
                                    markers[driverId].setAnimation(google.maps.Animation.DROP);
                                    setTimeout(() => {
                                        markers[driverId].setAnimation(null);
                                        // Reset icon after 3 seconds
                                        markers[driverId].setIcon(originalIcon);
                                    }, 3000);
                                }

                                // Draw circle around selected marker
                                if (selectedMarkerCircle) {
                                    selectedMarkerCircle.setMap(null); // remove previous
                                }
                                

                                // Move and zoom map
                                map.panTo(position);
                                let currentZoom = map.getZoom();
                                const targetZoom = 15;

                                const zoomInterval = setInterval(() => {
                                    if (currentZoom < targetZoom) {
                                        currentZoom += 1.5;
                                        map.setZoom(currentZoom);
                                    } else {
                                        clearInterval(zoomInterval);
                                    }
                                }, 10);
                            } else {
                                alert("{{ __('message.driver_not_found') }}");
                            }
                        },
                        error: function() {
                            alert("{{ __('message.error_occurred') }}");
                        }
                    });
                }


                function resetMap() {
                    map.setZoom(1.5);
                    map.setCenter(new google.maps.LatLng(20.947940, 72.955786));
                    loadDriverList();
                }
            });
        </script>
    @endsection

</x-master-layout>
