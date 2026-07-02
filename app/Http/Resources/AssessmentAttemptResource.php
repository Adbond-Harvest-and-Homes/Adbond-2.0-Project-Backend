<?php

namespace app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use app\Http\Resources\AssessmentResource;
use app\Http\Resources\AssessmentAttemptAnswerResource;
use app\Http\Resources\VirtualStaffCategoryResource;
use App\Http\Resources\UserResource;

class AssessmentAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "firstname" => $this->firstname,
            "lastname" => $this->surname,
            "email" => $this->email,
            "phoneNumber" => $this->phone_number,
            "gender" => $this->gender,
            "address" => $this->address,
            "occupation" => $this->occupation,
            "score" => $this->score,
            "passed" => ($this->passed === null) ? "pending" : (($this->passed == 1) ? true : false),
            "status" => ($this->approved == null) ? "pending" : (($this->approved == 1) ? "approved" : "rejected"),
            "submittedAt" => $this->created_at,
            "timeUsed" => $this->time_used,
            "category" => new VirtualStaffCategoryResource($this->category),
            "answers" => AssessmentAttemptAnswerResource::collection($this->answers),
            "assessment" => new AssessmentResource($this->assessment),
            "treatedBy" => new UserResource($this->whenLoaded('treatedBy')),

        ];
    }
}
