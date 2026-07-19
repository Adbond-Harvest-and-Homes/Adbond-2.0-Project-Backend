<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use app\Models\Project;
use app\Models\State;

class LoadProjectStates extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        if($projects->count() > 0) {
            foreach($projects as $project) {
                $state = State::where("name", $project->state)->first();
                if($state){
                    $project->state_id = $state->id;
                    $project->update();
                }
            }
        }
    }
}
