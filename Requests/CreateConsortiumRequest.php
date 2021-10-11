<?php

namespace App\Http\Requests;
use Response;
use App\Http\Requests\Request;

class CreateConsortiumRequest extends Request
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
        $project = \App\Project::findOrFail($this->request->get('project_id'));
        /*$consortium = \App\Consortiummember::findOrFail($this->request->get('consortiummember_id'));*/


            $rules = [
                'consortiummember_id'=>['required_if:newConsortium,0', 'unique:consortiummember_project,consortiummember_id,NULL,id,project_id,'.$project->id],
                'name' => ['required_if:newConsortium,chosen'],

            ];
        if($this->request->get('lead')==1){
            $rules2 = [
                'lead' => ['unique:consortiummember_project,lead,NULL,id,project_id,'.$project->id]
            ];
        } else {
            $rules2=[];
        }

        return ($rules + $rules2 );

    }

  /*  public function messages()
    {
        return [
          'lead.unique' => 'Something',
        ];
    }*/
 /*   public function response(array $errors)
    {
        $show=1;
        return Response::with('show', $show);
    }*/
}
