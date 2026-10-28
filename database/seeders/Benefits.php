<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use app\Models\Benefit;

class Benefits extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benefits = [
            ["name" => "Survey Plan", "icon" => env('APP_URL') . Storage::url('public/benefits/Survey Plan.svg')],
            ["name" => "Communal Living", "icon" => env('APP_URL') . Storage::url('public/benefits/Communal Living.svg')],
            ["name" => "Good Drainage System", "icon" => env('APP_URL') . Storage::url('public/benefits/Good Drainage System.svg')],
            ["name" => "Good Road Network", "icon" => env('APP_URL') . Storage::url('public/benefits/Good Road Network.svg')],
            ["name" => "Perimeter Fencing", "icon" => env('APP_URL') . Storage::url('public/benefits/Perimeter Fencing.svg')],
            ["name" => "Power Supply", "icon" => env('APP_URL') . Storage::url('public/benefits/Power Supply.svg')],
            ["name" => "Instant allocation", "icon" => env('APP_URL') . Storage::url('public/benefits/Survey Plan.svg')],
            ["name" => "Deed of Assignment", "icon" => env('APP_URL') . Storage::url('public/benefits/Survey Plan.svg')],
            ["name" => "Security", "icon" => env('APP_URL') . Storage::url('public/benefits/Survey Plan.svg')],
            ["name" => "Layout", "icon" => env('APP_URL') . Storage::url('public/benefits/Survey Plan.svg')],

            // Legacy Green Homestead (LGH) leasehold apartment benefits
            ["name" => "Prime Lagos Location", "icon" => env('APP_URL') . Storage::url('public/benefits/Prime Lagos Location.svg')],
            ["name" => "Rental Income Potential", "icon" => env('APP_URL') . Storage::url('public/benefits/Rental Income Potential.svg')],
            ["name" => "Free Plot of Land at Legacy Green Homestead(LCH)", "icon" => env('APP_URL') . Storage::url('public/benefits/Free Plot of Land.svg')],
        ];

        foreach($benefits as $benefit) {
            $benefitObj = Benefit::where("name", $benefit['name'])->first();
            if(!$benefitObj) $benefitObj = new Benefit;

            // Keep the icon in sync even for rows that already exist, so a
            // stale APP_URL (or any other icon correction) self-heals on the
            // next deploy instead of silently sticking forever.
            $benefitObj->name = $benefit['name'];
            $benefitObj->icon = $benefit['icon'];
            $benefitObj->save();
        }
    }
}
