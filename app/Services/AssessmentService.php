<?php

namespace app\Services;

use Maatwebsite\Excel\Facades\Excel;
use PDF;

use app\Models\Assessment;

class AssessmentService
{

    public function save($data)
    {
        $assessment = new Assessment;
        $assessment->title = $data['title'];
        if (isset($data['description'])) $assessment->description = $data['description'];
        if (isset($data['instructions'])) $assessment->instructions = $data['instructions'];
        if (isset($data['duration'])) $assessment->duration = $data['duration'];
        if (isset($data['cutOffMark'])) $assessment->cut_off_mark = $data['cutOffMark'];
        if (isset($data['active'])) $assessment->active = $data['active'];

        $assessment->save();

        return $assessment;
    }

    public function update($data, $assessment)
    {
        if (isset($data['title'])) $assessment->title = $data['title'];
        if (isset($data['description'])) $assessment->description = $data['description'];
        if (isset($data['instructions'])) $assessment->instructions = $data['instructions'];
        if (isset($data['duration'])) $assessment->duration = $data['duration'];
        if (isset($data['cutOffMark'])) $assessment->cut_off_mark = $data['cutOffMark'];
        if (isset($data['active'])) $assessment->active = $data['active'];

        $assessment->update();

        return $assessment;
    }

    public function assessment($id, $with = [])
    {
        return Assessment::with($with)->where("id", $id)->first();
    }

    public function activeAssessment($with = [])
    {
        return Assessment::with($with)->where("active", 1)->first();
    }

    public function assessments($with = [])
    {
        return Assessment::with($with)->orderBy("created_at", "DESC")->get();
    }

    public function activate($assessment)
    {
        $assessment->active = 1;
        $assessment->update();
        return $assessment;
    }

    public function deactivate($assessment)
    {
        $assessment->active = 0;
        $assessment->update();
        return $assessment;
    }

    public function delete($assessment)
    {
        $assessment->delete();
    }
}
