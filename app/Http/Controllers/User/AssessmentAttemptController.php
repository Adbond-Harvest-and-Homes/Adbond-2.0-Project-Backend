<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\StartAssessment;
use app\Http\Requests\User\UpdateAssessmentAttempt;
use app\Http\Requests\User\SubmitAssessment;

use app\Http\Resources\AssessmentAttemptResource;

use app\Services\AssessmentAttemptService;
use app\Services\AssessmentService;

use app\Utilities;

class AssessmentAttemptController extends Controller
{
    private $userActivityLogService;

    private $assessmentService;
    private $assessmentAttemptService;

    public function __construct()
    {
        $this->userActivityLogService = new UserActivityLogService;
        $this->assessmentAttemptService = new AssessmentAttemptService;
        $this->assessmentService = new AssessmentService;
    }

    public function start(StartAssessment $request)
    {
        try{
            $data = $request->validated();
            $assessment = $this->assessmentService->assessment($data['assessmentId']);
            if(!$assessment) return Utilities::error402("Assessment not Found");

            $data['totalQuestions'] = $assessment->questions->count();
            $data['cutOffMark'] = $assessment->cut_off_mark;

            $attempt = $this->assessmentAttemptService->save($data);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Started Assessment Attempt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateAssessmentAttempt $request)
    {
        try{
            $data = $request->validated();

            $attempt = $this->assessmentAttemptService->attempt($data['attemptId']);
            if(!$attempt) return Utilities::error402("Assessment Attempt not found");

            $attempt = $this->assessmentAttemptService->update($data, $attempt);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Assessment Attempt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function submit(SubmitAssessment $request)
    {
        try{
            $data = $request->validated();

            $attempt = $this->assessmentAttemptService->attempt($data['attemptId']);
            if(!$attempt) return Utilities::error402("Assessment Attempt not found");

            $this->assessmentAttemptService->grade($data['answers'], $attempt);

            
            try {
                $this->userActivityLogService->log(Auth::user(), "Submitted Assessment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::okay("Assessment Submitted Successfully");
        }catch(\Exception $e){
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function attempt($attemptId)
    {
        if (!is_numeric($attemptId) || !ctype_digit($attemptId)) return Utilities::error402("Invalid parameter attemptID");

        $attempt = $this->assessmentAttemptService->attempt($attemptId);
        if(!$attempt) return Utilities::error402("Assessment Attempt not found");

        return Utilities::ok(new AssessmentAttemptResource($attempt));
    }
}
