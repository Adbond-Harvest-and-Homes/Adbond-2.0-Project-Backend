<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;

use app\Services\PaymentService;
use app\Services\ReceiptService;
use app\Services\FileService;

use app\Mail\NewPayment;

use app\Utilities;

class CheckAndGenerateReceipts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-and-generate-receipts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates and sends missing receipts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get payments with no receipt or receipt not sent
        $payments = app(PaymentService::class)->getMissingOrUnsentReceipts();

        foreach($payments as $payment) {
            if(!$payment->receipt_file_id){
                // Generate receipt
                $filePath = app(ReceiptService::class)->generateReceipt($payment);

                //Upload the receipt
                app(ReceiptService::class)->uploadReceipt($payment, $filePath);

                //mark as uploaded
                $payment->markDocUploaded();

                //send the receipt to client's mail
                // $this->sendReceipt($payment, $filePath);
            }else{
                if($payment->receipt_sent == 0) {
                    // $this->sendReceipt($payment);
                }
            }
        }
    }

    private function sendReceipt($payment, $uploadedReceipt=null)
    {
        try {
            // If uploadedFile is not provided, try to get it from Cloudinary via asset model
            if (!$uploadedReceipt && $payment->receipt_file_id) {
                $file = app(FileService::class)->getFile($payment->receipt_file_id); // File::find($asset->contract_file_id);
                if ($file) {
                    $uploadedReceipt = $file->url;
                }
            }

            if (!$uploadedReceipt) {
                Utilities::jobLog("Receipt file not found for payment ID: " . $payment->id);
                return;
            }

            // Send Payment Mail
            Mail::to($payment->client->email)
                ->send(new NewPayment($payment, $uploadedReceipt));

            $payment->markReceiptSent();
            
            // Clean up receipt file
            if (isset($uploadedReceipt) && file_exists($uploadedReceipt)) {
                unlink($uploadedReceipt);
            }

        } catch (\Exception $e) {
            Utilities::jobLog("Error Occurred while attempting to send Payment Email: " . $e->getMessage());
            
            // Optional: Retry the job if failed
            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }
}
