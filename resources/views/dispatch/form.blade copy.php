<x-master-layout :assets="$assets ?? []">
    <style>
        .ui-autocomplete {
            background: #fff; /* White background */
            border: 1px solid #ddd; /* Light border */
            max-height: 200px; /* Limit height */
            overflow-y: auto; /* Enable scroll if needed */
            font-size: 14px;
            z-index: 1050 !important; /* Ensure it appears on top */
            position: absolute !important; /* Ensure it stays independent */
            width: 100% !important; /* Match input width */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); /* Soft shadow for visibility */
        }
        
        .ui-menu {
            padding: 0;
            margin: 0;
            list-style: none;
            width: 25.50% !important; /* Ensure full width */
        }
        
        .ui-menu-item {
            padding: 8px 12px; /* Spacing inside suggestions */
            cursor: pointer;
            white-space: nowrap; /* Prevent text wrapping */
            overflow: hidden;
            text-overflow: ellipsis; /* Trim long text */
        }
        
        .ui-menu-item:hover, .ui-state-active {
            background: #4788ff; /* Highlight color */
            color: #fff; /* White text */
            font-weight: bold;
        }
        
        @media (max-width: 1399px)
        {
            .ui-menu {
                width: 24% !important; /* Ensure full width */
            }
        }
        
        @media (max-width: 1199px)
        {
            .ui-menu {
                width: 29% !important; /* Ensure full width */
            }
        }
        
        @media (max-width: 992px)
        {
            .ui-menu {
                width: 90% !important; /* Ensure full width */
            }
        }
        
        
    </style>
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['dispatch.update', $id], 'method' => 'patch', 'data-toggle'=>'validator' ]) !!}
        @else
            {!! Form::open(['route' => ['dispatch.store'], 'method' => 'post', 'data-toggle'=>'validator' ]) !!}
        @endif
        <div class="row">
            <div class="col-12">
                <?php echo $button; ?>
            </div>
            <div class="col-lg-12 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                {{ Form::hidden('start_latitude', null, [ 'id' => 'start_latitude'] ) }}
                                {{ Form::hidden('start_longitude', null, [ 'id' => 'start_longitude']) }}
                                {{ Form::hidden('end_latitude', null, [ 'id' => 'end_latitude'] ) }}
                                {{ Form::hidden('end_longitude', null, [ 'id' => 'end_longitude']) }}
                                <div class="form-group col-md-4">
                                    {{ Form::label('rider_id', __('message.rider').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::select('rider_id', isset($id) ? [ optional($data->rider)->id => optional($data->rider)->display_name ] : [] , old('rider_id') , [
                                            'data-ajax--url' => route('ajax-list', [ 'type' => 'rider' ]),
                                            'class' =>'form-control select2js',
                                            'data-placeholder' => __('message.select_field', [ 'name' => __('message.rider') ]),
                                            'required'
                                        ])
                                    }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('start_address', __('message.start_address').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false) }}
                                    {{ Form::text('start_address', old('start_address'),[ 'placeholder' => __('message.start_address'),'class' =>'form-control', 'required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('end_address', __('message.end_address').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false) }}
                                    {{ Form::text('end_address', old('end_address'),[ 'placeholder' => __('message.end_address'),'class' =>'form-control', 'required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('service_id', __('message.service').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    <a class="float-right serviceList" href="javascript:void(0)"><i class="ri-refresh-line"></i></a>
                                    {{ Form::select('service_id', isset($id) ? [ optional($data->service)->id => optional($data->service)->display_name ] : [] , old('service_id') , [
                                            'class' => 'select2js form-group service',
                                            'required',
                                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.service') ]),
                                        ])
                                    }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('driver_id', __('message.driver'), ['class' => 'form-control-label'], false) }}
                                    <a class="float-right driverList" href="#"><i class="ri-refresh-line"></i></a>
                                    {{ Form::select('driver_id', isset($id) ? [ optional($data->driver)->id => optional($data->driver)->display_name ] : [] , old('driver_id') , [
                                            'class' => 'select2js form-group driver',
                                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.driver') ]),
                                        ])
                                    }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('schedule_datetime', __('message.schedule_datetime'), [ 'class' => 'form-control-label']) }}
                                    {{ Form::text('schedule_datetime', old('schedule_datetime'),[ 'placeholder' => __('message.schedule_datetime'),'class' => 'form-control datetimepicker']) }}
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex">
                                <h5>{{ __('message.drop_address') }}</h5>
                                <button type="button" id="add_button" class="btn btn-sm btn-secondary float-right ml-2 mb-2">{{ __('message.add') }}</button>
                            </div>
                            
                            <div id="dropAddress" class="clone-master">
                                <div class="row clone-item" id="row_0" row="0" data-id="0">
                                    <div class="form-group col-md-6">
                                        <input type="textbox" name="search_drop_location[]" value="" class="form-control drop_location" id='search_drop_location_0' row="0" placeholder="{{ __('message.drop_address') }}"  />
                                        <input type="hidden" name="drop_location[]" value="" class="form-control hidden_drop_location" id='drop_location_0' row="0" />
                                    </div>
                                    <a href="javascript:void(0)" id="remove_0" class="removebtn text-danger mt-2 p-1" row="0">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                            <hr>
                            {{ Form::submit( __('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
    @section('bottom_script')
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
        <script>
            $(function () {
                let drop_location = [];
            
                $(document).ready(function () {
                    $('#start_address, #end_address').val('');
                    $(".drop_location input[type='text']").val('');
                    drop_location = []; 
                    resetSequenceNumbers();
                });
            
                function resetSequenceNumbers() {
                    $("#dropAddress .clone-item").each(function (i) {
                        let placeholder = "{{ __('message.drop_address') }}";
                        $(this).find('input.drop_location').attr('placeholder', placeholder + ' ' + (i + 1));
                    });
                }
            
                $("#add_button").click(function () {
                    let cloneMaster = $(".clone-master");
                    let lastCount = parseInt(cloneMaster.find('.clone-item:last').attr('row')) || 0;
                    let newCloneCount = lastCount + 1;
            
                    let newClone = cloneMaster.find('.clone-item:first').clone();
                    newClone.attr('id', 'row_' + newCloneCount).attr('row', newCloneCount);
                    newClone.find('input.drop_location')
                        .attr('id', 'search_drop_location_' + newCloneCount)
                        .attr('row', newCloneCount)
                        .val('');
                    newClone.find('input.hidden_drop_location')
                        .attr('id', 'drop_location_' + newCloneCount)
                        .attr('row', newCloneCount)
                        .val('');
                    newClone.find('.removebtn').show().fadeIn(300);
                    newClone.find('[id^="remove_"]').attr('id', "remove_" + newCloneCount).attr('row', newCloneCount);
                    cloneMaster.append(newClone);
            
                    bindAutocomplete('#search_drop_location_' + newCloneCount);
                    resetSequenceNumbers();
                });
            
                $(document).on('click', '.removebtn', function () {
                    let row = $(this).attr('row');
                    let total_row = $('#dropAddress .clone-item').length;
            
                    if (!confirm("{{ __('message.delete_msg') }}")) return false;
                    if (total_row === 1) $("#add_button").trigger('click');
            
                    $('#row_' + row).remove();
                    resetSequenceNumbers();
                });
            
                function debounce(func, delay) {
                    let timer;
                    return function (...args) {
                        clearTimeout(timer);
                        timer = setTimeout(() => func.apply(this, args), delay);
                    };
                }
            
                function bindAutocomplete(selector) {
                    $(selector).autocomplete({
                        source: function (request, response) {
                            $.ajax({
                                url: '/api/place-autocomplete-api',
                                method: 'GET',
                                data: {
                                    search_text: request.term,
                                    language: 'en',
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function (data) {
                                    let predictions = data.suggestions || [];
                                    let suggestions = predictions.map((suggestion) => ({
                                        label: suggestion.placePrediction.text.text,
                                        value: suggestion.placePrediction.text.text,
                                        place_id: suggestion.placePrediction.placeId // Store place_id
                                    }));
                
                                    response(suggestions);
                                },
                                error: function () {
                                    console.error("Error fetching autocomplete data.");
                                }
                            });
                        },
                        minLength: 2,
                        select: function (event, ui) {
                            let selectedPlaceId = ui.item.place_id;
                            let formatted_address = ui.item.value; 
                            let inputField = $(this);
                        
                            $.ajax({
                                url: '/api/place-detail-api',
                                method: 'GET',
                                data: {
                                    placeid: selectedPlaceId,
                                    _token: "{{ csrf_token() }}"
                                },
                                success: function (data) {
                                    if (data) {
                                        let location = data.location;
                                        let fullAddress = data.formattedAddress;
                                        let lat = location.latitude;
                                        let lng = location.longitude;
                        
                                        inputField.val(fullAddress).trigger('change'); // ✅ Ensure change event triggers
                        
                                        if (inputField.attr('id') === 'start_address') {
                                            $('#start_latitude').val(lat);
                                            $('#start_longitude').val(lng);
                                        } 
                                        
                                        if (inputField.attr('id') === 'end_address') {
                                            $('#end_latitude').val(lat);
                                            $('#end_longitude').val(lng);
                                            $('#end_address').val(fullAddress).trigger('change'); // ✅ Ensure value is set properly
                                        }
                        
                                        serviceList(lat, lng);
                                    } else {
                                        console.error("❌ Place details not found.");
                                    }
                                },
                                error: function () {
                                    console.error("Error fetching place details.");
                                }
                            });
                        
                            return false;
                        }
                        
                    });
                }
                
                bindAutocomplete('#start_address');
                bindAutocomplete('#end_address');

                $('.drop_location').each(function () {
                    bindAutocomplete('#' + $(this).attr('id'));
                });
                $(document).on('change', '.service', function ()
                {
                    var service_id = $(this).val();
                    $('.driver').empty();
                    if( service_id != null ) {
                        driverList(service_id);
                    }
                })
            });
            
            $('.serviceList').on('click',function() {
                start_latitude = $('#start_latitude').val();
                start_longitude = $('#start_longitude').val();

                if( start_latitude != '' && start_longitude != '' ) {
                    serviceList(start_latitude,start_longitude);
                } else {
                    $('.service').empty();
                }
            });

            function serviceList(latitude, longitude) {
                var route = "{{ route('ajax-list',[ 'type' => 'service_for_ride']) }}&latitude="+latitude+"&longitude="+longitude;
                route = route.replaceAll('amp;','');
                
                $.ajax({
                    url: route,
                    success: function(result){
                        $('.service').select2({
                            width : '100%',
                            placeholder: "{{ __('message.select_name',[ 'select' => __('message.service') ]) }}",
                            data: result.results
                        });

                        $(".service").val(latitude).trigger('change');
                    }
                })
            }

            $('.driverList').on('click',function() {
                service_id = $('#service_id').val();
                if( service_id != null ) {
                    driverList(service_id);
                } else {
                    $('.driver').empty();
                }
            });
            
            function driverList(service_id)
            {
                latitude = $('#start_latitude').val();
                longitude = $('#start_longitude').val();

                var route = "{{ route('ajax-list',[ 'type' => 'driver_for_ride']) }}&service_id="+service_id+"&latitude="+latitude+"&longitude="+longitude;
                route = route.replaceAll('amp;','');
                
                $.ajax({
                    url: route,
                    success: function(result){
                        $('.driver').select2({
                            width : '100%',
                            placeholder: "{{ __('message.select_name',[ 'select' => __('message.driver') ]) }}",
                            data: result.results
                        });

                        $(".driver").val(service_id).trigger('change');
                    }
                })
            }
        </script>
    @endsection
</x-master-layout>
