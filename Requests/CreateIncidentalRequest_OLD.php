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
        switch($this->method())
        {
            case 'POST':
            {
                $rules = [
                    'budget' => 'required|numeric',
                    'ir_no' => 'required',
                    'ir_title' => 'required',
                    'submitdate' => 'required',
                    'status' => 'required',
                    'currency_id' => 'required',
                    'filecategory_id' => 'required',
                    'filename' => 'required',
                    'filedescription' => 'required',
                    'path' => 'required|max:8000'
                ];
                foreach ($this->request->get('contactFirstName') as $key => $val) {
                    $rules['contactFirstName.' . $key] = 'required';
                }
            return $rules;
            }

            case 'PATCH':
            default: break;
        }
    }
}
