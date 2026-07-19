<?php

namespace app\Services;

use app\Models\HybridStaffDraw;

class HybridStaffDrawService
{
    public function getOpenDraw()
    {
        return HybridStaffDraw::where('completed', false)->first();
    }

    public function IncreaseSelected(HybridStaffDraw $draw)
    {
        $draw->selected = $draw->selected + 1;
        if ($draw->selected >= $draw->total) {
            $draw->completed = true;
        }
        $draw->save();
        return $draw;
    }

    public function openDraw(int $total)
    {
        $draw = new HybridStaffDraw;
        $draw->total = $total;
        $draw->selected = 1;
        $draw->completed = ($total <= 1);
        $draw->save();
        return $draw;
    }
}
