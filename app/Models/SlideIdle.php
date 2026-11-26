<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOpd;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SlideIdle extends Model
{
    use BelongsToOpd, HasUuids;

    public const DISK = 'public';
    public const DIRECTORY = 'slide-idle';

    protected $table = 'slide_idle';

    public function url(): string
    {
        return asset('storage/'.$this->gambar);
    }
}
