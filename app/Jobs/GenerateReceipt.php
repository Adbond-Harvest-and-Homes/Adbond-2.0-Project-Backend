<?php

namespace app\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;

use app\Jobs\SendPaymentEmail;

use app\Services\ReceiptService;
use app\Services\PaymentService;

use app\Utilities;

class GenerateReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    private $uploadedFilePath;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $paymentId, protected bool $sendMail = true)
    {
        // Utilities::JobLog("Generate Receipt Job triggered for Payment: ".$paymentId);
    }

    /**
     * Middleware for the job.
     */
    public function middleware(): array
    {
        // Prevent another job with the same investmentId from running simultaneously
        return [new WithoutOverlapping($this->paymentId)];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payment = app(PaymentService::class)->getPayment($this->paymentId);
        try{
            if($payment) {
                $this->uploadedFilePath = app(ReceiptService::class)->generateReceipt($payment);
                app(ReceiptService::class)->uploadReceipt($payment, $this->uploadedFilePath);

                if($this->sendMail && $this->uploadedFilePath) {
                    SendPaymentEmail::dispatch($payment, $this->uploadedFilePath);
                }else{
                    $this->removeReceiptFile();
                }
            }else{
                Utilities::JobLog("Payment not found in GenerateReceipt.. paymentId: ".$this->paymentId);
            }
        }catch(\Exception $e) {
            Utilities::JobLog("Failed to Generate receipt for payment.. ".$this->paymentId);
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
        Utilities::jobLog("GenerateReceiptJob failed permanently: " . $exception->getMessage());
        $this->removeReceiptFile();
    }

    private function removeReceiptFile()
    {
        // Only delete if it's a local file (not a URL)
        if (isset($this->uploadedFilePath) && !str_starts_with($this->uploadedFilePath, 'http') && file_exists($this->uploadedFilePath)) {
            unlink($this->uploadedFilePath);
        }
    }
}
