<?php

namespace App\Filament\Admin\Resources\VisitHistories\Pages;

use App\Filament\Admin\Resources\VisitHistories\VisitHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageVisitHistories extends ManageRecords
{
    protected static string $resource = VisitHistoryResource::class;

    protected static ?string $title = 'Riwayat Kunjungan';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
