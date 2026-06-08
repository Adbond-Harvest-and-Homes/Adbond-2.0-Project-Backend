<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\SaveAssessmentQuestionOption;
use app\Http\Requests\User\UpdateAssessmentQuestionOption;

use app\Http\Resources\QuestionOptionResource;

use app\Services\AssessmentQuestionOptionService;

use app\Utilities;

class AssessmentQuestionOptionController extends Controller
{
    private $userActivityLogService;

    private $optionService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->optionService = new AssessmentQuestionOptionService;
    }

    public function save(SaveAssessmentQuestionOption $request)
    {
        try{
            $data = $request->validated();

            $this->optionService->saveQuestionOptions($data['options'], $data['questionId']);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Saved Assessment Question Option");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Question Option(s) Added Successfully");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateAssessmentQuestionOption $request, $optionId)
    {
        if (!is_numeric($optionId) || !ctype_digit($optionId)) return Utilities::error402("Invalid parameter optionID");

        $data = $request->validated();

        $option = $this->optionService->option($optionId);
        if(!$option) return Utilities::error402("Option not found");

        $this->optionService->update($data, $option);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Assessment Question Option");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Option Updated Successfully");
    }

    public function delete($optionId)
    {
        if (!is_numeric($optionId) || !ctype_digit($optionId)) return Utilities::error402("Invalid parameter optionID");

        $option = $this->optionService->option($optionId);
        if(!$option) return Utilities::error402("Option not found");

        $this->optionService->delete($option);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Assessment Question Option");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Option Deleted Successfully");
    }
}
