<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use app\Models\ProjectType;
use app\Models\Client;
use app\Models\Project;
use app\Models\Package;
use app\Models\Order;
use app\Models\ClientPackage;
use app\Models\Payment;
use app\Models\PaymentStatus;

class InvestmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                "name" => "Package 1",
                "state" => "Lagos",
                "units" => 120,
                "available_units" => 120,
                "amount" => 1200000,
                "interest_return_duration" => 4,
                "interest_return_timeline" => 12,
                "interest_return_percentage" => 12,
            ],
            [
                "name" => "Package Fixed",
                "state" => "Lagos",
                "units" => 50,
                "available_units" => 50,
                "amount" => 1000000,
                "interest_return_duration" => 3,
                "interest_return_timeline" => 12,
                "interest_return_amount" => 150000,
            ],
            [
                "name" => "Emerald Package",
                "state" => "Jos",
                "units" => 80,
                "available_units" => 80,
                "amount" => 1500000,
                "interest_return_duration" => 4,
                "interest_return_timeline" => 18,
                "interest_return_percentage" => 10,
            ],
            [
                "name" => "Silver Package",
                "state" => "Enugu",
                "units" => 100,
                "available_units" => 100,
                "amount" => 2000000,
                "interest_return_duration" => 6,
                "interest_return_timeline" => 30,
                "interest_return_amount" => 300000,
            ]
        ];
        $projects = [
            [
                "name" => "Green-wide Agro-Investment",
                "project_type_id" => ProjectType::agro()->id,
                "packages" => [$packages[0], $packages[1]]
            ],
            [
                "name" => "Smart Homes Investment",
                "project_type_id" => ProjectType::homes()->id,
                "packages" => [$packages[2], $packages[3]]
            ]
        ];

        $clients = Client::all();
        if ($clients->count() === 0) {
            $client1 = new Client;
            $client1->firstname = 'John';
            $client1->lastname = 'Doe';
            $client1->email = 'johndoe@example.com';
            $client1->password = bcrypt('password');
            $client1->save();

            $client2 = new Client;
            $client2->firstname = 'Jane';
            $client2->lastname = 'Smith';
            $client2->email = 'janesmith@example.com';
            $client2->password = bcrypt('password');
            $client2->save();

            $clients = Client::all();
        }

        $clientsIds = [];
        $selectedClientIds = [];

        if($clients->count() > 0) {
            foreach($clients as $client) {
                $clientsIds[] = $client->id;
            }
        }

        $totalSelection = ($clients->count()/2 < 4) ? $clients->count() : $clients->count()/2;
        if($clients->count() > $totalSelection) {
            for($i=$totalSelection; $i>0; $i--) {
                do{
                    $j = rand(0, count($clientsIds) -1);
                }while(in_array($j, $selectedClientIds));
                $selectedClientIds[] = $j; 
            }
        }else{
            $selectedClientIds = $clientsIds;
        }

        $packages = [];

        $user = \app\Models\User::first();
        $userId = $user ? $user->id : 1;

        $country = \app\Models\Country::where('code', 'NG')->first() ?? \app\Models\Country::first();
        $countryId = $country ? $country->id : 1;

        $paymentPeriodNormal = \app\Models\PaymentPeriodStatus::first();
        $paymentPeriodNormalId = $paymentPeriodNormal ? $paymentPeriodNormal->id : 1;

        foreach($projects as $project) {
            $projectObj = new Project;
            $projectObj->name = $project['name'];
            $projectObj->project_type_id = $project['project_type_id'];
            $projectObj->state = 'Lagos';
            $projectObj->save();

            foreach($project['packages'] as $package) {
                $stateObj = \app\Models\State::where('name', $package['state'])->first() ?? \app\Models\State::first();
                $stateId = $stateObj ? $stateObj->id : 1;

                $packageObj = new Package;
                $packageObj->name = $package['name'];
                $packageObj->state = $package['state'];
                $packageObj->units = $package['units'];
                $packageObj->available_units = $package['available_units'];
                $packageObj->amount = $package['amount'];
                $packageObj->interest_return_duration = $package['interest_return_duration'];
                $packageObj->interest_return_timeline = $package['interest_return_timeline'];
                
                $packageObj->user_id = $userId;
                $packageObj->project_id = $projectObj->id;
                $packageObj->country_id = $countryId;
                $packageObj->state_id = $stateId;

                if(isset($package['interest_return_amount'])) $packageObj->interest_return_amount = $package['interest_return_amount'];
                if(isset($package['interest_return_percentage'])) $packageObj->interest_return_percentage = $package['interest_return_percentage'];
                $packageObj->save();
                $packages[] = $packageObj;
            }
        }

        if (count($packages) > 0) {
            foreach($selectedClientIds as $clientId) {
                $package = $packages[rand(0, count($packages) - 1)];
                $order = new Order;
                $order->client_id = $clientId;
                $order->package_id = $package->id;
                $order->units = 2;
                $order->unit_price = $package->amount;
                $order->amount_payed = $order->units * $package->amount;
                $order->amount_payable = $order->units * $package->amount;
                $order->balance = 0;
                $order->payment_status_id = PaymentStatus::pending()->id;
                $order->order_date = now();
                $order->payment_period_status_id = $paymentPeriodNormalId;
                $order->save();
            }
        }

    }

    /*
        $table->foreignId("client_id")->references("id")->on("clients");
            $table->foreignId("package_id")->references("id")->on("packages");
            $table->double("units");
            $table->double("amount_payed");
            $table->double("amount_payable");
            $table->foreignId("promo_code_id")->nullable()->references("id")->on("promo_codes");
            $table->boolean("is_installment")->default(false);
            $table->double("balance");
            $table->foreignId("payment_status_id");
            $table->date("order_date");
            $table->date("payment_due_date")->nullable();
            $table->date("grace_period_end_date")->nullable();
            $table->date("penalty_period_end_date")->nullable();
            $table->foreignId("payment_period_status_id");
    */
}
