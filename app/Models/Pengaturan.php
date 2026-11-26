<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


#[Fillable(['kunci', 'nilai'])]
class Pengaturan extends Model
{
    use HasUuids;

    protected $table = 'pengaturan';
}
