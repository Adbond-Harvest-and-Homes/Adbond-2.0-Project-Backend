<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\SaveAssessmentQuestion;
use app\Http\Requests\User\UpdateAssessmentQuestion;

use app\Http\Resources\QuestionResource;

use app\Services\AssessmentQuestionService;
use app\Services\AssessmentQuestionOptionService;

use app\Utilities;

class AssessmentQuestionController extends Controller
{
    private $userActivityLogService;

    private $assessmentQuestionService;
    private $questionOptionService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->assessmentQuestionService = new AssessmentQuestionService;
        $this->questionOptionService = new AssessmentQuestionOptionService;
    }

    public function save(SaveAssessmentQuestion $request)
    {
        try{
            $data = $request->validated();

            DB::beginTransaction();

            $question = $this->assessmentQuestionService->save($data);

            $this->questionOptionService->saveQuestionOptions($data['options'], $question->id);

            $question = $this->assessmentQuestionService->question($question->id);
            DB::commit();

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Saved Assessment Question");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new QuestionResource($question));
        }catch(\Exception $e){
            DB::rollBack();
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateAssessmentQuestion $request, $questionId)
    {
        try{
            if (!is_numeric($questionId) || !ctype_digit($questionId)) return Utilities::error402("Invalid parameter questionID");

            $question = $this->assessmentQuestionService->question($questionId);
            if(!$question) return Utilities::error402("Question not found");

            $data = $request->validated();
            $question = $this->assessmentQuestionService->update($data, $question);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Assessment Question");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new QuestionResource($question));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function assessmentQuestions($assessmentId)
    {
        if (!is_numeric($assessmentId) || !ctype_digit($assessmentId)) return Utilities::error402("Invalid parameter assessmentID");

        $questions = $this->assessmentQuestionService->assessmentQuestions($assessmentId);

        return Utilities::ok(QuestionResource::collection($questions));
    }

    public function question($questionId)
    {
        if (!is_numeric($questionId) || !ctype_digit($questionId)) return Utilities::error402("Invalid parameter questionID");

        $question = $this->assessmentQuestionService->question($questionId);
        if(!$question) return Utilities::error402("Question not found");

        return Utilities::ok(new QuestionResource($question));
    }

    public function delete($questionId)
    {
        if (!is_numeric($questionId) || !ctype_digit($questionId)) return Utilities::error402("Invalid parameter questionID");

        $question = $this->assessmentQuestionService->question($questionId);
        if(!$question) return Utilities::error402("Question not found");

        $this->assessmentQuestionService->delete($question);

        
            try {
                $this->userActivityLogService->log(Auth::user(), "Deleted Assessment Question");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Question Deleted Successfully");
    }
}
