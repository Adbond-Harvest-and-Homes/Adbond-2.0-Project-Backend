<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use app\Models\Bank;
use app\Models\BankAccount;

class AddCompanyBankAccount extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                "name" => "Adbond Harvest and Homes",
                "number" => "0506705918",
                "bankName" => "Guaranty Trust Bank",
            ],
        ];

        foreach ($accounts as $account) {
            $bank = Bank::where("name", $account["bankName"])->first();
            if (!$bank) {
                $this->command?->error("Bank not found: {$account['bankName']}. Skipping account {$account['number']}.");
                continue;
            }

            BankAccount::firstOrCreate(
                ["number" => $account["number"]],
                [
                    "name" => $account["name"],
                    "bank_id" => $bank->id,
                ]
            );
        }
    }
}
