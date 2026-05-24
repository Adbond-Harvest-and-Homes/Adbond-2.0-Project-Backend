<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBond extends Model
{
    use HasFactory;

    public function getTotalAttribute()
    {
        return $this->current_capital + $this->payouts->sum('payout_amount');
    }

    public static $type = "app\Models\ClientBond";

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function parentBond()
    {
        return $this->belongsTo(ClientBond::class, "parent_bond_id", "id");
    }

    public function clientPackage()
    {
        return $this->hasOne(ClientPackage::class, "purchase_id");
    }

    public function payouts()
    {
        return $this->hasMany(ClientBondPayout::class);
    }

    /**
     * Get all files associated with the ClientPackage.
     */
    public function files()
    {
        return $this->morphMany(File::class, 'belongs');
    }

    public function mou()
    {
        return $this->belongsTo(File::class, "mou_file_id", "id");
    }

    public function markDocUploaded()
    {
        $this->docs_uploaded = 1;
        $this->save();

        return $this;
    }

    public function markMouSent()
    {
        $this->mou_sent = 1;
        $this->save();

        return $this;
    }



    /*
    |--------------------------------------------------------------------------
    | Payout Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the total number of payout periods over the bond duration.
     * Based on the rental income timeline.
     */
    public function getTotalPeriods(): int
    {
        $durationInMonths = $this->getDurationInMonths();

        return match($this->net_rental_income_timeline) {
            'weekly'    => (int) round($durationInMonths * 4.33),
            'monthly'   => $durationInMonths,
            'quarterly' => (int) floor($durationInMonths / 3),
            'yearly'    => (int) floor($durationInMonths / 12),
            default     => $durationInMonths,
        };
    }

    /**
     * Get the total number of appreciation periods over the bond duration.
     * Based on the asset appreciation timeline.
     */
    public function getTotalAppreciationPeriods(): int
    {
        $durationInMonths = $this->getDurationInMonths();

        return match($this->asset_appreciation_timeline) {
            'weekly'    => (int) round($durationInMonths * 4.33),
            'monthly'   => $durationInMonths,
            'quarterly' => (int) floor($durationInMonths / 3),
            'yearly'    => (int) floor($durationInMonths / 12),
            default     => $durationInMonths,
        };
    }

    /**
     * Convert the bond duration to months regardless of duration_metric.
     */
    public function getDurationInMonths(): int
    {
        return match($this->duration_metric) {
            'years'  => $this->duration * 12,
            'months' => $this->duration,
            default  => $this->duration,
        };
    }

    /**
     * Calculate how many rental periods fall within one appreciation period.
     * Used to sync the two timelines when they differ.
     */
    public function getRentalPeriodsPerAppreciationPeriod(): float
    {
        $totalRentalPeriods       = $this->getTotalPeriods();
        $totalAppreciationPeriods = $this->getTotalAppreciationPeriods();

        if ($totalAppreciationPeriods === 0) {
            return 0;
        }

        return $totalRentalPeriods / $totalAppreciationPeriods;
    }

    /**
     * Main calculation — walks through each rental period, applying rental
     * income on the current capital, and grows the capital at the correct
     * appreciation interval when the timelines differ.
     *
     * Returns an array with a breakdown of all components.
     */
    public function calculateTotalPayout(): array
    {
        $totalRentalPeriods              = $this->getTotalPeriods();
        $rentalPeriodsPerAppreciation    = $this->getRentalPeriodsPerAppreciationPeriod();
        $capital                         = $this->start_capital;
        $totalRentalIncome               = 0.0;
        $nextAppreciationAt              = $rentalPeriodsPerAppreciation; // when to apply next appreciation

        for ($period = 1; $period <= $totalRentalPeriods; $period++) {

            // 1. Calculate rental income on the current capital for this period
            $rentalThisPeriod = match($this->net_rental_income_measurement) {
                'percentage' => ($this->net_rental_income / 100) * $capital,
                'fixed'      => $this->net_rental_income,
                default      => 0.0,
            };

            $totalRentalIncome += $rentalThisPeriod;

            // 2. Grow capital when we've crossed an appreciation period boundary
            if ($period >= $nextAppreciationAt) {
                $capital += match($this->asset_appreciation_measurement) {
                    'percentage' => ($this->asset_appreciation / 100) * $capital,
                    'fixed'      => $this->asset_appreciation,
                    default      => 0.0,
                };

                $nextAppreciationAt += $rentalPeriodsPerAppreciation;
            }
        }

        $totalAppreciation = $capital - $this->start_capital;
        $totalPayout       = $totalRentalIncome + $totalAppreciation;
        $totalWithCapital  = $totalPayout + $this->start_capital;

        return [
            'start_capital'      => $this->start_capital,
            'end_capital'        => round($capital, 2),
            'total_rental_income'=> round($totalRentalIncome, 2),
            'total_appreciation' => round($totalAppreciation, 2),
            'total_payout'       => round($totalPayout, 2),       // earnings only
            'total_with_capital' => round($totalWithCapital, 2),  // earnings + capital returned
        ];
    }

    /**
     * Shorthand to get just the total payout figure.
     */
    public function getTotalPayout(): float
    {
        return $this->calculateTotalPayout()['total_payout'];
    }

    /**
     * Shorthand to get total payout including capital return.
     */
    public function getTotalWithCapital(): float
    {
        return $this->calculateTotalPayout()['total_with_capital'];
    }
}
