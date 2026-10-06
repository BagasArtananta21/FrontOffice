<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

#[Fillable(['jenis', 'periode'])]
class NomorCounter extends Model
{
    use BelongsToOpd, HasUuids;

    protected $table = 'nomor_counters';

    protected function casts(): array
    {
        return [
            'nomor_terakhir' => 'integer',
        ];
    }
}
    