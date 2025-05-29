{{ Form::model($databasebackup_setting, ['method' => 'POST','route' => ['updateAppsSetting'],'data-toggle'=>'validator']) }}
    {{ Form::hidden('id', null, ['class' => 'form-control'] ) }}
    {{ Form::hidden('page', $page, ['class' => 'form-control'] ) }}
    <div class="row">
        <div class="form-group col-lg-6">
            {{ Form::label('backup_type', __('message.backup_type').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
            {{ Form::select('backup_type',['daily' => __('message.daily'), 'monthly' => __('message.monthly'), 'weekly' => __('message.weekly'),'none' => __('message.none')], old('backup_type'),[ 'class' => 'form-control select2js', 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('backup_email', __('message.email').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
            {{ Form::email('backup_email', old('backup_email'), ['placeholder' => __('message.email'), 'class' => 'form-control']) }}
        </div>
    </div>
    {{ Form::submit(__('message.save'), [ 'class' => 'btn btn-md btn-primary float-md-right' ]) }}
{{ Form::close() }}
<script>
    $(document).ready(function() {
        $('.select2js').select2();
    });
</script>