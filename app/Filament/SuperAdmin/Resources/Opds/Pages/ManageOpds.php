<?php

namespace App\Filament\SuperAdmin\Resources\Opds\Pages;

use App\Filament\SuperAdmin\Resources\Opds\OpdResource;
use App\Models\Opd;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageOpds extends ManageRecords
{
    protected static string $resource = OpdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Model {
                    $opd = new Opd();
                    $opd->forceFill($data)->save();

                    return $opd;
                }),
        ];
    }
}
