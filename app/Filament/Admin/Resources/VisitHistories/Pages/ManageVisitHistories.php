<?php

namespace App\Filament\Admin\Resources\VisitHistories\Pages;

use App\Exports\VisitHistoryExport;
use App\Filament\Admin\Resources\VisitHistories\VisitHistoryResource;
use App\Filament\Support\SweetAlert;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageVisitHistories extends ManageRecords
{
    protected static string $resource = VisitHistoryResource::class;

    protected static ?string $title = 'Riwayat Kunjungan';

    protected function getHeaderActions(): array
    {
        return [];
    }    
}
