<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height border-radius-20">
                    <div class="card-header d-flex flex-sm-nowrap flex-wrap justify-content-between align-items-center">
                        <div class="header-title w-100">
                            <h4 class="card-title mb-0">{{ $pageTitle ?? ''}}</h4>
                        </div>
                        <div class="d-flex justify-content-end w-100 flex-wrap mt-sm-0 mt-3">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm border-radius-10 dropdown-toggle me-2" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-export"></i> {{ __('message.export') }}
                                </button>
                                <ul class="dropdown-menu border-radius-10" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item text-decoration-none border-radius-10" href="#" id="export-csv"><i class="fas fa-file-csv"></i> {{__('message.excel')}}</a></li>
                                    <li><a class="dropdown-item text-decoration-none border-radius-10" href="#" id="export-pdf"><i class="fas fa-file-pdf"></i> {{__('message.pdf')}}</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-warning btn-sm border-radius-10 ml-2" type="button" id="openFilterModal" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="fas fa-filter"></i> {{ __('message.filter') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body table-responsive">
                        <table id="basic-table" class="table table-hover mb-1 text-center" role="grid">
                            <thead>
                                <tr>
                                    <th scope='col'>{{ __('message.ride_request_id') }}</th>
                                    <th scope='col'>{{ __('message.title_name',['title' => __('message.rider')]) }}</th>
                                    <th scope='col'>{{ __('message.title_name',['title' => __('message.driver')]) }}</th>
                                    <th scope='col'>{{ __('message.pickup_date_time') }}</th>
                                    <th scope='col'>{{ __('message.drop_date_time') }}</th>
                                    <th scope='col'>{{ __('message.total_amount') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.admin_commission') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.driver_commission') }}</th>
                                    <th scope='col'>{{ __('message.created_at') }}</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="font-weight-700">{{ __('message.total') }}</td>
                                    <td id="total-payment-amount" class="font-weight-700">0.00</td>
                                    <td id="total-admin-commission" class="font-weight-700">0.00</td>
                                    <td id="total-driver-commission" class="font-weight-700">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>                            
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('report.admin-filter')

    @section('bottom_script')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    dropdownParent: $('#filterModal'),
                });
                $('#basic-table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    stateSave: true,
                    ajax: {
                        url: '{{ route("adminEarningReport") }}',
                        data: function(d) {
                            d.from_date = $('#from_date_main').val();
                            d.to_date = $('#to_date_main').val();
                            d.rider_id = $('#rider_id').val();
                            d.driver_id = $('#driver_id').val();
                        },
                        dataSrc: function (json) {
                            $('#total-payment-amount').html(json.totalAmount.toFixed(2));
                            $('#total-admin-commission').html(json.totalAdminCommission.toFixed(2));
                            $('#total-driver-commission').html(json.totalDriverCommission.toFixed(2));
                            return json.data;
                        }
                    },
                    columns: [
                        { data: 'id' },
                        { data: 'rider_display_name' },
                        { data: 'driver_display_name' },
                        { data: 'pickup_date_time' },
                        { data: 'drop_date_time' },
                        { data: 'payment_total_amount' ,className: 'text-right' },
                        { data: 'payment_admin_commission' ,className: 'text-right' },
                        { data: 'payment_driver_commission' ,className: 'text-right' },
                        { data: 'created_at' }
                    ],
                    
                });
        
                $('#admin_report_filter_form').on('submit', function(e) {
                    e.preventDefault();
                    $('#basic-table').DataTable().ajax.reload();
                });
        
                $('#reset-filter-btn').on('click', function() {
                    $('#from_date_main').val('').trigger('change');
                    $('#to_date_main').val('').trigger('change');
                    $('#rider_id').val('').trigger('change');
                    $('#driver_id').val('').trigger('change');
                    $('#basic-table').DataTable().ajax.reload();
                });
        
                $('#export-csv').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const riderId = $('#rider_id').val() || '';
                    const driverId = $('#driver_id').val() || '';
                    const exportUrl = `{{ route('download-admin-earning') }}?from_date=${fromDate}&to_date=${toDate}&rider_id=${riderId}&driver_id=${driverId}`;
                    window.location.href = exportUrl;
                });
        
                $('#export-pdf').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const riderId = $('#rider_id').val() || '';
                    const driverId = $('#driver_id').val() || '';
                    const exportUrl = `{{ route('download-adminearningpdf') }}?from_date=${fromDate}&to_date=${toDate}&rider_id=${riderId}&driver_id=${driverId}`;
                    window.location.href = exportUrl;
                });
                
            });
        </script>
    
    @endsection
</x-master-layout>