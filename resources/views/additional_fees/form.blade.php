<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['additionalfees.update', $id], 'method' => 'patch' ]) !!}
        @else
            {!! Form::open(['route' => ['additionalfees.store'], 'method' => 'post' ]) !!}
        @endif
        <div class="row">
            <div class="col-lg-12 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                        <a href="{{route('additionalfees.index')}}" class="float-right btn btn-sm border-radius-10 btn-primary me-2" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
                    </div>

                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('title', __('message.title').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::text('title', old('title'),[ 'placeholder' => __('message.title'),'class' =>'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('status',__('message.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                    {{ Form::select('status',[ '1' => __('message.active'), '0' => __('message.inactive') ], old('status'), [ 'class' =>'form-control select2js','required']) }}
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
    @endsection
</x-master-layout>
