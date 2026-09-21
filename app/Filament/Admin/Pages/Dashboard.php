<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|array
    {
        return 3;
    }
}