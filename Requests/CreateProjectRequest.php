<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CreateProjectRequest extends Request
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
        return [
            'title' => ['required','min:3'],
            'contract_no' => 'required',
            'bxl_project_id' => 'required',
            'duration' => ['required', 'numeric'],
            'start_date' => 'required',
            'end_date' => 'required',
            'project_country_id' => 'required',
            'short' => 'required',
            'name' => 'required_if:newBeneficiary,chosen',
            'contactName' => 'required_if:newBeneficiary,chosen',
            'contactLastName' => 'required_if:newBeneficiary,chosen',
            'street' => 'required_if:newBeneficiary,chosen',
            'city' => 'required_if:newBeneficiary,chosen',
            'country_id' => 'required_if:newBeneficiary,chosen',
            'tel' => 'required_if:newBeneficiary,chosen',
            'email' => array('required_if:newBeneficiary,chosen','email'),
            'beneficiary_id' => 'required_if:newBeneficiary,0',
            'name2' => 'required_if:newContractingAuthority,chosen',
            'contactName2' => 'required_if:newContractingAuthority,chosen',
            'contactLastName2' => 'required_if:newContractingAuthority,chosen',
            'street2' => 'required_if:newContractingAuthority,chosen',
            'city2' => 'required_if:newContractingAuthority,chosen',
            'country_id2' => 'required_if:newContractingAuthority,chosen',
            'tel2' => 'required_if:newContractingAuthority,chosen',
            'email2' => array('required_if:newContractingAuthority,chosen','email'),
            'contractingauthority_id' => 'required_if:newContractingAuthority,0',
            'currency_id' => 'required_with:suppliercontract',
            'ir' => 'required',
            'mailuser_id' => 'required',
            'template_id' => 'required'

        ];
    }

  /*  public function messages()
    {
        return ['beneficiary_id.required_if'=> trans('validation.benefId')];
    }*/

    public function response(array $errors)
    {
        $newBeneficiary = Request::input('newBeneficiary');
        if($newBeneficiary == 'chosen'){
            $show = 'forcedShow';
        } else {
            $show ='';
        }

        $newContract = Request::input('newContractingAuthority');
        if($newContract == 'chosen'){
            $show2 = 'forcedShow';
        } else {
            $show2 ='';
        }

        return $this->redirector->to($this->getRedirectUrl())
                                            ->withInput($this->except($this->dontFlash))
                                            ->withErrors($errors, $this->errorBag)
                                            ->with('show', $show)
                                            ->with('show2', $show2);

        // always return json response

    }
}
