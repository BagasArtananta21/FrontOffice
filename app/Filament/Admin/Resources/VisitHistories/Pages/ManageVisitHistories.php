<?php

namespace App\Filament\Admin\Resources\VisitHistories\Pages;

use App\Filament\Admin\Resources\VisitHistories\VisitHistoryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVisitHistories extends ManageRecords
{
    protected static string $resource = VisitHistoryResource::class;

    protected static ?string $title = 'Riwayat Kunjungan';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
