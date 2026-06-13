<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;

use app\Services\PaymentService;

class AddReceiptFileIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:add-receipt-file-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $paymentService = new PaymentService;
        $paymentService->addReceiptFileIds();
    }
}
