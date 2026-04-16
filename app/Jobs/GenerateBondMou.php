<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use app\Jobs\SendBondMemorandumMail;

use app\Services\ClientBondService;
use app\Services\ContractService;

use app\Utilities;

class GenerateBondMou implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private $uploadedFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $bondId, protected bool $sendMail = true)
    {
        //
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent another job with the same bondId from running simultaneously
        return [new WithoutOverlapping($this->bondId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bond = app(ClientBondService::class)->getBond($this->bondId);
        $purchase = $bond?->order;
        try{
            if($purchase) {
                $this->uploadedFilePath = null;
                if (!$bond->mou_file_id || $bond->docs_uploaded == 0) {
                    $this->uploadedFilePath = app(ContractService::class)->generateBondMOU($purchase);
                    app(ClientBondService::class)->uploadMOU($purchase, $bond);
                    $bond->refresh();
                }

                if ($bond->mou_file_id && $bond->mou_sent==0 && $this->sendMail) {
                // if($this->sendMail && $this->uploadedFilePath) {
                    SendBondMemorandumMail::dispatch($purchase, $this->uploadedFilePath);
                }else{
                    $this->removeMouFile();
                }
            }else{
                Utilities::JobLog("Bond Order not found in GenerateMOU.. bondId: ".$this->bondId);
            }
        }catch(\Exception $e) {
            Utilities::JobLog("Failed to Generate bond for asset.. ".$this->bondId);
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
