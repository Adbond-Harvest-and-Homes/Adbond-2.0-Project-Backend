<?php

namespace app\Services;

use PDF;
use app\Notifications\APIPasswordResetNotification;
use app\Exceptions\UserNotFoundException;

use app\Models\Payment;
use app\Models\Order;

use app\Exports\TransactionExport;

use app\Helpers;

/**
 * Transaction service class
 */
class TransactionService
{
    public $clientId = null;
    public $count = null;
    public $filters = [];

    public function transactions($with=[], $offset=0, $perPage=null)
    {
        $filter = $this->filters;
        $query = Payment::with($with)->where("purchase_type", Order::$type);

        if(array_key_exists('status', $filter)) {
            ($filter['status'] === null) ? $query->whereNull("confirmed") : $query->where("confirmed", $filter['status']);
        }

        if($this->clientId) $query->where("client_id", $this->clientId);
        if(isset($filter['text'])) $query->where("receipt_no", "LIKE", "%".$filter['text']."%")->orWhere("amount", $filter['text'])
            ->orWhereHas('client', function($q) use($filter) {
                $q->where("firstname", "LIKE", "%".$filter['text']."%")->orWhere("lastname", "LIKE", "%".$filter['text']."%");
            })
            ->orWhereHas('purchase', function($query2) use($filter) {
            $query2->whereHas('package', function($query3) use($filter) {
                $query3->where("name", "LIKE", "%".$filter['text']."%")->orWhereHas('project', function($query4) use($filter) {
                    $query4->where("name", "LIKE", "%".$filter['text']."%");
                });
            });
        });
        if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
        if(isset($filter['projectType'])) $query = $query->whereHas('purchase', function($query1) use($filter) {
            $query1->whereHas('package', function($query2) use($filter) {
                $query2->whereHas('project', function($query3) use($filter) {
                    $query3->whereHas("projectType", function($query4) use($filter) {
                        $query4->where("name", $filter['projectType']);
                    });
                });
            });
        });
        if($this->count) return $query->count();

        if($perPage==null) $perPage=config('pagination.PER_PAGE');
        // dd($perPage);
        return $query->offset($offset)->limit($perPage)->orderBy("created_at", "DESC")->get();
        // dd($query->toSql(), $query->getBindings());
    }

    public function transaction($id, $with=[])
    {
        return Payment::with($with)->where("id", $id)->first();
    }

    public function filter($filter, $with=[], $offset=0, $perPage=null)
    {
        $query = Payment::with($with);
        if($this->clientId) $query->where("client_id", $this->clientId);
        if(isset($filter['text'])) $query->where("receipt_no", "LIKE", "%".$filter['text']."%")->orWhereHas('purchase', function($query2) use($filter) {
            $query2->whereHas('package', function($query3) use($filter) {
                $query3->where("name", "LIKE", "%".$filter['text']."%")->orWhereHas('project', function($query4) use($filter) {
                    $query4->where("name", "LIKE", "%".$filter['text']."%");
                });
            });
        });
        if(isset($filter['date'])) $query = $query->whereDate("created_at", $filter['date']);
        if(isset($filter['projectType'])) $query = $query->whereHas('purchase', function($query1) use($filter) {
            $query1->whereHas('package', function($query2) use($filter) {
                $query2->whereHas('project', function($query3) use($filter) {
                    $query3->whereHas("projectType", function($query4) use($filter) {
                        $query4->where("name", $filter['projectType']);
                    });
                });
            });
        });
        if($this->count) return $query->count();
        return $query->orderBy("created_at", "DESC")->offset($offset)->limit($perPage)->get();
    }

    public function exportToPDF($transactions, $clientName, $headingConfig = null)
    {
        $export = new TransactionExport($transactions, $headingConfig);
        $data = [
            'headings' => $export->headings(),
            'transactions' => $transactions,
            'name' => $clientName,
            'mappedData' => $transactions->map(function ($transaction) use ($export) {
                return $export->map($transaction);
            })
        ];

        $pdf = PDF::loadView('exports.pdf.transactions', $data);
        return $pdf->download('transactions-' . now()->format('Y-m-d') . '.pdf');
    }
    
}
