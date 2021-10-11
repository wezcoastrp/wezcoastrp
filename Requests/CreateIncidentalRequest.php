<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateIncidentalRequest extends Request
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

                $rules = [
                    'budget' => 'required|numeric',
                    'ir_no' => 'required',
                    'ir_title' => 'required',
                    'submitdate' => 'required',
                    'status_id' => 'required',
                    'currency_id' => 'required',

                ];
                /*foreach ($this->request->get('path') as $key => $val) {
                    $rules['path.' . $key] = 'required';
                }*/
            return $rules;
            }

}
