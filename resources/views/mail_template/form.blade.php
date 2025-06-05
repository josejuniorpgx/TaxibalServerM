<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        {!! Form::open(['route' => ['mail-template.store'], 'method' => 'post']) !!}
        {!! Form::hidden('type',$type) !!}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-radius-20">
                        <div class="card-header d-flex justify-content-between border-bottom-0"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                            <div class="header-title">
                                <h4 class="card-title">{{ $pageTitle ?? __('message.list') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card border-radius-20">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ Form::label('subject', __('message.subject').' <span class="text-danger">*</span>', ['class' => 'form-control-label'],false) }}
                                    {{ Form::text('subject', isset($data) ? $data->subject : old('subject'), ['placeholder' => __('message.subject'), 'class' => 'form-control', 'required']) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ Form::textarea('description',isset($data) ? $data->description : old('description'), ['rows' => 5,'class'=> 'form-control tinymce-mail_description', 'placeholder'=> __('message.mail_description')]) }}
                                </div>
                            </div>
                            {{ Form::submit( __('message.save'), ['class'=>'btn border-radius-10 btn-primary float-right']) }}
                        </div>
                    </div>
                </div>
            </div>
        {{ Form::close() }}
    </div>
    @section('bottom_script')
        <script>
            (function($) {
                $(document).ready(function(){
                    tinymceEditor('.tinymce-mail_description',' ',function (ed) {
                    }, 450)
                });
            })(jQuery);
        </script>
    @endsection
</x-master-layout>
