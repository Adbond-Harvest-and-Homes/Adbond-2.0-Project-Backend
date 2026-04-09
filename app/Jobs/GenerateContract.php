<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use app\Jobs\SendContractMail;

use app\Services\OrderService;
use app\Services\ClientPackageService;
use app\Services\PaymentService;
use app\Services\ContractService;

use app\Utilities;

class GenerateContract implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private $uploadedFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $assetId, protected bool $isOffer=false, protected bool $sendMail = true)
    {
        //
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent another job with the same assetId from running simultaneously
        return [new WithoutOverlapping($this->assetId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $asset = app(ClientPackageService::class)->clientPackage($this->assetId);
        $purchase = $asset?->purchase;
        try{
            if($purchase) {
                $this->uploadedFilePath = null;

                // Generate and upload contract if it has not been generated
                if (!$asset->contract_file_id || $asset->docs_uploaded == 0) {
                    $this->uploadedFilePath = app(ContractService::class)->generateContract($purchase, $this->isOffer);
                    app(ContractService::class)->uploadContract($purchase, $asset);
                    $asset->refresh();
                }

                // Dispatch mail if contract exists
                if ($asset->contract_file_id && $asset->contract_sent==0 && $this->sendMail) {
                // if($this->sendMail && $this->uploadedFilePath) {
                    SendContractMail::dispatch($asset, $this->uploadedFilePath);
                }else{
                    $this->removeContractFile();
                }
            }else{
                Utilities::JobLog("Asset Order not found in GenerateContract.. assetId: ".$this->assetId);
            }
        }catch(\Exception $e) {
            Utilities::JobLog("Failed to Generate contract for asset.. ".$this->assetId);
            Utilities::JobLog($e->getMessage());
            Utilities::JobLog($e);

            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Utilities::jobLog("GenerateContractJob failed permanently: " . $exception->getMessage());
        $this->removeContractFile();
    }

    private function removeContractFile()
    {
        // Only delete if it's a local file (not a URL)
        if (isset($this->uploadedFilePath) && !str_starts_with($this->uploadedFilePath, 'http') && file_exists($this->uploadedFilePath)) {
            unlink($this->uploadedFilePath);
        }
    }
}
