<?php

namespace App\Exports\Concerns;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;

trait WritesTextCells
{
    private function textCell(?string $value): Cell
    {
        return filled($value) ? new StringCell($value, null) : Cell::fromValue(null);
    }
}
