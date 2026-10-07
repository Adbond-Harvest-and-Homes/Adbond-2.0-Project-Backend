<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use app\Models\Package;
use app\Models\Order;
use app\Models\ClientBond;
use app\Models\ClientPackage;

use app\Services\ClientBondService;
use app\Services\ClientPackageService;
use app\Services\ContractService;
use app\Services\ReceiptService;
use app\Services\FileService;

use app\Mail\BondDocumentCorrection;

use app\Enums\PackageType;
use app\Enums\ClientPackageOrigin;

use app\Utilities;

class BackfillBondDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bond:backfill-documents
        {package : Package ID or exact package name}
        {--apply : Create the missing ClientBond records and (re)generate the PDF documents. Without this flag, nothing is written; the command only reports what it would do.}
        {--send : Email the corrected documents to affected clients. Requires --apply. Without this flag, documents are generated/uploaded but no email is sent.}
        {--force : Re-send even if this bond already has a MOU marked as sent (use only if intentionally re-sending).}
        {--remove-old-asset : Soft-delete the stale land-origin ClientPackage asset row the wrong flow created for each order, so the client is left with one correct bond asset instead of two. Requires --apply.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Backfill the missing Bond Home Participation Agreement (and refresh the receipt) for orders placed under a package that was mistakenly saved with the wrong type, so clients who received the wrong land-offer-letter document get the correct one.";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $packageArg = $this->argument('package');
        $package = is_numeric($packageArg)
            ? Package::find($packageArg)
            : Package::where('name', $packageArg)->first();

        if (!$package) {
            $this->error("Package not found: {$packageArg}");
            return self::FAILURE;
        }

        if ($package->type !== PackageType::BOND->value) {
            $this->error("Package [{$package->id}] {$package->name} has type '{$package->type}', not 'bond'. Fix the package type first, then re-run this command.");
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $send = (bool) $this->option('send');
        $force = (bool) $this->option('force');
        $removeOldAsset = (bool) $this->option('remove-old-asset');

        if ($send && !$apply) {
            $this->error('--send requires --apply.');
            return self::FAILURE;
        }

        if ($removeOldAsset && !$apply) {
            $this->error('--remove-old-asset requires --apply.');
            return self::FAILURE;
        }

        $orders = Order::where('package_id', $package->id)->get();

        $this->info("Package [{$package->id}] {$package->name} — {$orders->count()} order(s) found.");
        $this->line($apply
            ? ($send ? 'Mode: APPLY + SEND (writing records and emailing clients)' : 'Mode: APPLY only (writing records, no emails sent)')
            : 'Mode: DRY RUN (no writes, no emails — pass --apply to act)');
        $this->newLine();

        $rows = [];

        foreach ($orders as $order) {
            $client = $order->client;
            $existingBond = ClientBond::where('order_id', $order->id)->first();

            if (!$order->completed) {
                $rows[] = [$order->order_number, $client?->full_name ?? 'N/A', 'skipped', 'order not marked completed'];
                continue;
            }

            if (!$client?->email) {
                $rows[] = [$order->order_number, $client?->full_name ?? 'N/A', 'skipped', 'client has no email on file'];
                continue;
            }

            if ($existingBond && $existingBond->mou_sent && !$force) {
                $rows[] = [$order->order_number, $client->full_name, 'skipped', 'bond MOU already sent — use --force to resend'];
                continue;
            }

            $staleAsset = ClientPackage::where('purchase_id', $order->id)
                ->where('purchase_type', Order::$type)
                ->where('origin', ClientPackageOrigin::ORDER->value)
                ->first();

            if (!$apply) {
                $rows[] = [
                    $order->order_number,
                    $client->full_name,
                    'would backfill',
                    ($existingBond ? 'has bond record, would regenerate documents' : 'no bond record yet, would create it')
                        . ($staleAsset ? '; would leave stale land-asset row (pass --remove-old-asset to clean up)' : '')
                ];
                continue;
            }

            try {
                $bond = $existingBond;
                DB::transaction(function () use ($order, &$bond) {
                    if (!$bond) {
                        $bond = app(ClientBondService::class)->saveBond($order, []);
                    }
                    app(ClientPackageService::class)->saveClientPackageBond($bond);
                });

                if ($removeOldAsset && $staleAsset) {
                    $staleAsset->delete();
                }

                app(ContractService::class)->generateBondMOU($order->fresh());
                app(ClientBondService::class)->uploadMOU($order->fresh(), $bond->fresh());
                $bond = $bond->fresh();

                $payment = $order->payments()->latest()->first();
                if ($payment) {
                    $receiptPath = app(ReceiptService::class)->generateReceipt($payment);
                    app(ReceiptService::class)->uploadReceipt($payment, $receiptPath);
                    $payment = $payment->fresh();
                }

                $result = 'documents generated';

                if ($send) {
                    if (!$bond->mou_file_id) {
                        $rows[] = [$order->order_number, $client->full_name, 'generated, NOT sent', 'bond document failed to upload — check logs'];
                        continue;
                    }

                    $bondFile = app(FileService::class)->getFile($bond->mou_file_id);
                    $bondFileUrl = $bondFile ? $bondFile->url : null;

                    $receiptFileUrl = null;
                    if ($payment && $payment->receipt_file_id) {
                        $receiptFile = app(FileService::class)->getFile($payment->receipt_file_id);
                        $receiptFileUrl = $receiptFile ? $receiptFile->url : null;
                    }

                    if (!$bondFileUrl) {
                        $rows[] = [$order->order_number, $client->full_name, 'generated, NOT sent', 'bond document URL not found after upload'];
                        continue;
                    }

                    Mail::to($client->email)->send(new BondDocumentCorrection($client, $bondFileUrl, $receiptFileUrl));
                    $bond->markMouSent();

                    $result = 'generated and emailed';
                }

                if ($removeOldAsset && $staleAsset) {
                    $result .= ', stale asset removed';
                } elseif ($staleAsset) {
                    $result .= ', stale land-asset row still present';
                }

                $rows[] = [$order->order_number, $client->full_name, $result, "bond_id={$bond->id}"];
            } catch (\Exception $e) {
                Utilities::logStuff("BackfillBondDocuments failed for order {$order->id}: " . $e->getMessage());
                $rows[] = [$order->order_number, $client->full_name ?? 'N/A', 'ERROR', $e->getMessage()];
            }
        }

        $this->table(['Order #', 'Client', 'Result', 'Notes'], $rows);

        return self::SUCCESS;
    }
}
