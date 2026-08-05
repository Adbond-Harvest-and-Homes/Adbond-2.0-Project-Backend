<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\CreateAssessment;
use app\Http\Requests\User\UpdateAssessment;
use app\Http\Requests\User\ToggleAssessmentActivate;

use app\Http\Resources\AssessmentResource;
use app\Http\Resources\AssessmentAttemptResource;

use app\Services\AssessmentService;
use app\Services\AssessmentQuestionService;
use app\Services\AssessmentQuestionOptionService;
use app\Services\AssessmentAttemptService;

use app\Utilities;

class AssessmentController extends Controller
{
    private $userActivityLogService;

    private $assessmentService;
    private $questionService;
    private $optionService;
    private $assessmentAttemptService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->assessmentService = new AssessmentService;
        $this->questionService = new AssessmentQuestionService;
        $this->optionService = new AssessmentQuestionOptionService;
        $this->assessmentAttemptService = new AssessmentAttemptService;
    }

    public function create(CreateAssessment $request)
    {
        try{
            $data = $request->validated();

            DB::beginTransaction();
            
            $assessment = $this->assessmentService->save($data);

            if(isset($data['questions'])) {
                foreach($data['questions'] as $question) {
                    $questionData = ["assessmentId" => $assessment->id, "question" => $question['question']];
                    $questionObj = $this->questionService->save($questionData);
                    $this->optionService->saveQuestionOptions($question['options'], $questionObj->id);
                }
            }

            DB::commit();

            $assessment = $this->assessmentService->assessment($assessment->id);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Created Assessment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentResource($assessment));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateAssessment $request, $assessmentId)
    {
        try{
            if (!is_numeric($assessmentId) || !ctype_digit($assessmentId)) return Utilities::error402("Invalid parameter assessmentID");

            $assessment = $this->assessmentService->assessment($assessmentId);
            if(!$assessment) return Utilities::error402("Assessment not found");

            $data = $request->validated();

            $currentQuestions = $assessment->questions;
            $updatedQuestionsIds = [];

            if(isset($data['questions'])) {
                foreach($data['questions'] as $question) {
                    if(isset($question['questionId'])) {
                        $questionObj = $this->questionService->question($question['questionId']);
                        if(!$questionObj) return Utilities::error402("QuestionId ".$question['questionId']." is invalid");

                        if(isset($question['options']) && count($question['options']) > 0) {
                            $currentOptions = $questionObj->options;
                            $updatedOptionsIds = [];
                            foreach($question['options'] as $option) {
                                if(isset($option['optionId'])) {
                                    $optionObj = $this->optionService->option($option['optionId']);
                                    if(!$optionObj) return Utilities::error402("OptionId ".$option['optionId']." is invalid");
                                    $this->optionService->update($option, $optionObj);
                                    $updatedOptionsIds[] = $option['optionId'];
                                }else{
                                    $option['questionId'] = $questionObj->id;
                                    $this->optionService->save($option);
                                }
                            }
                            //delete options that are not in the updated options

                            $this->removeDeletedObjects($currentOptions, $updatedOptionsIds);
                        }
                        $this->questionService->update($question, $questionObj);
                        $updatedQuestionsIds[] = $questionObj->id;
                    }else{
                        $question['assessmentId'] = $assessment->id;
                        $questionObj = $this->questionService->save($question);
                        if(isset($question['options']) && count($question['options']) > 0) {
                            foreach($question['options'] as $option) {
                                $option['questionId'] = $questionObj->id;
                                $this->optionService->save($option);
                            }
                        }
                    }
                }
                //delete questions that are not in the updated questions
                $this->removeDeletedObjects($currentQuestions, $updatedQuestionsIds);
            }

            $assessment = $this->assessmentService->update($data, $assessment);
            $assessment = $this->assessmentService->assessment($assessment->id);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Assessment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentResource($assessment));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    private function removeDeletedObjects($objects, $arrIds) 
    {
        if($objects->count() > 0) {
            foreach($objects as $object) {
                if(!in_array($object->id, $arrIds)) $object->delete();
            }
        }
    }

    private function handleOptionsUpdate($options, $question)
    {
        $currentOptions = $question->options;
        $updatedOptionsIds = [];
        foreach($options as $option) {
            dd($option);
            if(isset($option['optionId'])) {
                $optionObj = $this->optionService->option($option['optionId']);
                if(!$optionObj) return Utilities::error402("OptionId ".$option['optionId']." is invalid");
                $this->optionService->update($option, $optionObj);
                $updatedOptionsIds[] = $option['optionId'];
            }else{
                $option['questionId'] = $questionObj->id;
                dd($option);
                $this->optionService->save($option);
            }
        }
        //delete options that are not in the updated options

        $this->removeDeletedObjects($currentOptions, $updatedOptionsIds);
    }

    public function assessments()
    {
        $assessments = $this->assessmentService->assessments();

        return Utilities::ok(AssessmentResource::collection($assessments));
    }

    public function assessment($assessmentId)
    {
        if (!is_numeric($assessmentId) || !ctype_digit($assessmentId)) return Utilities::error402("Invalid parameter assessmentID");

        $assessment = $this->assessmentService->assessment($assessmentId);
        if(!$assessment) return Utilities::error402("Assessment not found");

        return Utilities::ok(new AssessmentResource($assessment));
    }

    public function attempts($assessmentId)
    {
        if (!is_numeric($assessmentId) || !ctype_digit($assessmentId)) return Utilities::error402("Invalid parameter assessmentID");

        $attempts = $this->assessmentAttemptService->assessmentAttempts($assessmentId);

        return Utilities::okay(AssessmentAttemptResource::collection($attempts));
    }

    public function toggleActivate(ToggleAssessmentActivate $request)
    {
        $assessment = $this->assessmentService->assessment($request->validated("assessmentId"));
        if(!$assessment) return Utilities::error402("Assessment not found");

        $assessment = ($assessment->active==0) ? $this->assessmentService->activate($assessment) : $this->assessmentService->deactivate($assessment);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Toggled Assessment Activation State");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentResource($assessment));
    }

    public function delete($assessmentId)
    {
        if (!is_numeric($assessmentId) || !ctype_digit($assessmentId)) return Utilities::error402("Invalid parameter assessmentID");

        $assessment = $this->assessmentService->assessment($assessmentId);
        if(!$assessment) return Utilities::error402("Assessment not found");

        $this->assessmentService->delete($assessment);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Assessment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Assessment Deleted Successfully");
    }
}
