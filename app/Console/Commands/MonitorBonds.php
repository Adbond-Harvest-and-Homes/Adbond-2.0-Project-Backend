<?php

namespace app\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use app\Jobs\BondPayout;

use app\Services\ClientBondService;

class MonitorBonds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-bonds';

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
        $notStartedBonds = app(ClientBondService::class)->notStartedBonds();
        if($notStartedBonds->count() > 0) {
            foreach($notStartedBonds as $notStartedBond) {
                $startDate = Carbon::parse($notStartedBond->start_date);

                // if ($startDate->isToday() || $startDate->isPast()) {

                // }

                // Check if today or in the past (including today)
                // if ($startDate->lte(Carbon::today())) {
                //     // Today or past
                // }

                // Check if not in the future
                if (!$startDate->isFuture()) {
                    // Today or past
                    $notStartedBond->started = 1;
                    $notStartedBond->save();
                }

                // // Check if date has passed (strictly before today)
                // if ($startDate->lt(Carbon::today())) {
                //     // Strictly before today (not including today)
                // }
            }
        }


        //for bonds that has started but not ended

        $startedBonds = app(ClientBondService::class)->runningBonds();
        if($startedBonds->count() > 0) {
            foreach($startedBonds as $startedBond) {
                $payoutDate = Carbon::parse($startedBond->next_capital_payout);

                if (!$payoutDate->isFuture()) {
                    // Today or past
                    $payout = app(ClientBondService::class)->getPayout($startedBond);

                    //add the payout to the wallet;
                    app(ClientBondService::class)->addPayout($startedBond, $payout);

                    // dispatch job to notify client that they have a new payout

                    // calculate the next payout date
                    $nextCapitalPayoutDuration = $this->convertTimelineToDuration($startedBond->net_rental_income_timeline);
                    $startedBond->next_capital_payout = (new DateTime($startedBond->next_capital_payout))->modify('+'.$nextCapitalPayoutDuration)->format('Y-m-d');
                }

                //check if bond has ended
                $endDate = Carbon::parse($startedBond->end_date);
                if (!$endDate->isFuture()) {
                    //bond has ended

                    $startedBond->ended = 1;

                    // dispatch job to notify client that their bond has ended
                }

                $startedBond->save();
            }
        }


    }
}
