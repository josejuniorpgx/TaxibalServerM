<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 mt-3">
            
                <div class="card border-radius-20 card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('message.list') }}</h5>
                        </div>
                    </div>
                </div>
            
        </div>
        <div class="col-lg-12">
            <div class="card border-radius-20">
                <div class="card-body">
                    {{ Form::model($setting_data,['method' => 'POST','route'=>'privacy-policy-save', 'data-toggle'=>"validator" ] ) }}
                        {{ Form::hidden('id') }}
                        <div class="row">
                            <div class="form-group col-md-12">
                                {{ Form::label('privacy_policy',__('message.privacy_policy'), ['class' => 'form-control-label']) }}
                                {{ Form::textarea('value', null, ['class'=> 'form-control tinymce-privacy_policy' , 'placeholder'=> __('message.privacy_policy') ]) }}
                            </div>
                        </div>
                        {{ Form::submit( __('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@section('bottom_script')
    <script>
        (function($) {
            $(document).ready(function(){
                tinymceEditor('.tinymce-privacy_policy',' ',function (ed) {

                }, 450)
            
            });

        })(jQuery);
    </script>
@endsection
</x-master-layout>