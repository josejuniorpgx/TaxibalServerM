<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between border-bottom-0"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle ?? __('message.list') }}</h4>
                        </div>
                        @if (count($data) < 3)
                            <a href="{{ route('why-choose.create') }}"class="float-right btn btn-md border-radius-10 btn-primary loadRemoteModel">{{ __('message.why_choose') }}</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card border-radius-20">
                    <div class="card-body">
                        {{ Form::open(['method' => 'POST', 'route' => [ 'frontend.website.information.update', 'why_choose'], 'enctype' => 'multipart/form-data', 'data-toggle'=>'validator']) }}
                            <div class="row">
                                @foreach($why_choose as $key => $value)
                                    @if( in_array( $key, ['title', 'subtitle'] ))
                                        <div class="col-md-6 form-group">
                                            {{ Form::label($key, __('message.'.$key),['class'=>'form-control-label'] ) }}
                                            {{ Form::text($key, $value ?? null,[ 'placeholder' =>  __('message.'.$key), 'class' => 'form-control' ]) }}
                                        </div>
                                    @else
                                        <div class="form-group col-md-4">
                                            <label class="form-control-label" for="{{ $key }}">{{ __('message.'.$key) }} </label>
                                            <div class="custom-file">
                                                <input type="file" name="{{ $key }}" class="custom-file-input" accept="image/*" data--target="{{$key}}_image_preview">
                                                <label class="custom-file-label">{{  __('message.choose_file',['file' =>  __('message.image') ]) }}</label>
                                            </div>
                                        </div>

                                        <div class="col-md-2 mb-2">
                                            <img id="{{$key}}_image_preview" src="{{ getSingleMedia($value, $key) }}" alt="{{$key}}" class="attachment-image mt-1 {{$key}}_image_preview">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <hr>
                            <div class="col-md-12 mt-1 mb-4">
                                <button class="btn border-radius-10 btn-primary float-md-right" id="saveButton">{{ __('message.save') }}</button>
                            </div>
                        {{ Form::close() }}
                    </div>
                    @if(count($data) > 0)
                        @include('why_choose.list')
                    @endif
                    <br>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
