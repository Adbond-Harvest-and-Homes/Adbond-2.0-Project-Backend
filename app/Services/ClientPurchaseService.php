<?php

namespace app\Services;

use app\Models\Payment;
use app\Models\Order;

class ClientPurchaseService
{
    public $filters = [];
    public $user = null;
    public $count = null;

    public function purchases($with = [], $offset = 0, $perPage = null)
    {
        $filter = $this->filters;
        $query = Payment::with($with)
            ->where("purchase_type", Order::$type)
            ->where("confirmed", 1)
            ->whereHas('purchase', function ($q) {
                $q->where("type", "purchase");
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
