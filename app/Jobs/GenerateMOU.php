<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use app\Jobs\SendMemorandumMail;

use app\Services\OrderService;
use app\Services\ClientPackageService;
use app\Services\ClientInvestmentService;
use app\Services\PaymentService;
use app\Services\ContractService;

use app\Utilities;

class GenerateMOU implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private $uploadedFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $investmentId, protected bool $sendMail = true)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $investment = app(ClientInvestmentService::class)->getInvestment($this->investmentId);
        $purchase = $investment?->order;
        try{
            if($purchase) {
                $this->uploadedFilePath = null;
                if (!$investment->memorandum_agreement_file_id || $investment->docs_uploaded == 0) {
                    $this->uploadedFilePath = app(ContractService::class)->generateMOU($purchase);
                    app(ClientInvestmentService::class)->uploadMOU($purchase, $investment);
                    $investment->refresh();
                }

                if ($investment->memorandum_agreement_file_id && $investment->mou_sent==0 && $this->sendMail) {
                // if($this->sendMail && $this->uploadedFilePath) {
                    SendMemorandumMail::dispatch($purchase, $this->uploadedFilePath);
                }else{
                    $this->removeMouFile();
                }
            }else{
                Utilities::JobLog("Investment Order not found in GenerateMOU.. investmentId: ".$this->investmentId);
            }
        }catch(\Exception $e) {
            Utilities::JobLog("Failed to Generate investment for asset.. ".$this->investmentId);
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
        Utilities::jobLog("Send MOU Mail Job failed permanently: " . $exception->getMessage());
        $this->removeMouFile();
    }

    private function removeMouFile()
    {
        // Only delete if it's a local file (not a URL)
        if (isset($this->uploadedFilePath) && !str_starts_with($this->uploadedFilePath, 'http') && file_exists($this->uploadedFilePath)) {
            unlink($this->uploadedFilePath);
        }
    }
}
