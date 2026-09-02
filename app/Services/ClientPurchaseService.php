<?php

namespace app\Services;

use app\Models\Payment;
use app\Models\Order;
use app\Models\Offer;

class ClientPurchaseService
{
    public $filters = [];
    public $user = null;
    public $count = null;

    public function purchases($with = [], $offset = 0, $perPage = null)
    {
        $filter = $this->filters;
        $query = Payment::with($with)->where("confirmed", true)
            // A purchase is only "done" once its order/offer is fully paid -
            // Order and Offer already maintain this via their own "completed"
            // flag (Order: balance <= 0 on every update; Offer: set on full
            // settlement in OfferService), so reuse it instead of re-deriving it.
            ->whereHasMorph('purchase', [Order::class, Offer::class], function ($q) {
                $q->where('completed', 1);
            })
            // A one-off purchase has a single confirmed payment, which is
            // trivially "the complete one". An installment purchase can have
            // several confirmed payments before it's fully paid - only the
            // last one is the payment that actually completed it, so only
            // that row should represent the purchase here.
            ->where('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('payments as latest_payment')
                    ->whereColumn('latest_payment.purchase_id', 'payments.purchase_id')
                    ->whereColumn('latest_payment.purchase_type', 'payments.purchase_type')
                    ->where('latest_payment.confirmed', true);
            });

        if ($this->user !== null) {
            $query->whereHas('client', function ($q) {
                $q->where('referer_id', $this->user->id)->where('referer_type', $this->user::class);
            });
        }

        if (isset($filter['start'])) $query->whereDate("payment_date", ">=", $filter['start']);
        if (isset($filter['end'])) $query->whereDate("payment_date", "<=", $filter['end']);

        if (isset($filter['status'])) $query->whereHas('purchase', function ($q) use ($filter) {
            $q->where("payment_status_id", $filter['status']);
        });

        if (isset($filter['projectType'])) $query->whereHas('purchase', function ($q) use ($filter) {
            $q->whereHas('package', function ($q2) use ($filter) {
                $q2->whereHas('project', function ($q3) use ($filter) {
                    $q3->whereHas('projectType', function ($q4) use ($filter) {
                        $q4->where("name", $filter['projectType']);
                    });
                });
            });
        });

        if (isset($filter['text'])) $query->where(function ($q) use ($filter) {
            $q->whereHas('client', function ($q2) use ($filter) {
                $q2->where("firstname", "LIKE", "%" . $filter['text'] . "%")
                    ->orWhere("lastname", "LIKE", "%" . $filter['text'] . "%")
                    ->orWhere("email", "LIKE", "%" . $filter['text'] . "%");
            })->orWhereHas('purchase', function ($q2) use ($filter) {
                $q2->whereHas('package', function ($q3) use ($filter) {
                    $q3->where("name", "LIKE", "%" . $filter['text'] . "%")
                        ->orWhereHas('project', function ($q4) use ($filter) {
                            $q4->where("name", "LIKE", "%" . $filter['text'] . "%");
                        });
                });
            });
        });

        if ($this->count) return $query->count();

        if ($perPage == null) $perPage = config('pagination.PER_PAGE');
        return $query->offset($offset)->limit($perPage)->orderBy("payment_date", "DESC")->get();
    }
}
