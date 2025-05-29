{{ Form::open(['method' => 'POST','route' => ['mailTemplateSettingsUpdate'],'data-toggle'=>'validator']) }}
{{ Form::hidden('page', $page, ['class' => 'form-control'] ) }}
    <div class="col-md-12 mt-20">
        <div class="row">
            @foreach($mail_template_setting as $key => $value)
                <div class="col-md-4 form-group">
                    <div class="custom-switch custom-switch-color custom-control-inline">
                        <div class="custom-switch-inner">
                            {!! Form::hidden($key, 0) !!}
                            {!! Form::checkbox($key, 1, $value == 1, ['class' => 'custom-control-input bg-success float-right', 'id' => $key]) !!}
                            {!! Form::label($key, __('message.' . $key), ['class' => 'custom-control-label']) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
{{ Form::submit(__('message.save'), ['class'=>"btn btn-md btn-primary float-md-right"]) }}
{{ Form::close() }}
