<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class RegionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $method = strtolower($this->method());
        $user_id = $this->route()->region;

        $rules = [];
        switch ($method) {
            case 'post':
                $rules = [
                    'name' => 'required',
                    'coordinates' => 'required|string',
                ];
                break;
            case 'patch':
                $rules = [
                    'name'  => 'required',
                    'coordinates' => 'required|string',
                ];
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'coordinates.required' => __('message.map_coordinates_required'), // Customize this translation as needed
        ];
    }

     /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator) {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data,422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
