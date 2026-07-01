<?php

namespace app\Http\Controllers\User;

use Illuminate\Http\Request;
use app\Services\UserActivityLogService;
use Illuminate\Support\Facades\Auth;
use app\Http\Controllers\Controller;

use app\Http\Requests\User\StartAssessment;
use app\Http\Requests\User\UpdateAssessmentAttempt;
use app\Http\Requests\User\SubmitAssessment;
use app\Http\Requests\User\ApproveAssessmentAttempt;

use app\Http\Resources\AssessmentAttemptResource;
use app\Http\Resources\AssessmentResource;

use app\Services\AssessmentAttemptService;
use app\Services\AssessmentService;
use app\Services\UserService;

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

    public function activeAssessment()
    {
        $assessment = $this->assessmentService->activeAssessment();

        if (!$assessment) return Utilities::error402("Assessment not found");

        return Utilities::ok(
            new AssessmentResource($assessment)
        );
    }

    public function start(StartAssessment $request)
    {
        try {
            $data = $request->validated();
            if (isset($data['assessmentId'])) {
                $assessment = $this->assessmentService->assessment($data['assessmentId']);
            } else {
                $assessment = $this->assessmentService->activeAssessment();
            }
            if (!$assessment) return Utilities::error402("Assessment not Found");

            $data['totalQuestions'] = $assessment->questions->count();
            $data['cutOffMark'] = $assessment->cut_off_mark;

            $attempt = $this->assessmentAttemptService->save($data);


            try {
                $this->userActivityLogService->log(Auth::user(), "Started Assessment Attempt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function update(UpdateAssessmentAttempt $request)
    {
        try {
            $data = $request->validated();

            $attempt = $this->assessmentAttemptService->attempt($data['attemptId']);
            if (!$attempt) return Utilities::error402("Assessment Attempt not found");

            $attempt = $this->assessmentAttemptService->update($data, $attempt);


            try {
                $this->userActivityLogService->log(Auth::user(), "Updated Assessment Attempt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function submit(SubmitAssessment $request)
    {
        try {
            $data = $request->validated();

            $attempt = $this->assessmentAttemptService->attempt($data['attemptId']);
            if (!$attempt) return Utilities::error402("Assessment Attempt not found");

            $this->assessmentAttemptService->grade($data['answers'], $attempt);


            try {
                $this->userActivityLogService->log(Auth::user(), "Submitted Assessment");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            $attempt->refresh();

            // return Utilities::okay("Assessment Submitted Successfully");
            return Utilities::ok(new AssessmentAttemptResource($attempt));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function attempt(int $attemptId)
    {
        if (!is_numeric($attemptId) || !ctype_digit($attemptId)) return Utilities::error402("Invalid parameter attemptID");

        $attempt = $this->assessmentAttemptService->attempt($attemptId);
        if (!$attempt) return Utilities::error402("Assessment Attempt not found");

        return Utilities::ok(new AssessmentAttemptResource($attempt));
    }

    public function applications(Request $request)
    {
        $status = ($request->query('status')) ?? null;
        $this->assessmentAttemptService->minScore = $request->query('minScore');
        $this->assessmentAttemptService->maxScore = $request->query('maxScore');

        if ($status) {
            if (!in_array($status, ['pending', 'approved', 'rejected'])) return Utilities::error402("Invalid status parameter");
            $this->assessmentAttemptService->status = $status;
        }
        $applications = $this->assessmentAttemptService->successfulAttampts(['treatedBy']);
        return AssessmentAttemptResource::collection($applications);
    }

    public function approve(ApproveAssessmentAttempt $request, int $attemptId)
    {
        try {
            if (!is_numeric($attemptId) || !ctype_digit($attemptId)) return Utilities::error402("Invalid parameter attemptID");

            $attempt = $this->assessmentAttemptService->attempt($attemptId);
            if (!$attempt) return Utilities::error402("Assessment Attempt not found");

            $note = $request->validated("note") ?? null;

            $attempt = $this->assessmentAttemptService->approve($attempt, $note);

            $userService = new UserService;
            $userService->upgradeToVirtualStaff($attempt);

            try {
                $this->userActivityLogService->log(Auth::user(), "Approved Assessment Attempt & Upgraded User");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }

    public function reject(ApproveAssessmentAttempt $request, int $attemptId)
    {
        try {
            if (!is_numeric($attemptId) || !ctype_digit($attemptId)) return Utilities::error402("Invalid parameter attemptID");

            $attempt = $this->assessmentAttemptService->attempt($attemptId);
            if (!$attempt) return Utilities::error402("Assessment Attempt not found");

            $note = $request->validated("note") ?? null;

            $attempt = $this->assessmentAttemptService->reject($attempt, $note);

            try {
                $this->userActivityLogService->log(Auth::user(), "Rejected Assessment Attempt");
            } catch (\Exception $e) {
                Utilities::logStuff("An error occurred while trying to log user activity: " . $e->getMessage());
            }

            return Utilities::ok(new AssessmentAttemptResource($attempt));
        } catch (\Exception $e) {
            return Utilities::error($e, 'An error occurred while trying to process the request, Please try again later or contact support');
        }
    }
}
