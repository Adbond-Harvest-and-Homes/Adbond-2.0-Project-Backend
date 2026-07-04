<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use app\Models\Project;
use app\Models\State;
use app\Models\User;
use app\Services\PackageService;
use Illuminate\Support\Facades\Log;

class Packages1 extends Seeder
{
    private $packageTypes = [
        'Premium',
        'Standard',
        'Deluxe',
        'Executive',
        'Classic',
        'Elite',
        'Comfort',
        'Luxury',
        'Basic',
        'Superior'
    ];

    private $benefitsList = [
        'Fertile Soil',
        'Developing Area',
        'Close to Highway',
        'Gated Community',
        'Good Drainage',
        'Proximity to Market',
        'Easy Access Road',
        'Green Area',
        'Recreational Facilities',
        'Security Post',
        'Street Lights',
        'Water Supply',
        'Electricity',
        'School Nearby',
        'Hospital Nearby'
    ];

    public function run(): void
    {
        $packageService = new PackageService;
        $user = User::first();

        if (!$user) {
            Log::error('No user found for package seeding');
            return;
        }

        $packages = [
            [
                "name" => "Coper 1 & Undergraduate Package",
                "userId" => $user->id,
                "project" => "Ibeju Country Home 2",
                "state" => "Lagos",
                "address" => "Shimawa, Lagos State",
                "size" => 648,
                "amount" => 1000000,
                "units" => 100,
                "discount" => 5,
                "minPrice" => 200000,
                "installmentDuration" => 16,
                "infrastructureFee" => 200000,
                "description" => "A Spacious and louscious land",
                "benefits" => ["Fertile", "Developing"],
                "installmentOption" => true
            ],
            [
                "name" => "Pension and Children Package",
                "userId" => $user->id,
                "project" => "Ibeju Country Home 2",
                "state" => "Lagos",
                "address" => "Otta, Lagos State",
                "size" => 648,
                "amount" => 1000000,
                "units" => 100,
                "discount" => 5,
                "minPrice" => 200000,
                "installmentDuration" => 16,
                "infrastructureFee" => 200000,
                "description" => "A Spacious and louscious land",
                "benefits" => ["Fertile", "Developing"],
                "installmentOption" => true
            ],
        ];

        foreach ($packages as $packageData) {
            try {
                $project = Project::whereName($packageData['project'])->first();
                $state = State::whereName($packageData['state'])->first();

                if (!$project) {
                    Log::warning("Project {$packageData['project']} not found");
                    continue;
                }

                if (!$state) {
                    Log::warning("State {$packageData['state']} not found");
                    continue;
                }

                $packageData['projectId'] = $project->id;
                $packageData['stateId'] = $state->id;

                $existingPackage = $packageService->getByName($packageData['name'], $project->id);

                if (!$existingPackage) {
                    $packageService->save($packageData);
                    $this->command->info("Created package: {$packageData['name']}");
                } else {
                    $this->command->info("Package already exists: {$packageData['name']}");
                }
            } catch (\Exception $e) {
                Log::error("Error seeding package {$packageData['name']}: " . $e->getMessage());
                $this->command->error("Failed to create package: {$packageData['name']}");
            }
        }
    }
}
