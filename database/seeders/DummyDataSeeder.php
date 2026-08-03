<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use app\Models\User;
use app\Models\Client;
use app\Models\Country;
use app\Models\State;
use app\Models\Project;
use app\Models\Package;
use app\Models\PaymentMode;
use app\Models\PaymentStatus;
use app\Models\Order;

use app\Services\ClientService;
use app\Services\PackageService;
use app\Services\OrderService;
use app\Services\PaymentService;
use app\Services\ClientBondService;

use app\Enums\PackageType;
use app\Enums\BondOwnershipType;
use app\Enums\BondTimeMetric;
use app\Enums\BondOccurrenceMetric;
use app\Enums\Measurement;
use app\Enums\PaymentPurpose;
use app\Enums\ClientBondStatus;

/**
 * Seeds a connected set of dummy records (clients, projects, packages, orders,
 * payments, client bonds) for local API testing. Purely additive data — run
 * with: php artisan db:seed --class=Database\\Seeders\\DummyDataSeeder
 *
 * Safe to re-run: every step looks up existing records by name/email first.
 */
class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reference/lookup data these flows depend on (idempotent - firstOrCreate based)
        (new Roles)->run();
        (new StaffTypes)->run();
        (new ProjectTypes)->run();
        (new PaymentModes)->run();
        (new PaymentStatuses)->run();
        (new PaymentPeriodStatuses)->run();
        (new PaymentGateways)->run();
        $this->seedNigerianStates();

        // 2. Staff users, projects, land/home packages (existing seeders)
        (new Users)->run();
        (new Projects)->run();
        // Packages.php names packages with randomized text, so it isn't safe to
        // re-run - it would pile up duplicates. Only seed if none exist yet.
        if (Package::where('type', PackageType::NON_INVESTMENT->value)->count() === 0) {
            (new Packages)->run();
        }

        // 3. A bond-type package (none of the existing seeders create one)
        $bondPackage = $this->seedBondPackage();

        // 4. Dummy clients (all share password: Password123!)
        $clients = $this->seedClients();

        // 5. Orders + Payments + ClientBonds tying it all together
        $this->seedPurchases($clients, $bondPackage);

        // 6. Wallets for every client
        (new WalletSeeder)->run();

        $this->info('Dummy data seeded: '.count($clients).' clients, purchases, and bonds ready for testing.');
        $this->info('Client login: any seeded client email + password "Password123!" via POST /api/v2/auth/client/login');
    }

    private function info(string $message): void
    {
        if ($this->command) $this->command->info($message);
    }

    private function seedNigerianStates(): void
    {
        $nigeria = Country::where('code', 'NG')->first();
        if (!$nigeria) return;

        foreach (['Lagos', 'Ogun', 'FCT - Abuja', 'Rivers'] as $name) {
            State::firstOrCreate(['name' => $name, 'country_id' => $nigeria->id]);
        }
    }

    private function seedBondPackage(): ?Package
    {
        $homesProject = Project::whereName('Smart Homes Estate')->first();
        $admin = User::first();
        $state = State::first();

        if (!$homesProject || !$admin || !$state) return null;

        $packageService = new PackageService;
        $packageService->projectId = $homesProject->id;
        $existing = $packageService->getByName('Homestead Bond Package');
        if ($existing) return $existing;

        return $packageService->save([
            'name' => 'Homestead Bond Package',
            'userId' => $admin->id,
            'projectId' => $homesProject->id,
            'stateId' => $state->id,
            'address' => 'Plot 12, Heritage City Homestead, '.$state->name,
            'size' => 500,
            'amount' => 3000000,
            'units' => 20,
            'minPrice' => 600000,
            'installmentOption' => true,
            'installmentDuration' => 12,
            'infrastructureFee' => 150000,
            'description' => 'Dummy bond package seeded for local API testing.',
            'type' => PackageType::BOND->value,
            'bondOwnershipType' => BondOwnershipType::CO_OWNERSHIP->value,
            'bondSlots' => 10,
            'bondCountDown' => 30,
            'bondCountDownMetric' => BondTimeMetric::DAYS->value,
            'bondInvestmentDuration' => 2,
            'bondInvestmentDurationMetric' => BondTimeMetric::YEARS->value,
            'bondNetRentalIncome' => 10,
            'bondNetRentalIncomeMeasurement' => Measurement::PERCENTAGE->value,
            'bondNetRentalIncomeTimeline' => BondOccurrenceMetric::QUARTERLY->value,
            'bondAssetAppreciation' => 15,
            'bondAssetAppreciationMeasurement' => Measurement::PERCENTAGE->value,
            'bondAssetAppreciationTimeline' => BondOccurrenceMetric::YEARLY->value,
        ]);
    }

    /** @return Client[] */
    private function seedClients(): array
    {
        $clientService = new ClientService;
        $states = State::all();
        $country = Country::where('code', 'NG')->first();

        $dummyClients = [
            ['firstname' => 'Chinedu', 'lastname' => 'Okafor', 'email' => 'chinedu.okafor@example.test'],
            ['firstname' => 'Amaka', 'lastname' => 'Eze', 'email' => 'amaka.eze@example.test'],
            ['firstname' => 'Tunde', 'lastname' => 'Bakare', 'email' => 'tunde.bakare@example.test'],
            ['firstname' => 'Ngozi', 'lastname' => 'Umeh', 'email' => 'ngozi.umeh@example.test'],
            ['firstname' => 'Segun', 'lastname' => 'Adewale', 'email' => 'segun.adewale@example.test'],
            ['firstname' => 'Blessing', 'lastname' => 'Ibe', 'email' => 'blessing.ibe@example.test'],
        ];

        $clients = [];
        foreach ($dummyClients as $i => $data) {
            $client = $clientService->getClientByEmail($data['email']);
            if (!$client) {
                $client = $clientService->save([
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'],
                    'email' => $data['email'],
                    'password' => 'Password123!',
                    'emailVerifiedAt' => now(),
                    'phoneNumber' => '080'.rand(10000000, 99999999),
                    'stateId' => $states->isNotEmpty() ? $states[$i % $states->count()]->id : null,
                    'countryId' => $country?->id,
                ]);
            }
            $clients[] = $client;
        }

        return $clients;
    }

    private function seedPurchases(array $clients, ?Package $bondPackage): void
    {
        if (empty($clients)) return;

        if (Order::count() > 0) {
            $this->info('Orders already exist — skipping purchase/payment/bond seeding to avoid duplicates.');
            return;
        }

        $orderService = new OrderService;
        $paymentService = new PaymentService;
        $packageService = new PackageService;
        $clientBondService = new ClientBondService;

        $landPackages = Package::where('type', PackageType::NON_INVESTMENT->value)
            ->inRandomOrder()->take(4)->get();

        $paymentModeId = PaymentMode::bankTransfer()->id;

        // -- Fully paid / completed purchases (2) --
        foreach ($landPackages->slice(0, 2) as $i => $package) {
            $client = $clients[$i % count($clients)];
            $amountPayable = $package->amount;

            $order = $orderService->save([
                'clientId' => $client->id,
                'packageId' => $package->id,
                'units' => 1,
                'amountPayable' => $amountPayable,
                'unitPrice' => $package->amount,
                'isInstallment' => false,
                'paymentStatusId' => PaymentStatus::pending()->id,
                'orderDate' => now()->subDays(10)->toDateString(),
            ]);

            $payment = $paymentService->save([
                'purchaseId' => $order->id,
                'purchaseType' => Order::$type,
                'clientId' => $client->id,
                'amount' => $amountPayable,
                'paymentModeId' => $paymentModeId,
                'confirmed' => true,
                'success' => true,
                'paymentDate' => now()->subDays(9)->toDateString(),
                'purpose' => PaymentPurpose::PACKAGE_FULL_PAYMENT->value,
            ]);

            $orderService->update([
                'amountPayed' => $amountPayable,
                'paymentStatusId' => PaymentStatus::complete()->id,
            ], $order);

            $orderService->completeOrder($order->fresh(), $payment);
            $packageService->deductUnits($package);
        }

        // -- In-progress installment purchases, not yet completed (2) --
        foreach ($landPackages->slice(2, 2) as $i => $package) {
            $client = $clients[($i + 2) % count($clients)];
            $amountPayable = $package->amount;
            $installmentCount = 6;
            $amountPerInstallment = round($amountPayable / $installmentCount);

            $order = $orderService->save([
                'clientId' => $client->id,
                'packageId' => $package->id,
                'units' => 1,
                'amountPayable' => $amountPayable,
                'unitPrice' => $package->amount,
                'isInstallment' => true,
                'installmentCount' => $installmentCount,
                'paymentStatusId' => PaymentStatus::deposit()->id,
                'orderDate' => now()->subDays(5)->toDateString(),
                'paymentDueDate' => now()->addDays(25)->toDateString(),
            ]);

            $paymentService->save([
                'purchaseId' => $order->id,
                'purchaseType' => Order::$type,
                'clientId' => $client->id,
                'amount' => $amountPerInstallment,
                'paymentModeId' => $paymentModeId,
                'confirmed' => true,
                'success' => true,
                'paymentDate' => now()->subDays(4)->toDateString(),
                'purpose' => PaymentPurpose::INSTALLMENT_PAYMENT->value,
            ]);

            $orderService->update([
                'amountPayed' => $amountPerInstallment,
                'installmentsPayed' => 1,
            ], $order);
        }

        if (!$bondPackage) return;

        // -- Bond purchase #1: fully paid, started, with one payout recorded --
        $bondClient = $clients[4] ?? $clients[0];
        $bondAmount = $bondPackage->bond_slots_amount ?? $bondPackage->amount;

        $bondOrder = $orderService->save([
            'clientId' => $bondClient->id,
            'packageId' => $bondPackage->id,
            'units' => 1,
            'amountPayable' => $bondAmount,
            'unitPrice' => $bondAmount,
            'isInstallment' => false,
            'paymentStatusId' => PaymentStatus::pending()->id,
            'orderDate' => now()->subDays(40)->toDateString(),
        ]);

        $bondPayment = $paymentService->save([
            'purchaseId' => $bondOrder->id,
            'purchaseType' => Order::$type,
            'clientId' => $bondClient->id,
            'amount' => $bondAmount,
            'paymentModeId' => $paymentModeId,
            'confirmed' => true,
            'success' => true,
            'paymentDate' => now()->subDays(39)->toDateString(),
            'purpose' => PaymentPurpose::PACKAGE_FULL_PAYMENT->value,
        ]);

        $clientBondService->saveBond($bondOrder, []);

        $orderService->update([
            'amountPayed' => $bondAmount,
            'paymentStatusId' => PaymentStatus::complete()->id,
        ], $bondOrder);

        $orderService->completeOrder($bondOrder->fresh(), $bondPayment);

        $activeBond = $clientBondService->getByOrderId($bondOrder->id);
        if ($activeBond) {
            // ClientBondService::start() only computes the dates - it doesn't flip
            // the started flag or status, so an "active" test bond needs this too.
            $activeBond->started = true;
            $activeBond->status = ClientBondStatus::ACTIVE->value;
            $activeBond->save();

            $clientBondService->addPayout($activeBond, $clientBondService->getPayout($activeBond));
        }

        // -- Bond purchase #2: order placed, bond not yet started (pending) --
        $bondClient2 = $clients[5] ?? $clients[1];
        $bondOrder2 = $orderService->save([
            'clientId' => $bondClient2->id,
            'packageId' => $bondPackage->id,
            'units' => 1,
            'amountPayable' => $bondAmount,
            'unitPrice' => $bondAmount,
            'isInstallment' => false,
            'paymentStatusId' => PaymentStatus::pending()->id,
            'orderDate' => now()->toDateString(),
        ]);
        $clientBondService->saveBond($bondOrder2, []);
    }
}
