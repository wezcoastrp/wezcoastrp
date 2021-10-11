<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateBeneficiaryRequest extends Request
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
        $justComment = $this->request->get('justComment');
        if(!$justComment) {
            $rules1 = [
                'name' => 'required|max:255',
                'country_id' => 'required',
                'email' => 'email',
            ];
            foreach ($this->request->get('contactFirstName') as $key => $val) {
                $rules1['contactFirstName.' . $key] = 'required';
            }
            $showLead = $this->request->get('showLead');
            if($showLead==2){
                $rules2 = ['suppliercategory_id'=>'required'];
            } else {
                $rules2 = [];
            }

            $rules = $rules1 + $rules2;
            return $rules;
        } else {
            $rules = array();
            return $rules;
        }
    }

    public function messages()
    {
        $justComment = $this->request->get('justComment');
        if(!$justComment) {
            $messages = [];
            foreach ($this->request->get('contactFirstName') as $key => $val) {
                $messages['contactFirstName' . $key] = 'First Name of Contact is required';
            }
            return $messages;
        } else {
            $rules = array();
            return $rules;
        }

    }
}
