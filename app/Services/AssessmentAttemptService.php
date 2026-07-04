<?php

namespace app\Services;

use app\Http\Resources\QuestionResource;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

use app\Models\AssessmentAttempt;
use app\Models\AssessmentAttemptAnswer;

use app\Services\AssessmentQuestionService;
use app\Services\AssessmentQuestionOptionService;

use app\Utilities;

class AssessmentAttemptService
{
    public string | null $status = null;
    public $minScore = null;
    public $maxScore = null;

    public function save($data)
    {
        $attempt = new AssessmentAttempt;
        $attempt->assessment_id = $data['assessmentId'];
        $attempt->firstname = $data['firstname'];
        $attempt->category_id = $data['categoryId'];
        if (isset($data['lastname'])) $attempt->surname = $data['lastname'];
        $attempt->email = $data['email'];
        if (isset($data['phoneNumber'])) $attempt->phone_number = $data['phoneNumber'];
        if (isset($data['gender'])) $attempt->gender = $data['gender'];
        if (isset($data['address'])) $attempt->address = $data['address'];
        if (isset($data['occupation'])) $attempt->occupation = $data['occupation'];
        if (isset($data['referralCode'])) $attempt->referral_code = $data['referralCode'];
        if (isset($data['cutOffMark'])) $attempt->cut_off_mark = $data['cutOffMark'];
        $attempt->total_questions = $data['totalQuestions'];
        $attempt->started_at = now();

        $attempt->save();

        return $attempt;
    }

    public function update($data, $attempt)
    {
        if (isset($data['startedAt'])) $attempt->started_at = $data['startedAt'];
        if (isset($data['address'])) $attempt->address = $data['address'];
        if (isset($data['occupation'])) $attempt->occupation = $data['occupation'];

        $attempt->update();

        return $attempt;
    }

    public function grade($answers, $attempt)
    {
        if (is_array($answers) && count($answers) > 0) {
            $correctAnswers = 0;
            $questionService = new AssessmentQuestionService;
            $optionService = new AssessmentQuestionOptionService;
            foreach ($answers as $answer) {
                $assessmentAnswer = new AssessmentAttemptAnswer;
                $question = $questionService->question($answer['questionId']);
                $assessmentAnswer->attempt_id = $attempt->id;
                $assessmentAnswer->question_id = $answer['questionId'];
                $assessmentAnswer->answer = $optionService->option($answer['selectedOptionId'])?->value;
                $assessmentAnswer->question = $question->question;
                $assessmentAnswer->correct_answer = $question->correctOption->value;
                $assessmentAnswer->correct = ($answer['selectedOptionId'] == $question->correctOption->id) ? true : false;
                $assessmentAnswer->save();

                if ($assessmentAnswer->correct == 1) $correctAnswers++;
            }
            $attempt->score = Utilities::getPercentage($correctAnswers, $attempt->assessment->questions->count());
            $attempt->time_used = $attempt->started_at ? now()->diffInSeconds($attempt->started_at) : null;
            $attempt->update();
        }
    }

    public function approve(AssessmentAttempt $attempt, $note = null)
    {
        $attempt->approved = 1;
        if ($note) $attempt->review_note = $note;
        $attempt->save();

        return $attempt;
    }

    public function reject(AssessmentAttempt $attempt, $note = null)
    {
        $attempt->approved = 0;
        if ($note) $attempt->review_note = $note;
        $attempt->save();

        return $attempt;
    }

    public function assessmentAttempts($assessmentId)
    {
        return AssessmentAttempt::where("assessment_id", $assessmentId)->orderBy("created_at", "DESC")->get();
    }

    public function attempts($with = [])
    {
        return AssessmentAttempt::with($with)->orderBy("created_at", "DESC")->get();
    }

    public function successfulAttampts($with = [])
    {
        $approved = null;
        if ($this->status) {
            switch ($this->status) {
                case "pending":
                    $approved = null;
                    break;
                case "approved":
                    $approved = 1;
                    break;
                case "rejected":
                    $approved = 0;
                    break;
            }
        }
        return AssessmentAttempt::with($with)->where("cancelled", 0)->where("disqualified", 0)
            ->when($this->status, fn($query) => $query->where("approved", $approved))
            ->when($this->minScore, fn($query) => $query->where("score", "<=", $this->minScore))
            ->when($this->maxScore, fn($query) => $query->where("score", ">=", $this->maxScore))
            ->orderBy("created_at")->get();
    }

    public function attempt($attemptId)
    {
        return AssessmentAttempt::find($attemptId);
    }
}
