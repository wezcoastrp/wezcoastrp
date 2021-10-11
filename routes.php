<?php
use Bican\Roles\Models\Role;
use App\User;
Route::get('/ceylan',function(){
$activity = \App\Activity::find(175);
    $days = $activity->days->where('expertposition_id','149')->toArray();

    return $days;
});
Route::get('/getTime', function(){
    $now = \Carbon\Carbon::now('Europe/Istanbul');
    $calibrated = $now->hour ;
    return  $calibrated;
});
Route::get('/kep',function(){
   return view('kep');
});

Route::get('/updateDays',function(){
    $days = \App\Day::where('id','>',24000)->where('id','<=',26000)->get();
    foreach($days as $day){
        if($day->expertposition){
            $project_id = $day->expertposition->project_id;
            $day->timestamps = false;
            $day->update(['project_id'=>$project_id]);
        }


    }
    return 'OK';
});
Route::get('/updateInvoices', function(){
   $invoices = \App\Invoice::where('project_id','25')->where('source','3')->get();
   foreach($invoices as $invoice){
       $invoice->timestamps = false;
       $invoice->update(['source'=>'2']);
   }
   return 'OK';
});
Route::get('/wemos/updateTemp', function(){
    if(isset($_GET['token'])){
        if($_GET['token']=='abrakadabra123'){
            if(isset($_GET['temp']) AND isset($_GET['humidity'])){
                $now = \Carbon\Carbon::now('Europe/Istanbul');
                $temp = $_GET['temp'];
                $humidity = $_GET['humidity'];

                \App\Wemo::create(['temp'=>$temp,'humidity'=>$humidity, 'created_at'=> $now, 'updated_at'=> $now]);

                return 'Humidity and Temp are recorded in the database: OK';
            } else {
                return 'No Temp and Humidity value available';
            }

        } else {
            return 'Token mismatch exception';
        }
    } else {
        return 'No token exception';
    }


});
Route::get('/timesheetupdate',function(){
  $timesheet = \App\Timesheet::findOrFail(834);
    $timesheet->update(['totaldays'=>$timesheet->days->sum('days'),'totalperdiems'=>$timesheet->days->sum('perdiem')]);
    return 'OK! Timesheet_id: '.$timesheet->id;
});

Route::get('/holidayupdate',function(){
    DB::table('holidays')->whereNotNull('deleted_at')->delete();

   return 'OK';
});
Route::get('/dayupdate',function(){
    $days = \App\Day::where('id','>=',3000)->where('id','<',4000)->get();

    foreach($days as $day){
        $day->update(['timesheet_id'=>$day->timesheets()->first()->pivot->timesheet_id]);
    }
   return $days;

});
Route::get('bxlexperts', 'BxlexpertsController@index');
/*FOR SIVIL DUSUN INDICATORS*/
Route::get('/api/expenditures_mis',function(){

  $expenditures = \App\Expenditure::has('misfields')->get();


    $allExpenditures = array();
    foreach($expenditures as $exp){
        $allExpenditures[] = [
            'exp_id' => $exp->id,
            'application_id' => $exp->misapplication_id,
            'activity_id' => $exp->misactivity_id,
            'expenditure_code_id' =>$exp->expenditurescode ? $exp->expenditurescode->code : null,
            'expenditure_code_desc' => $exp->expenditurescode ? $exp->expenditurescode->description : null,
            'supplier' => $exp->supplier ? $exp->supplier->name : null,
            'expert' => $exp->expert ? $exp->expert->first_name.' '.$exp->expert->last_name : null,
            'description' => strip_tags($exp->description),
            'amount' => $exp->amount,
            'currency_id' => $exp->currency->id,
            'currency_short' => $exp->currency->short,
            'currency_name' => $exp->currency->name,
            'inforeuro' => $exp->inforeuro,
            'unit_id' => $exp->misfields->unit_id,
            'unit_person' => $exp->misfields->unit_person,
            'unit_amount' => $exp->misfields->unit_amount,
            'unit_cost' => $exp->misfields->unit_cost,
            'unit_total' => $exp->misfields->unit_total


        ];
    }

    return response($allExpenditures)->header('Content-Type','application/json');

});

/*FOR SIVIL DUSUN MIS EXPENDITURES*/
Route::get('/api/expenditure_fields',function() {

    if(isset($_GET['token'])){
        $token = mis_token();
        if($_GET['token'] == $token){
            if(isset($_GET['project_id'])) {
                $project_id = $_GET['project_id'];
                $all['suppliers'] = \App\Supplier::with(['category', 'comment'])->get()->keyBy('id')->toArray();
                $all['supplierCategories'] = \App\Suppliercategory::get()->lists('name','id');
                $budgets = \App\Budget::where('project_id', $project_id)->with('budgettype')->get();
                $budgetBalances = array();
                foreach($budgets as $budget){
                    $balance = $budget->budgetbalance;
                    $budgetBalances[$budget->budgettype->id] = ['id' => $budget->budgettype->id,'budgetType'=>$budget->budgettype->budgetType.' (Balance: '.$balance.' EURO)','balance'=> $budget->budgetbalance];
                }
                $all['budgetBalances'] = $budgetBalances;
                $all['budgets'] = $budgets->keyBy('id')->toArray();
                $all['budgettypes'] = \App\Budgettype::get()->keyBy('id')->toArray();
                $bankAccounts = \App\Bankaccount::where('project_id', $project_id)->with('currency')->get();
                $all['bankaccounts'] = $bankAccounts->keyBy('id')->toArray();

                $bankBalances = array();
                foreach($bankAccounts as $bankAccount){
                    $balance = $bankAccount->bankstatements->sum("credit") - $bankAccount->bankstatements->sum("debit");
                    $bankBalances[$bankAccount->id] = ['id'=>$bankAccount->id,'accountName'=>$bankAccount->accountname,'accountNameAndBalance'=>$bankAccount->accountname.' (Balance: '.$balance.' '.$bankAccount->currency->short. ')','balance'=>$balance, 'currency_id' => $bankAccount->currency_id,'currency_short'=>$bankAccount->currency->short];
                }
                $all['bankBalances'] = $bankBalances;
                $all['pettyCashBalances'] = \App\Bankstatement::where('bankaccount_id',999)->where('project_id',$project_id)->groupBy('currency_id')->selectRaw('*, sum(debit) as debited, sum(credit) as credited')->get()->keyBy('currency_id')->toArray();
                $all['experts'] = \App\Expert::whereHas('projects', function ($q) use ($project_id) {
                    $q->where('project_id', $project_id);
                })->with(['user','expertposition'])->get()->keyBy('id')->toArray();
                $all['project'] = \App\Project::with('currency')->find($project_id)->toArray();
                $all['incidentalrequests'] = \App\Incidentalrequest::where('project_id', $project_id)->with('currency')->get()->keyBy('id')->toArray();
                $all['activities'] = \App\Activity::where('project_id',$project_id)->with('component')->get()->keyBy('id')->toArray();
                $all['expenditurecategories'] = \App\Suppliercategory::get()->keyBy('id')->toArray();

                return response($all)->header('Content-Type', 'application/json');
            }else{
                return 'NO PROJECT, NO PASARAN';
            }
        } else {
            return 'WRONG TOKEN: You do not have permission';
        }
    } else {
        return 'NO TOKEN: You do not have permission';
    }
});
/*bankstatements*/
Route::get('/api/bankstatements',function() {

    if(isset($_GET['token'])){
        $token = mis_token();
        if($_GET['token'] == $token){
            if(isset($_GET['project_id'])) {
                $project_id = $_GET['project_id'];
                $all['bankstatements'] = \App\Bankstatement::where('project_id', $project_id)->with(['bankaccount', 'currency', 'budgettype'])->get()->toArray();
                return response($all)->header('Content-Type','application/json');
            } else {
                return 'NO PROJECT, NO PASARAN';
            }
        } else {
            return 'WRONG TOKEN: You do not have permission';
        }
    } else {
        return 'NO TOKEN: You do not have permission';
    }
});


/*GENERAL****************************************/
Route::get('/', array('as' => 'login', function()
{

    return View::make('login');
}));

Route::get('/api/incoming/createSupplier','SuppliersController@createIncoming');
Route::get('/api/incoming/createExpenditure','ExpendituresController@createIncoming');
Route::get('/api/incoming/updateExpenditure','ExpendituresController@updateIncoming');
Route::get('/api/incoming/deleteExpenditure','ExpendituresController@deleteIncoming');
Route::get('/api/paymentrequests', 'PaymentrequestsController@json');
Route::get('/api/paymentrequests/incoming', 'PaymentrequestsController@incoming');
Route::get('/api/invoices', 'InvoicesController@json');
Route::get('/api/invoices/incoming', 'InvoicesController@incoming');
Route::get('/api/expenditures', 'ExpendituresController@json');
Route::get('/api/expenditurePage/{prid}/budgetType/{bt_id}', 'ExpendituresController@jsonForExpenditurePage');
Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware'=>'sentry.auth'], function ()
{

        Route::get('/home', array('as' => 'home', 'uses' => 'HomeController@index'));
        Route::get('timesheets/{tsid}/show/{prid}', 'TimesheetsController@show');
        Route::get('timesheets/{tsid}/pdf/{prid}', 'TimesheetsController@pdf');
    Route::get('files/{id}/delete', 'FilesController@destroy');
        /*monthly used days*/
        Route::get('monthlyuseddays/{user_id}', 'AllocateddaysController@monthly');
        /*ADMIN STARTS*/
        Route::group(['middleware' => ['sentry.member:Admins']], function () {

            Route::get('users/loginAsAnotherUser/{id}',['as'=>'login.asAnotherUser','uses'=>'UserController@loginAsAnotherUser']);
                //PROJECTS
                /*Route::resource('projects', 'ProjectsController');*/
                Route::get('projects/{prid}/create', 'ProjectsController@create');
                //Projects
                Route::get('projects', [
                    'as' => 'projects',

                    'uses' => 'ProjectsController@index'
                ]);

                /*PROJECTS --Main*/
                Route::get('projects/{id}', 'ProjectsController@show');
                Route::get('projects/{prid}/projects/create', 'ProjectsController@create');
                Route::post('projects', 'ProjectsController@store');
                Route::get('projects/{id}/edit', 'ProjectsController@edit');
                Route::patch('projects/{id}', 'ProjectsController@update');
                Route::get('projects/{id}/delete', 'ProjectsController@destroy');

                /*PROJECTS --Beneficiaries*/
                Route::get('projects/{id}/beneficiary', 'ProjectsController@beneficiary');
                Route::post('linkBenef', 'ProjectsController@linkBenef');
                Route::get('projects/{prid}/unlinkBenef/{id}', 'ProjectsController@unlinkBenef');

                /*PROJECTS --Contracting Authority*/
                Route::get('projects/{id}/contra', 'ProjectsController@contra');
                Route::post('linkContra', 'ProjectsController@linkContra');
                Route::get('projects/{id}/unlinkContra', 'ProjectsController@unlinkContra');

                /*PROJECTS --Consortium Members*/
                Route::get('projects/{id}/consortium', 'ProjectsController@consortium');
                Route::post('linkConsort', 'ProjectsController@linkConsort');
                Route::get('projects/{prid}/unlinkConsort/{id}', 'ProjectsController@unlinkConsort');

                /*PROJECTS --Experts*/
                Route::get('projects/{id}/expertpositions', 'ProjectsController@expertpositions');
                Route::get('projects/{id}/expertplanning', 'ProjectsController@expertplanning');
                Route::get('projects/{prid}/expertplanning/single/{expertposition_id}', 'ProjectsController@expertplanningSingle');
                Route::get('projects/{id}/experts', 'ProjectsController@experts');
                Route::get('projects/{id}/approvals', 'ProjectsController@approvals');
                Route::get('projects/{id}/timesheets', 'ProjectsController@timesheets');
                Route::get('projects/{id}/invoices', 'ProjectsController@invoices');

                /*PROJECTS --Budget, Margin, Expenditures*/
                Route::get('projects/{id}/budget', 'ProjectsController@budget');
                Route::get('projects/{id}/margin', 'ProjectsController@margin');
                Route::get('projects/{id}/{btid}/expenditures', 'ProjectsController@expenditures');
                Route::get('projects/{id}/incidentals', 'ProjectsController@incidentals');
                Route::get('projects/{id}/paymentrequests', 'ProjectsController@paymentrequests');
                Route::get('projects/{id}/components', 'ProjectsController@components');
                Route::get('projects/{id}/banks', 'ProjectsController@banks');
                Route::get('projects/{id}/bankexchanges', 'ProjectsController@bankexchanges');
                Route::get('projects/{id}/suppliers', 'ProjectsController@suppliers');
                Route::get('projects/{id}/pettycash', 'ProjectsController@pettycash');
                Route::get('projects/{id}/files', 'ProjectsController@files');
                Route::get('projects/{id}/monitorings', 'ProjectsController@monitorings');
                Route::get('projects/{id}/monitorings/charts', 'ProjectsController@monitoringsCharts');

                Route::get('projects/{id}/financialreporting/step1', 'ProjectsController@financialreportingstep1');
                Route::post('financialreporting', 'ProjectsController@financialreportingstep2');

                Route::get('projects/{id}/ecinvoices', 'ProjectsController@ecinvoices');
                Route::get('projects/{id}/ecinvoices/create', 'EcinvoicesController@create');
                Route::get('projects/{id}/ecinvoices/{invoice_id}/edit', 'EcinvoicesController@edit');
                Route::post('ecinvoices', 'EcinvoicesController@store');
                Route::patch('ecinvoices/{invoice_id}', 'EcinvoicesController@update');
            Route::get('ecinvoices/{id}/delete', 'EcinvoicesController@destroy');


                //BENEFICIARIES
                Route::resource('beneficiaries', 'BeneficiaryController', ['except' => ['destroy']]);
                Route::get('beneficiaries/{id}/delete', 'BeneficiaryController@destroy');

                //CONTRACTING AUTHORITIES
                Route::resource('contractingauthorities', 'ContractingauthoritiesController', ['except' => ['destroy']]);
                Route::get('contractingauthorities/{id}/delete', 'ContractingauthoritiesController@destroy');

                //Consortiummembers
                Route::resource('consortiummembers', 'ConsortiummembersController', ['except' => ['destroy']]);
                Route::get('consortiummembers/{id}/delete', 'ConsortiummembersController@destroy');

                //SUPPLIERS
                Route::resource('suppliers', 'SuppliersController', ['except' => ['destroy']]);
                Route::get('suppliers/{id}/delete', 'SuppliersController@destroy');

                //CONTACTS
                Route::get('contacts/{id}/delete', 'ContactsController@destroy');


                //BUDGETS
                Route::get('projects/{id}/budgets/create', 'BudgetsController@create');
                Route::post('budgets', 'BudgetsController@store');
                Route::get('budgets/{budget_id}/edit/{prid}', 'BudgetsController@edit');
                Route::patch('budgets/{budget_id}', 'BudgetsController@update');
                Route::get('budgets/{id}/delete', 'BudgetsController@destroy');

                //BUDGET PLANNINGS
                Route::get('projects/{id}/budgetplanning', 'ProjectsController@budgetplanning');

                Route::get('budgetplannings/{activity_id}/create/{budgettype_id}', 'BudgetplanningsController@create');
                Route::post('budgetplannings', 'BudgetplanningsController@store');


                //BXLEXPERTS

                Route::get('bxlexperts/search', 'BxlexpertsController@searchBox');
                Route::post('bxlexpertsSearch', 'BxlexpertsController@bxlexpertSearch');

                //BXLPROJECTS
                Route::get('bxlprojects', 'BxlprojectsController@index');
                Route::post('bxlprojects', 'BxlprojectsController@update');

                //EXPERTS
                Route::get('projects/{prid}/experts/{bxlexpertid}/create', 'ExpertsController@Create');
                Route::post('experts', 'ExpertsController@store');
                Route::get('experts/{id}', 'ExpertsController@show');
                Route::get('/admin/experts', 'ExpertsController@index2');
                Route::get('experts/{id}/edit/{prid}', 'ExpertsController@edit');
                Route::patch('experts/{id}', 'ExpertsController@update');
                Route::get('experts/{id}/delete', 'ExpertsController@destroy');
                Route::post('files/add/{id}', 'ExpertsController@addFile');

                //COMPONENTS
                Route::get('projects/{prid}/components/create', 'ComponentsController@create');
                Route::post('projects/expertsMonthlyUsed', 'AllocateddaysController@monthlyAdmin');
                Route::post('components', 'ComponentsController@store');
                Route::get('components/{id}/edit', 'ComponentsController@edit');
                Route::patch('components/{id}', 'ComponentsController@update');
                Route::get('components/{id}/delete', 'ComponentsController@destroy');

                //EXPERT POSITIONS
                Route::get('projects/{prid}/expertpositions/create', 'ExpertpositionsController@create');
                Route::post('expertpositions', 'ExpertpositionsController@store');
                Route::get('expertpositions/{id}/edit', 'ExpertpositionsController@edit');
                Route::patch('expertpositions/{id}', 'ExpertpositionsController@update');
                Route::get('expertpositions/{id}/delete', 'ExpertpositionsController@destroy');

                //ACTIVITIES
                Route::get('projects/{prid}/activities/create', 'ActivitiesController@create');
                Route::post('activities', 'ActivitiesController@store');
                Route::get('activities/{id}/edit', 'ActivitiesController@edit');
                Route::patch('activities/{id}', 'ActivitiesController@update');
                Route::get('activities/{id}/delete', 'ActivitiesController@destroy');

                //FILES
                Route::get('projects/{prid}/files/create', 'FilesController@create');
                Route::post('projects/files', 'ProjectsController@upload');

                Route::get('files/{id}/edit', 'FilesController@edit');
                Route::patch('files/{id}', 'FilesController@update');

                //MARGINS
                Route::get('projects/{prid}/margins/create', 'MarginsController@create');
                Route::get('projects/{prid}/margins/step2', 'MarginsController@create2');
                Route::get('projects/{prid}/margins/step3', 'MarginsController@create3');
                Route::post('margins', 'MarginsController@store');
                Route::post('margins-step2', 'MarginsController@store2');
                Route::post('margins-step3', 'MarginsController@store3');
                Route::get('projects/{prid}/margins/edit', 'MarginsController@edit');
                Route::get('projects/{prid}/margins/edit2', 'MarginsController@edit2');
                Route::get('projects/{prid}/margins/edit3', 'MarginsController@edit3');
                Route::patch('margins/{id}', 'MarginsController@update');
                Route::get('margins/{id}/delete', 'MarginsController@destroy');

                //BANK ACCOUNTS
                Route::get('projects/{prid}/bankaccounts/create', 'BankaccountsController@create');
                Route::post('bankaccounts', 'BankaccountsController@store');
                Route::get('bankaccounts', 'BankaccountsController@index');
                Route::get('bankaccounts/{id}/edit/{prid}', 'BankaccountsController@edit');
                Route::patch('bankaccounts/{id}', 'BankaccountsController@update');
                Route::get('bankaccounts/{id}/delete', 'BankaccountsController@destroy');

                // BANK STATEMENTS
                Route::get('bankaccounts/{id}/index/{prid}', 'BankstatementController@index');

                //Paymentrequests
                Route::get('projects/{prid}/paymentrequests/create', 'PaymentrequestsController@create');
                Route::post('paymentrequests', 'PaymentrequestsController@store');
                Route::get('paymentrequests/{id}', 'PaymentrequestsController@show');
                Route::get('paymentrequests', 'PaymentrequestsController@index');
                Route::get('paymentrequests/{id}/edit/{prid}', 'PaymentrequestsController@edit');
                Route::patch('paymentrequests/{id}', 'PaymentrequestsController@update');
                Route::get('paymentrequests/{id}/delete', 'PaymentrequestsController@destroy');
                Route::get('paymentrequests/{id}/approve', 'PaymentrequestsController@approve');
                Route::get('paymentrequests/{id}/transfer', 'PaymentrequestsController@transfer');
                Route::get('paymentrequests/{id}/add2bankAccount', 'PaymentrequestsController@add2bankAccount');

                //Bank Exchanges
                Route::get('projects/{prid}/exchanges/create', 'ExchangesController@create');
                Route::post('exchanges', 'ExchangesController@store');
                Route::get('exchanges/{id}', 'ExchangesController@show');
                Route::get('exchanges/{id}/edit/{prid}', 'ExchangesController@edit');
                Route::patch('exchanges/{id}', 'ExchangesController@update');
                Route::get('exchanges/{id}/delete', 'ExchangesController@destroy');

                //Expenditures
                Route::get('expenditures/{id}', 'ExpendituresController@show');
                Route::get('projects/{prid}/expenditures/create', 'ExpendituresController@create');
                Route::post('expenditures', 'ExpendituresController@store');
                Route::get('expenditures/{id}/edit/{prid}', 'ExpendituresController@edit');
                Route::patch('expenditures/{id}', 'ExpendituresController@update');
                Route::get('expenditures/{id}/delete', 'ExpendituresController@destroy');



                //Incidentalrequests
                Route::get('incidentalrequests/{id}', 'IncidentalRequestsController@show');
                Route::get('projects/{prid}/incidentalrequests/create', 'IncidentalrequestsController@create');
                Route::post('incidentalrequests', 'IncidentalrequestsController@store');
                Route::get('incidentalrequests', 'IncidentalrequestsController@index');
                Route::get('incidentalrequests/{id}/edit/{prid}', 'IncidentalrequestsController@edit');
                Route::patch('incidentalrequests/{id}', 'IncidentalrequestsController@update');
                Route::get('incidentalrequests/{id}/delete', 'IncidentalrequestsController@destroy');

                //Holidays
                Route::resource('holidays', 'HolidaysController',['except' => ['destroy']]);
                Route::get('holidays/{id}/delete', 'HolidaysController@destroy');

                //Approvals
                Route::get('projects/{prid}/approvals/create', 'ApprovalsController@create');
                Route::get('projects/{prid}/approvals/{id}/step2', 'ApprovalsController@create2');
                Route::get('projects/{prid}/approvals/{id}/step3', 'ApprovalsController@create3');
                Route::get('projects/{prid}/approvals/{id}/approve', 'ApprovalsController@approve');
                Route::post('approvals-approve', 'ApprovalsController@store4');
                Route::post('approvals', 'ApprovalsController@store');
                Route::post('approvals-step2', 'ApprovalsController@store2');
                Route::post('approvals-step3', 'ApprovalsController@store3');
                Route::get('approvals/{id}/edit/{prid}', 'ApprovalsController@edit');
                Route::get('approvals/{id}/edit2/{prid}', 'ApprovalsController@edit2');
                Route::get('approvals/{id}/edit3/{prid}', 'ApprovalsController@edit3');
                Route::get('approvals/{id}/editApproval/{prid}', 'ApprovalsController@editapproval');
                Route::patch('approvals-step1/{id}', 'ApprovalsController@update');
                Route::patch('approvals-step2/{id}', 'ApprovalsController@update2');
                Route::patch('approvals-step3/{id}', 'ApprovalsController@update3');
                Route::patch('approvals/approvalupdate/{id}', 'ApprovalsController@approvalupdate');
                Route::get('approvals/{id}/delete', 'ApprovalsController@destroy');
                Route::get('admin/approvals', 'ApprovalsController@index2');

                //Timesheets
                Route::get('projects/{prid}/timesheets/{tsid}/approve', 'TimesheetsController@approve');
                Route::post('timesheets-approve', 'TimesheetsController@store2');
                Route::get('projects/{prid}/timesheets/{tsid}/editapprove', 'TimesheetsController@editapprove');
                Route::patch('timesheets/approvalupdate/{id}', 'TimesheetsController@approvalupdate');
                Route::get('projects/{prid}/timesheets/{tsid}/deleteapprove', 'TimesheetsController@approvaldelete');
                Route::get('admin/timesheets', 'TimesheetsController@adminindex');

                /*invoices*/
                Route::get('admin/invoices', 'InvoicesController@adminindex');

                //Pettycash
                Route::get('projects/{prid}/pettycash/create', 'PettycashController@create');
                Route::post('pettycash', 'PettycashController@store');
                Route::get('pettycash/{id}', 'PettycashController@show');
                Route::get('pettycash/{id}/edit/{prid}', 'PettycashController@edit');
                Route::patch('pettycash/{id}', 'PettycashController@update');
                Route::get('pettycash/{id}/delete', 'PettycashController@destroy');

                /*budgettypes*/
                Route::resource('budgettypes', 'BudgettypesController', ['except' => ['destroy']]);
                Route::get('budgettypes/{id}/delete', 'BudgettypesController@destroy');

                /*currencies*/
                Route::resource('currencies', 'CurrenciesController', ['except' => ['destroy', 'update']]);
                Route::get('currencies/{id}/delete', 'CurrenciesController@destroy');
                Route::patch('currencies', 'CurrenciesController@update');

                /*countries*/
                Route::resource('countries', 'CountriesController', ['except' => ['destroy', 'update']]);
                Route::get('countries/{id}/delete', 'CountriesController@destroy');
                Route::patch('countries', 'CountriesController@update');

                //Monitorings
                Route::get('projects/{prid}/monitorings/create/{type}', 'MonitoringsController@create');
                Route::post('monitorings', 'MonitoringsController@store');
                Route::get('monitorings/{id}/edit/{prid}', 'MonitoringsController@edit');
                Route::patch('monitorings/{id}', 'MonitoringsController@update');
                Route::get('monitorings/{id}/delete', 'MonitoringsController@destroy');

                //help
                Route::get('help','HomeController@help');

                /*timesheet templates*/
                Route::resource('templates','TemplatesController');
                Route::get('templates/{tsid}/linkProjects', 'TemplatesController@linkProjectsCreate');
            Route::post('templates/{tsid}/linkProjects', 'TemplatesController@linkProjectsStore');
                Route::get('templates/{prid}/unLinkProject', 'TemplatesController@unLinkProject');

        });/*end admin section*/

        Route::group(['middleware' => 'sentry.member:Experts'], function () {
            /*experts*/
            Route::get('experts', [
                'as' => 'projects',
                'uses' => 'ExpertsController@index'
            ]);
            /*approvals*/
            Route::get('approvals', [
                'as' => 'approvals',
                'uses' => 'ApprovalsController@index'
            ]);
            /*timesheets*/
            Route::get('timesheets/create', 'TimesheetsController@create');
            Route::get('timesheets/{prid}/create2/{tsid}', 'TimesheetsController@create2');
            Route::get('timesheets/{prid}/create3/{tsid}', 'TimesheetsController@create3');
            Route::get('timesheets/{prid}/create3forAfrica/{tsid}', 'TimesheetsController@create3forAfrica');
            Route::get('timesheets/{prid}/create4/{tsid}', 'TimesheetsController@create4');
            Route::get('timesheets', 'TimesheetsController@index');


            Route::post('timesheets', 'TimesheetsController@store');
            Route::get('timesheets/{id}/edit/{prid}', 'TimesheetsController@edit');
            Route::patch('timesheets/{id}', 'TimesheetsController@update');
            Route::patch('timesheets-step2/{id}', 'TimesheetsController@update2');
            Route::patch('timesheets-step3/{id}', 'TimesheetsController@update3');
            Route::patch('timesheets-step3-forAfrica/{id}', 'TimesheetsController@update3forAfrica');
            Route::patch('timesheets-step4/{id}', 'TimesheetsController@update4');
            Route::get('timesheets/{id}/delete', 'TimesheetsController@destroy');
            Route::get('pdf', 'TimesheetsController@pdf');

            /*days*/
            Route::get('days/{prid}/create/{tsid}/{daydate}', 'DaysController@create');
            Route::get('projects/{prid}/days/{id}/edit/{tsid}', 'DaysController@edit');
            Route::post('days', 'DaysController@store');
            Route::patch('days/{id}', 'DaysController@update');
            Route::get('days/{id}/delete', 'DaysController@destroy');
            Route::get('days/{dayid}/detachActivity/{activityid}/{expertid}/{hours}', 'DaysController@detachActivity');

            /*invoices*/
            Route::get('invoices', 'InvoicesController@index');
            Route::get('invoices/create', 'InvoicesController@create');
            Route::get('invoices/{id}/edit', 'InvoicesController@edit');
            Route::patch('invoices/{id}', 'InvoicesController@update');
            Route::post('invoices', 'InvoicesController@store');
            Route::get('invoices/{id}/delete', 'InvoicesController@destroy');

            /*allocated days*/
            Route::get('allocateddays', 'AllocateddaysController@index');



            /*help*/
            Route::get('help2','HomeController@expertHelp');


        }); /*End Experts Section*/


});/*end of authentication*/

Route::get('sentryroles', function ()
{
    $adminRole = Role::create([
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => '', // optional
        'level' => 1, // optional, set to 1 by default
    ]);
});

Route::get('attachRole', function ()
{


    $user = User::find(6);

    $user->attachRole(1);
});

Route::get('sentryupdate', function ()
{
    $group = Sentry::findGroupById(1);

    // Update the group details
    $group->name = 'Experts';
    $group->permissions = array(
        'admin' => 1,
        'users' => 1,
    );
});
Route::get('sentrycreate',function (){
    $group = Sentry::createGroup(array(
        'name'        => 'Moderator',
        'permissions' => array(
            'admin' => 1,
            'country' => 1,
            'project' => 1
        ),
    ));
});
Route::get('sentryuserupdate', function ()
{
    $user = Sentry::findUserById(1);

    // Update the user details
    $user->password = bcrypt('Eflatun78');
});
    Route::controllers([
        'auth'=>'Auth\AuthController',
        'password'=>'Auth\PasswordController',
    ]);
?>