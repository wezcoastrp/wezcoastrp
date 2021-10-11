<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateDaysRequest extends Request
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
        $rules=[];
        foreach ($this->request->get('place') as $key => $val) {
            if($val!=''){
                $rules['description.'.$key] = 'required_with:place';
            }

        }
        return $rules;
    }
}
